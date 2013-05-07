<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Http\Response;
use Zend\EventManager\EventManagerInterface;
use Zend\Paginator\Paginator;
use KapitchiEntity\View\Model\EntityViewModel;
use KapitchiEntity\Service\EntityService;
use KapitchiEntity\Exception\NotEntityException;

/**
 * Action controller implementing common actions to manage entities:
 * - indexAction() - list entities using Zend\Paginator
 * - viewAction() - loads entity for readonly type rendering
 * - createAction()
 * - updateAction()
 * - removeAction()
 * 
 * This works out-of-the-box with route set up in following way:
 * <code>
 *  'identity' => array(
 *      'type'    => 'Segment',
 *      'options' => array(
 *          'route' => '/identity[/:action[/:id]]',
 *          'constraints' => array(
 *              'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
 *          ),
 *          'defaults' => array(
 *              'controller' => 'Identity',
 *          ),
 *      ),
 *  ),
 * </code>
 * 
 * @author Matus Zeman <mz@kapitchi.com>
 * @deprecated since version 0.1 This will be renamed to EntityController
 */
class EntityController extends AbstractActionController
{
    protected $entityService;
    protected $entityForm;
    protected $entityViewModel;
    
    /**
     * Default implementation of this method returns entity update URL
     * using matched route name and setting action = 'update', id = $entity->getId()
     * If you have different routes set overwrite this method in your concrete controller
     * 
     * @deprecated since version 0.1 This method becomes protected
     */
    protected function getUpdateUrl($entity)
    {
        if(!$this->getEntityService()->isEntityInstance($entity)) {
            throw new NotEntityException();
        }
        
        return $this->url()->fromRoute($this->getEvent()->getRouteMatch()->getMatchedRouteName(), array(
            'action' => 'update', 'id' => $entity->getId()
        ));
    }
    
    /**
     * Default implementation of this method returns entity index URL
     * using matched route name and setting action = 'index'
     * If you have different routes set overwrite this method in your concrete controller
     * 
     * @deprecated since version 0.1 This method becomes protected
     */
    protected function getIndexUrl()
    {
        return $this->url()->fromRoute($this->getEvent()->getRouteMatch()->getMatchedRouteName(), array(
            'action' => 'index'
        ));
    }
    
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
        $form->setAttribute('readonly', true);
        $form->setAttribute('action', '#');
        $form->setData($service->createArrayFromEntity($entity));
        
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
        
        $submitLabel = 'Create';
        //@todo mz: what's the best way to set form action?
        //$form->setAttribute('action', '???');
        $form->add(array(
            'name' => 'submit',
            'type' => 'Zend\Form\Element\Submit',
            'options' => array(
                'label' => $submitLabel,
            ),
            'attributes' => array(
                'value' => $submitLabel,
            )
        ));
        
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
        
        //@todo mz: translate
        $submitLabel = 'Update';
        $form = $this->getEntityForm();
        $form->setAttribute('action', $this->getUpdateUrl($entity));
        $form->add(array(
            'name' => 'submit',
            'type' => 'Zend\Form\Element\Submit',
            'options' => array(
                'label' => $submitLabel,
            ),
            'attributes' => array(
                'value' => $submitLabel,
            )
        ));

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