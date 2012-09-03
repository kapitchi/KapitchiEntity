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
    
    /**
     * @param array $data
     * @return object
     */
    public function persist($entity, $data = null)
    {
        if(!is_object($entity)) {
            throw new \Exception('Not an entity object');
        }
        
        $mapper = $this->getMapper();
        try {
            if($mapper instanceof Transactional) {
                $mapper->beginTransaction();
            }
            
            $this->triggerEvent('persist', array(
                'data' => $data,
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
        
        return $entity;
    }
    
    public function find($priKey)
    {
        return $this->getMapper()->find($priKey);
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
        $this->triggerEvent('getPaginator.pre', array(
            'criteria' => $criteria,
            'orderBy' => $orderBy
        ));
        
        $adapter = $this->getMapper()->getPaginatorAdapter($criteria->getArrayCopy(), $orderBy->getArrayCopy());
        $paginator = new Paginator($adapter);
        
        $this->triggerEvent('getPaginator.post', array(
            'paginator' => $paginator
        ));
        
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
    
    public function remove($entity)
    {
        $mapper = $this->getMapper();
        
        if(!is_object($entity)) {
            $entity = $mapper->find($entity);
            if(!$entity) {
                throw new \Exception("Entity does not exist #$id");
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
            $model = new \KapitchiEntity\Model\GenericEntityModel();
            $model->setEntity($entity);
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
        
        $events->attach('findOneBy', function($e) use ($mapper) {
            $criteria = $e->getParam('criteria');
            return $mapper->findOneBy($criteria);
        });
        
        $events->attach('find', function($e) use ($mapper) {
            $priKey = $e->getParam('priKey');
            return $mapper->find($priKey);
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
                    $e = new Exception\ValidationException("Validation failed on the entity");
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