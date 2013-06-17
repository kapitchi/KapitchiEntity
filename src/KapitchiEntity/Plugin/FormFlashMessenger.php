<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Plugin;

use Zend\EventManager\EventInterface;
use KapitchiApp\PluginManager\PluginInterface;
use Zend\Mvc\Controller\Plugin\FlashMessenger;

/**
 * @todo This needs to be rewritten to use ControllerPluginManager directly
 * and do not rely on controller?
 * @author Matus Zeman <mz@kapitchi.com>
 */
class FormFlashMessenger implements PluginInterface
{
    protected $namespace = 'userMessages';
    protected $eventId = 'KapitchiEntity\Controller\EntityController';
    
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
        
        $sharedEm->attach($this->eventId, 'update.persist', array($this, 'createMessages'), -10);
        $sharedEm->attach($this->eventId, 'create.persist', array($this, 'createMessages'), -10);
        $sharedEm->attach($this->eventId, 'remove.post', function($e) {
            $cont = $e->getTarget();
            $fm = $cont->plugin('flashMessenger')->setNamespace(FlashMessenger::NAMESPACE_INFO);
            $fm->addMessage('Item deleted');
        });
    }
    
    public function createMessages($e) {
        $form = $e->getParam('form');
        $cont = $e->getTarget();

        $inputFilter = $form->getInputFilter();
        $msgs = $inputFilter->getMessages();
        if(!empty($msgs)) {
            $fm = $cont->plugin('flashMessenger')->setNamespace(FlashMessenger::NAMESPACE_ERROR);
            $fm->addMessage(array(
                'message' => "Form validation error",
                'sticky' => true,
                'inputFilterMessages' => $msgs
            ));
        }
        else {
            $fm = $cont->plugin('flashMessenger')->setNamespace(FlashMessenger::NAMESPACE_SUCCESS);
            $fm->addMessage("Item saved");
        }
    }
    
}