<?php
namespace KapitchiEntity\Service;

use Zend\Stdlib\Hydrator\HydratorInterface as EntityHydrator,
    Zend\Paginator\Paginator,
    KapitchiBase\Service\AbstractService,
    KapitchiEntity\Mapper\EntityMapperInterface,
    KapitchiEntity\Model\EntityModelInterface;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class EntityService extends AbstractService
{
    protected $mapper;
    protected $hydrator;
    protected $entityPrototype;
    protected $inputFilter;
    
    public function __construct(EntityMapperInterface $mapper, $entityPrototype, EntityHydrator $hydrator)
    {
        $this->setMapper($mapper);
        $this->setEntityPrototype($entityPrototype);
        $this->setHydrator($hydrator);
    }
    
    public function persist($entity, $data = null)
    {
        if(!is_object($entity)) {
            throw new \KapitchiEntity\Exception\NotEntityException("Not an entity");
        }
        
        $mapper = $this->getMapper();
        try {
            if($mapper instanceof Transactional) {
                $mapper->beginTransaction();
            }
            
            $params = array(
                'data' => $data,
                'entity' => $entity,
            );
            
            //TODO excepts entity to have "id" we need EntityInterface - getId/setId!
            $entityId = $entity->getId();
            if($entityId) {
                $params['origEntity'] = $this->getMapper()->find($entityId);
            }
            //END
            
            $event = $this->createPersistEvent($params);
            $this->getEventManager()->trigger($event);
            
            if($mapper instanceof Transactional) {
                $mapper->commit();
            }
            
        } catch(\Exception $e) {
            if($mapper instanceof Transactional) {
                $mapper->rollback();
            }
            throw $e;
        }
        
        return $event;
    }
    
    public function createPersistEvent(array $params)
    {
        return new Event\PersistEvent('persist', $this, $params);
    }
    
    public function createRemoveEvent(array $params)
    {
        return new Event\RemoveEvent('remove', $this, $params);
    }
    
    public function find($priKey)
    {
        return $this->getMapper()->find($priKey);
    }
    
    public function get($priKey)
    {
        $ret = $this->find($priKey);
        if($ret) {
            return $ret;
        }
        
        throw new \KapitchiEntity\Exception\EntityNotFoundException("Entity not found");
    }
    
    /**
     * 
     * @param array $criteria
     * @param array $orderBy
     * @return \Zend\Paginator\Paginator
     */
    public function getPaginator(array $criteria = null, array $orderBy = null)
    {
        $criteria = new \ArrayObject((array)$criteria);
        $orderBy = new \ArrayObject((array)$orderBy);
        
        $ret = $this->triggerEvent('getPaginator', array(
            'criteria' => $criteria,
            'orderBy' => $orderBy
        ), function($ret) {
            return $ret instanceof Paginator;
        });
        
        $paginator = $ret->last();
        if(!$paginator instanceof Paginator) {
            throw new \Exception("TODO No paginator returned");
        }
        
        return $paginator;
    }
    
    public function findOneBy(array $criteria)
    {
        $paginator = $this->getPaginator($criteria);
        $paginator->setCurrentPageNumber(1);
        $paginator->setItemCountPerPage(2);
        if($paginator->count() > 1) {
            throw new \Exception("Ambigious entity (count > 1) in EntityService::findOneBy()");
        }
        $items = $paginator->getCurrentItems();
        return current($items);
    }
    
    public function getOneBy(array $criteria)
    {
        $ret = $this->findOneBy($criteria);
        if($ret) {
            return $ret;
        }
        
        throw new \KapitchiEntity\Exception\EntityNotFoundException("Entity not found");
    }
    
    public function remove($entity)
    {
        $mapper = $this->getMapper();
        
        if(!is_object($entity)) {
            $entity = $mapper->find($entity);
            if(!$entity) {
                throw new \KapitchiEntity\Exception\EntityNotFoundException("Entity does not exist #$id");
            }
        }
        
        try {
            if($mapper instanceof Transactional) {
                $mapper->beginTransaction();
            }
            
            $this->triggerEvent('remove', array(
                'entity' => $entity,
            ));

            if($mapper instanceof Transactional) {
                $mapper->commit();
            }
            
        } catch(\Exception $e) {
            if($mapper instanceof Transactional) {
                $mapper->rollback();
            }
            throw $e;
        }
    }
    
    /**
     * 
     * @param type $entity
     * @param type $options
     * @param \KapitchiEntity\Model\EntityModelInterface $model - used when extending classes creates different Model instances
     * @return \KapitchiEntity\Model\GenericEntityModel
     */
    public function loadModel($entity, $options = array(), EntityModelInterface $model = null)
    {
        if($model === null) {
            $model = new \KapitchiEntity\Model\GenericEntityModel($entity);
        }
        
        $this->triggerEvent('loadModel', array(
            'model' => $model,
            'entity' => $entity,
            'options' => $options
        ));
        
        return $model;
    }
    
    protected function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        
        $instance = $this;
        $events = $this->getEventManager();
        $mapper = $this->getMapper();
        
        $events->attach('find', function($e) use ($mapper) {
            $priKey = $e->getParam('priKey');
            return $mapper->find($priKey);
        });
        
        $events->attach('getPaginator', function($e) use ($mapper) {
            $criteria = $e->getParam('criteria');
            $orderBy = $e->getParam('orderBy');
            $adapter = $mapper->getPaginatorAdapter($criteria->getArrayCopy(), $orderBy->getArrayCopy());
            return new Paginator($adapter);
        });
        
        
        $events->attach('persist', function($e) use ($instance) {
            return $instance->getMapper()->persist($e->getParam('entity'));
        }, 1);
        
        //validate entity before persist if inputfilter is available
        $events->attach('persist', function($e) use ($instance) {
            $inputFilter = $instance->getInputFilter();
            if($inputFilter) {
                $entity = $e->getParam('entity');
                $data = $instance->getHydrator()->extract($entity);
                $inputFilter->setData($data);
                if(!$inputFilter->isValid()) {
                    $e = new \KapitchiEntity\Exception\ValidationException("Validation failed");
                    $e->setEntity($entity);
                    $e->setInputFilter($inputFilter);
                    throw $e;
                }
            }
        }, 10);
        
        $events->attach('remove', function($e) use ($mapper) {
            return $mapper->remove($e->getParam('entity'));
        });
    }
    
    public function createEntityInstance()
    {
        return clone $this->getEntityPrototype();
    }
    
    public function createEntityFromArray(array $data)
    {
        $entity = $this->getHydrator()->hydrate($data, $this->createEntityInstance());
        return $entity;
    }
    
    public function createArrayFromEntity($entity)
    {
        $entity = $this->getHydrator()->extract($entity);
        return $entity;
    }
    
    /**
     * @return EntityMapperInterface
     */
    public function getMapper()
    {
        return $this->mapper;
    }
    
    public function setMapper(EntityMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }
    
    /**
     * 
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    public function getHydrator()
    {
        return $this->hydrator;
    }

    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
    }
    
    public function getEntityPrototype()
    {
        return $this->entityPrototype;
    }

    public function setEntityPrototype($entityPrototype)
    {
        $this->entityPrototype = $entityPrototype;
    }
    
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    public function setInputFilter($inputFilter)
    {
        $this->inputFilter = $inputFilter;
    }

}