<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractRestfulController,
    Zend\View\Model\JsonModel,
    Zend\Mvc\MvcEvent,
    KapitchiEntity\Service\EntityService;

class EntityRestfulController extends AbstractRestfulController {
    protected $eventIdentifier = __CLASS__;
    protected $entityService;
    
    public function __construct($entityService = null)
    {
        if ($entityService !== null) {
            $this->setEntityService($entityService);
        }
    }
    
    public function onDispatch(MvcEvent $event)
    {
        $data = array(
            'error' => true,
        );
        try {
            /**
             * @todo we should move ErrorHandler stuff into KapDev maybe
             * as any PHP errors seems to be discovered during dev only?
             */
            \Zend\Stdlib\ErrorHandler::start(E_ALL | E_STRICT);
            $ret = parent::onDispatch($event);
            \Zend\Stdlib\ErrorHandler::stop(true);
            return $ret;
        } catch(\KapitchiEntity\Exception\ValidationException $e) {
            $event->getResponse()->setStatusCode(403);
            $data['errorMsg'] = $e->getMessage();
            $data['errorType'] = 'validation';
            $data['messages'] = $e->getInputFilter()->getMessages();
        } catch(\Exception $e) {
            $event->getResponse()->setStatusCode(500);
            $data['errorMsg'] = $e->getMessage();
            $data['errorType'] = 'exception';
        }
        
        $jsonModel = new JsonModel($data);
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
            'jsonViewModel' => $jsonModel,
            'entity' => $entity,
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
            'paginator' => $paginator,
            'jsonViewModel' => $jsonModel,
        ));
        return $jsonModel;
    }

    public function update($id, $data) {
        $service = $this->getEntityService();
        $hydrator = $service->getHydrator();
        
        $entity = $service->find($id);
        $hydrator->hydrate($data, $entity);
        
        $persistEvent = $service->persist($entity, $data);
        
        $ret = array(
            'id' => $id,
            'data' => $data,
            'entity' => $hydrator->extract($entity)
        );
        
        $jsonModel = new JsonModel($ret);
        $this->getEventManager()->trigger('update.post', $this, array(
            'jsonViewModel' => $jsonModel,
            'persistEvent' => $persistEvent,
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
    
    /**
     * 
     * @return \KapitchiEntity\Service\EntityService
     */
    public function getEntityService() {
        return $this->entityService;
    }

    public function setEntityService(EntityService $entityService) {
        $this->entityService = $entityService;
    }
    
    protected function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        
        $events = $this->getEventManager();
        $instance = $this;
        
    } 

}