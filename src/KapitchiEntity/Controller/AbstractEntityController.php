<?php
namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\Http\Response,
    Zend\EventManager\EventManagerInterface,
    Zend\Paginator\Paginator,
    KapitchiEntity\View\Model\EntityViewModel,
    KapitchiEntity\Service\EntityService;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
abstract class AbstractEntityController extends AbstractActionController
{
    protected $entityService;
    protected $entityForm;
    protected $entityViewModel;
    
    abstract public function getUpdateUrl($entity);
    abstract public function getIndexUrl();
    
    public function getCurrentEntityId() {
        return $this->getEvent()->getRouteMatch()->getParam('id');
    }

    public function getCurrentPageNumber()
    {
        return $this->getRequest()->getQuery()->page;
    }
    
    public function indexAction()
    {
        $ret = $this->getEventManager()->trigger('index.pre', $this);
        
        $pageNumber = $this->getCurrentPageNumber();
        
        $criteria = new \ArrayObject(array());
        $orderBy = new \ArrayObject(array());
        $ret = $this->getEventManager()->trigger('index.paginator', $this, array(
            'pageNumber' => $pageNumber,
            'paginatorCriteria' => $criteria,
            'paginatorOrderBy' => $orderBy,
        ), function($ret) {
            return $ret instanceof Paginator;
        });
        $paginator = $ret->last();
        if(!$paginator instanceof Paginator) {
            throw new \Exception("TODO index action expects paginator object");
        }
        $paginator->setCurrentPageNumber($pageNumber);
        
        $viewModel = $this->getEntityViewModel();
        $viewModel->setVariables(array(
            'paginator' => $paginator,
        ));
        
        $this->getEventManager()->trigger('index.post', $this, array(
            'viewModel' => $viewModel,
        ));
        
        return $viewModel;
    }
    
    public function viewAction()
    {
        $id = $this->getCurrentEntityId();
        
        $service = $this->getEntityService();
        $entity = $service->get($id);
        
        $viewModel = $this->getEntityViewModel();
        
        $form = $this->getEntityForm();
        if($form) {
            $form->setAttribute('readonly', true);
            $form->setAttribute('action', '#');
            $form->setData($service->createArrayFromEntity($entity));
        }
        
        $viewModel->setVariables(array(
            'entity' => $entity,
            'form' => $form,
        ));
        
        $this->getEventManager()->trigger('view.post', $this, array(
            'viewModel' => $viewModel,
            'form' => $form,
            'entity' => $entity,
        ));
        
        return $viewModel;
    }
    
    public function createAction()
    {
        $form = $this->getEntityForm();
        $viewModel = $this->getEntityViewModel();
        
        $eventParams = array(
            'form' => $form,
            'viewModel' => $viewModel,
        );
        
        $this->getEventManager()->trigger('create.pre', $this, $eventParams);
        
        if($this->getRequest()->isPost()) {
            $ret = $this->getEventManager()->trigger('create.persist', $this, $eventParams, function($ret) {
                return ($ret instanceof Response || $ret instanceof \Zend\View\Model\ModelInterface);
            });
            $last = $ret->last();
            if($last instanceof Response || $last instanceof \Zend\View\Model\ModelInterface) {
                return $last;
            }
        }
        
        $viewModel->setVariables(array(
            'form' => $form,
        ));
        
        $this->getEventManager()->trigger('create.post', $this, $eventParams);
        
        return $viewModel;
    }
    
    public function updateAction()
    {
        $id = $this->getCurrentEntityId();
        $service = $this->getEntityService();
        
        $entity = $service->find($id);
        if(!$entity) {
            //TODO
            throw new \KapitchiEntity\Exception\EntityNotFoundException("No entity found [id: '$id']");
        }
        
        $form = $this->getEntityForm();
        $form->setAttribute('action', $this->getUpdateUrl($entity));
        
        $viewModel = $this->getEntityViewModel();
        $eventParams = array(
            'viewModel' => $viewModel,
            'form' => $form,
            'entity' => $entity,
        );
        
        $this->getEventManager()->trigger('update.pre', $this, $eventParams);
        
        if($this->getRequest()->isPost()) {
            $ret = $this->getEventManager()->trigger('update.persist', $this, $eventParams, function($ret) {
                return ($ret instanceof Response);
            });
            $last = $ret->last();
            if($last instanceof Response) {
                return $last;
            }
        } else {
            $form->setData($service->createArrayFromEntity($entity));
            $this->getEventManager()->trigger('update.load', $this, $eventParams);
        }
        
        $model = $this->getEntityService()->loadModel($entity);
        
        $viewModel->setVariables(array(
            'entity' => $entity,
            'model' => $model,//DEPRECATED - we probably delete whole model stuff
            'form' => $form,
        ));
        
        $eventParams['model'] = $model;
        $eventParams['viewModel'] = $viewModel;
        $this->getEventManager()->trigger('update.post', $this, $eventParams);

        return $viewModel;
    }
    
    public function removeAction()
    {
        $id = $this->getCurrentEntityId();
        
        $service = $this->getEntityService();
        $entity = $service->find($id);
        if(!$entity) {
            throw new \KapitchiEntity\Exception\EntityNotFoundException("No entity found [id: '$id']");
        }
        
        //TODO remove by ID? or whole entity object?
        $service->remove($id);
        
        $ret = $this->getEventManager()->trigger('remove.post', $this, array(
            'entity' => $entity,
        ), function($ret) {
            return ($ret instanceof Response);
        });
        $last = $ret->last();
        if($last instanceof Response) {
            return $last;
        }
        
    }
    
    protected function attachDefaultListeners()
    {
        parent::attachDefaultListeners();
        
        $events = $this->getEventManager();
        $instance = $this;
        
        $events->attach('remove.post', function($e) use ($instance) {
            return $instance->redirect()->toUrl($instance->getIndexUrl());
        });
        
        $events->attach('create.persist.post', function($e) use ($instance) {
            $entity = $e->getParam('entity');
            return $instance->redirect()->toUrl($instance->getUpdateUrl($entity));
        });
        
        $events->attach('index.paginator', function($e) {
            $criteria = $e->getParam('paginatorCriteria');
            $orderBy = $e->getParam('paginatorOrderBy');
            return $e->getTarget()->getEntityService()->getPaginator($criteria->getArrayCopy(), $orderBy->getArrayCopy());
        });
        
        $events->attach('create.persist', function($e) {
            $cont = $e->getTarget();
            $form = $e->getParam('form');
            $service = $cont->getEntityService();
            
            $data = $cont->getRequest()->getPost()->toArray();
            $form->setData($data);
            if($form->isValid()) {
                $values = $form->getInputFilter()->getValues();
                $entity = $service->createEntityFromArray($values);
                $persistEvent = $service->persist($entity, $values);
                
                $ret = $cont->getEventManager()->trigger('create.persist.post', $cont, array(
                    'form' => $form,
                    'entity' => $entity,
                    'persistEvent' => $persistEvent,
                ), function($ret) {
                    return ($ret instanceof Response || $ret instanceof \Zend\View\Model\ModelInterface);
                });
                $last = $ret->last();
                if($last instanceof Response || $last instanceof \Zend\View\Model\ModelInterface) {
                    return $last;
                }
            }
        });
        
        $events->attach('update.persist', function($e) {
            $cont = $e->getTarget();
            $form = $e->getParam('form');
            $service = $cont->getEntityService();
            
            $values = $cont->getRequest()->getPost()->toArray();
            $form->setData($values);
            if($form->isValid()) {
                $data = $form->getData();
                $entity = $e->getParam('entity');
                $service->getHydrator()->hydrate($data, $entity);
                $e->setParam('persistEvent', $service->persist($entity, $data));
            }
        });
    }
    
    /**
     * 
     * @return EntityService
     */
    public function getEntityService()
    {
        return $this->entityService;
    }

    public function setEntityService(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    public function getEntityForm()
    {
        return $this->entityForm;
    }

    public function setEntityForm($entityForm)
    {
        $this->entityForm = $entityForm;
    }
    
    /**
     * 
     * @return EntityViewModel
     */
    public function getEntityViewModel()
    {
        if($this->entityViewModel === null) {
            $this->entityViewModel = new EntityViewModel();
        }
        return $this->entityViewModel;
    }

    public function setEntityViewModel($entityViewModel)
    {
        $this->entityViewModel = $entityViewModel;
    }
    
    /**
     * Adds all subclass identifiers
     * 
     * @author Matus Zeman <mz@kapitchi.com>
     * @param  EventManagerInterface $events
     * @return AbstractController
     */
    public function setEventManager(EventManagerInterface $events)
    {
        
        $events->setIdentifiers(array(
            'Zend\Stdlib\DispatchableInterface',
            'Zend\Mvc\Controller\AbstractController',
            'Zend\Mvc\Controller\AbstractActionController',
            __CLASS__,
            get_called_class(),
            $this->eventIdentifier,
            substr(get_called_class(), 0, strpos(get_called_class(), '\\'))
        ));
        $this->events = $events;
        $this->attachDefaultListeners();

        return $this;
    }
}