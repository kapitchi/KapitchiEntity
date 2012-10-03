<?php
namespace KapitchiEntity\Plugin;

use Zend\EventManager\EventInterface,
    KapitchiApp\PluginManager\PluginInterface;

/**
 *
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
    
}