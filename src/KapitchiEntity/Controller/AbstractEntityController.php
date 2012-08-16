<?php
namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractActionController,
    Zend\Http\Response,
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
    protected $entityHelper;
    
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
        $pageNumber = $this->getCurrentPageNumber();
        
        $this->getEventManager()->trigger('index.pre', $this, array(
            'pageNumber' => $pageNumber,
        ));
        
        $paginator = $this->getEntityService()->getPaginator();
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
        $service = $this->getEntityService();
        
        if($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form->setData($data);
            if($form->isValid()) {
                $values = $form->getInputFilter()->getValues();
                $entity = $service->createEntityFromArray($values);
                $service->persist($entity, $values);
                
                $ret = $this->getEventManager()->trigger('create.persist.post', $this, array(
                    'form' => $form,
                    'entity' => $entity,
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
        
        if($this->getRequest()->isPost()) {
            $values = $this->getRequest()->getPost()->toArray();
            $form->setData($values);
            
            if($form->isValid()) {
                $data = $form->getData();
                $service->getHydrator()->hydrate($data, $entity);
                $service->persist($entity, $data);
                
                $ret = $this->getEventManager()->trigger('update.persist.post', $this, array(
                    'form' => $form,
                    'entity' => $entity,
                ), function($ret) {
                    return ($ret instanceof Response);
                });
                $last = $ret->last();
                if($last instanceof Response) {
                    return $last;
                }
            }
        }
        
        $form->setData($service->getHydrator()->extract($entity));
        
        $model = $this->getEntityService()->loadModel($entity);
        
        $viewModel = $this->getEntityViewModel();
        $viewModel->setVariables(array(
            'model' => $model,
            'form' => $form,
        ));
        
        $this->getEventManager()->trigger('update.post', $this, array(
            'viewModel' => $viewModel,
            'model' => $model,
            'form' => $form,
        ));
        
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
    
    public function getEntityHelper()
    {
        return $this->entityHelper;
    }

    public function setEntityHelper($entityHelper)
    {
        $this->entityHelper = $entityHelper;
    }

}