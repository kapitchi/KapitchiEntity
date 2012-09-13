<?php
namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\Http\Response,
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
        
        $entity = $this->getEntityService()->find($id);
        if(!$entity) {
            //TODO
            throw new \Exception("No entity found [id: '$id']");
        }
        
        $model = $this->getEntityService()->loadModel($entity);
        
        $viewModel = $this->getEntityViewModel();
        $viewModel->setVariables(array(
            'model' => $model
        ));
        
        $this->getEventManager()->trigger('view.post', $this, array(
            'viewModel' => $viewModel,
            'model' => $model,
        ));
        
        return $viewModel;
    }
    
    public function createAction()
    {
        $form = $this->getEntityForm();
        $this->getEventManager()->trigger('create.pre', $this, array(
            'form' => $form,
        ));
        
        $service = $this->getEntityService();
        if($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form->setData($data);
            if($form->isValid()) {
                $values = $form->getInputFilter()->getValues();
                $entity = $service->createEntityFromArray($values);
                $persistEvent = $service->persist($entity, $values);
                
                $ret = $this->getEventManager()->trigger('create.persist.post', $this, array(
                    'form' => $form,
                    'entity' => $entity,
                    'persistEvent' => $persistEvent,
                ), function($ret) {
                    return ($ret instanceof Response);
                });
                $last = $ret->last();
                if($last instanceof Response) {
                    return $last;
                }
            }
        }
        
        $viewModel = $this->getEntityViewModel();
        $viewModel->setVariables(array(
            'form' => $form,
        ));
        
        $this->getEventManager()->trigger('create.post', $this, array(
            'viewModel' => $viewModel,
            'form' => $form,
        ));
        
        return $viewModel;
    }
    
    public function updateAction()
    {
        $id = $this->getCurrentEntityId();
        $service = $this->getEntityService();
        
        $entity = $service->find($id);
        if(!$entity) {
            //TODO
            throw new \Exception("No entity found [id: '$id']");
        }
        
        $form = $this->getEntityForm();
        $form->setAttribute('action', $this->getUpdateUrl($entity));
        $eventParams = array(
            'form' => $form,
            'entity' => $entity,
        );
        
        if($this->getRequest()->isPost()) {
            $values = $this->getRequest()->getPost()->toArray();
            $form->setData($values);
            
            if($form->isValid()) {
                $data = $form->getData();
                $service->getHydrator()->hydrate($data, $entity);
                $eventParams['persistEvent'] = $service->persist($entity, $data);
                $ret = $this->getEventManager()->trigger('update.persist.post', $this, $eventParams, function($ret) {
                    return ($ret instanceof Response);
                });
                $last = $ret->last();
                if($last instanceof Response) {
                    return $last;
                }
            }
        }
        
        $eventParams['formData'] = new \ArrayObject($service->getHydrator()->extract($entity));
        
        $model = $this->getEntityService()->loadModel($entity);
        
        $viewModel = $this->getEntityViewModel();
        $viewModel->setVariables(array(
            'model' => $model,
            'form' => $form,
        ));
        
        $eventParams['model'] = $model;
        $eventParams['viewModel'] = $viewModel;
        $this->getEventManager()->trigger('update.post', $this, $eventParams);

        $form->setData($eventParams['formData']);

        return $viewModel;
    }
    
    public function removeAction()
    {
        $id = $this->getCurrentEntityId();
        
        $service = $this->getEntityService();
        $entity = $service->find($id);
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
    
}