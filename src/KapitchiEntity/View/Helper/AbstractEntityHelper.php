<?php

namespace KapitchiEntity\View\Helper;

class AbstractEntityHelper extends \Zend\View\Helper\AbstractHelper
{
    protected $entityService;
    
    public function find($id)
    {
        return $this->getEntityService()->find($id);
    }
    
    /**
     * 
     * @return \KapitchiEntity\Service\EntityService
     */
    public function getEntityService()
    {
        return $this->entityService;
    }

    public function setEntityService($entityService)
    {
        $this->entityService = $entityService;
    }

}