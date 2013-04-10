<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Helper;

use Zend\Mvc\Controller\Plugin\PluginInterface,
    Zend\View\Helper\Navigation\HelperInterface;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
abstract class AbstractEntityHelper implements PluginInterface, HelperInterface
{
    protected $controller;
    protected $view;
    
    abstract public function getUpdateUrl($entity);
    abstract public function getIndexUrl();
        
    public function __construct($controller, $view)
    {
        $this->setController($controller);
        $this->setView($view);
    }
    
    public function getController()
    {
        return $this->controller;
    }
    
    public function getView()
    {
        return $this->view;
    }

    public function setController(Dispatchable $controller)
    {
        $this->controller = $controller;
    }

    public function setView(Renderer $view)
    {
        $this->view = $view;
    }
}