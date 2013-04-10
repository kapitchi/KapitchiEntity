<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

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
        
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'update.persist', array($this, 'createMessages'), -1000);
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'create.persist', array($this, 'createMessages'), -1000);
        $sharedEm->attach('KapitchiEntity\Controller\AbstractEntityController', 'remove.post', function($e) {
            $cont = $e->getTarget();
            $fm = $cont->plugin('flashMessenger')->setNamespace('FormFlashMessenger');
            $fm->addMessage(array(
                'message' => "Item deleted",
            ));
        });
    }
    
    public function createMessages($e) {
        $form = $e->getParam('form');
        $cont = $e->getTarget();

        $inputFilter = $form->getInputFilter();
        $msgs = $inputFilter->getMessages();
        $fm = $cont->plugin('flashMessenger')->setNamespace('FormFlashMessenger');
        if(!empty($msgs)) {
            $fm->addMessage(array(
                'message' => "Form validation error",
                'sticky' => true,
                'theme' => 'warning',
                'inputFilterMessages' => $msgs
            ));
        }
        else {
            $fm->addMessage(array(
                'message' => "Item saved",
            ));
        }
    }
    
}