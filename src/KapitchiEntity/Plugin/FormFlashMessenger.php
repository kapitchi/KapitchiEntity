<?php
namespace KapitchiEntity\Plugin;

use Zend\EventManager\EventInterface,
    KapitchiApp\PluginManager\PluginInterface;

/**
 * @todo This needs to be rewritten to use ControllerPluginManager directly
 * and do not rely on controller?
 * @author Matus Zeman <mz@kapitchi.com>
 */
class FormFlashMessenger implements PluginInterface
{
    
    public function getAuthor()
    {
        return 'Matus Zeman';
    }

    public function getDescription()
    {
        return 'TODO';
    }

    public function getName()
    {
        return '[KapitchiContact] Form flash messenger error messages';
    }

    public function getVersion()
    {
        return '0.1';
    }
    
    public function onBootstrap(EventInterface $e)
    {
        $em = $e->getApplication()->getEventManager();
        $sm = $e->getApplication()->getServiceManager();
        $sharedEm = $em->getSharedManager();
        
        $instance = $this;
        
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'update.post', array($this, 'createMessages'));
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'create.post', array($this, 'createMessages'));
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'create.persist.post', array($this, 'createPersistMessage'), 1000);
    }
    
    public function createMessages($e) {
        $form = $e->getParam('form');
        $viewModel = $e->getParam('viewModel');
        $cont = $e->getTarget();

        $inputFilter = $form->getInputFilter();
        $msgs = $inputFilter->getMessages();
        if(!empty($msgs)) {
            $fm = $cont->plugin('flashMessenger')->setNamespace('FormFlashMessenger');
            $fm->addMessage(array(
                'message' => "Form validation error",
                'inputFilterMessages' => $msgs
            ));
        }
    }
    
    public function createPersistMessage($e) {
        $cont = $e->getTarget();
        $fm = $cont->plugin('flashMessenger')->setNamespace('FormFlashMessenger');
        $fm->addMessage(array(
            'message' => "Form submitted",
        ));
    }
    
}