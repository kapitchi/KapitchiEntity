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
    
    public function execute(MvcEvent $event)
    {
        $ret = array(
            'error' => true,
        );
        try {
            return parent::execute($event);
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
        $this->getEventManager()->trigger('create', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    public function delete($id) {
        $service = $this->getEntityService();
        $ret = $service->remove($id);
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('delete', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    public function get($id) {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        $entity = $service->find($id);
        
        $ret = array(
            'id' => $id,
            'entity' => $hydrator->extract($entity),
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('get', $this, array(
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
        $this->getEventManager()->trigger('getList', $this, array(
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
        $this->getEventManager()->trigger('update', $this, array(
            'jsonViewModel' => $jsonModel
        ));
        return $jsonModel;
    }
    
    public function getEntityService() {
        return $this->entityService;
    }

    public function setEntityService(EntityService $entityService) {
        $this->entityService = $entityService;
    }

}