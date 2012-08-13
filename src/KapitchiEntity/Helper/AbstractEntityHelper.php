<?php
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