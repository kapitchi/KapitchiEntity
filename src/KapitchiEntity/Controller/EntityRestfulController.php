<?php

namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel,
    Zend\Mvc\MvcEvent,
    KapitchiEntity\Service\EntityService;

class EntityRestfulController extends AbstractRestfulController {
    protected $entityService;
    
    public function __construct($entityService = null)
    {
        if ($entityService !== null) {
            $this->setEntityService($entityService);
        }
    }
    
    public function onDispatch(MvcEvent $event)
    {
        $ret = array(
            'error' => true,
        );
        try {
            return parent::onDispatch($event);
        } catch(\KapitchiEntity\Service\Exception\ValidationException $e) {
            $ret['errorMsg'] = $e->getMessage();
            $ret['errorType'] = 'validation';
            $ret['messages'] = $e->getInputFilter()->getMessages();
        } catch(\Exception $e) {
            $ret['errorMsg'] = $e->getMessage();
            $ret['errorType'] = 'exception';
        }
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('error', $this, array(
            'jsonViewModel' => $jsonModel,
            'exception' => $e
        ));
        $event->setResult($jsonModel);
        return $jsonModel;
    }
    
    public function create($data) {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        $entity = $service->createEntityFromArray($data);
        $service->persist($entity, $data);
        
        $ret = array(
            'data' => $data,
            'entity' => $hydrator->extract($entity)
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('create.post', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    public function delete($id) {
        $service = $this->getEntityService();
        $ret = $service->remove($id);
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('delete.post', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    public function get($id) {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        $entity = $service->find($id);
        if(!$entity) {
            //TODO
            throw new \Exception("TODO can not find entity #$id");
        }
        
        $ret = array(
            'id' => $id,
            'entity' => $hydrator->extract($entity),
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('get.post', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }

    public function getList() {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        //TODO paginator params
        $paginator = $service->getPaginator();
        
        $entities = array();
        foreach($paginator as $item) {
            $entities[] = $hydrator->extract($item);
        }
        
        $ret = array(
            'entities' => $entities,
            'totalCount' => $paginator->getTotalItemCount(),
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('getList.post', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }

    public function update($id, $data) {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        $entity = $service->find($id);
        $hydrator->hydrate($data, $entity);
        
        $service->persist($entity, $data);
        
        $ret = array(
            'id' => $id,
            'data' => $data,
            'entity' => $hydrator->extract($entity)
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('update.post', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    protected function getEntityId($throwException = true)
    {
        $routeMatch = $this->getEvent()->getRouteMatch();
        $request = $this->getRequest();
        
        //copied from AbstractRestfulController - delete method
        if (null === $id = $routeMatch->getParam('id')) {
            if (!($id = $request->getQuery()->get('id', false)) && $throwException) {
                throw new \Exception('Missing identifier');
            }
        }
        
        return $id;
    }
    
    public function getPut() {
        $content = $this->getRequest()->getContent();
        parse_str($content, $values);
        return new \Zend\Stdlib\Parameters($values);
    }
    
    public function getEntityService() {
        return $this->entityService;
    }

    public function setEntityService(EntityService $entityService) {
        $this->entityService = $entityService;
    }

}