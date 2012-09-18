<?php

namespace KapitchiEntity\View\Helper;

class AbstractEntityHelper extends \Zend\View\Helper\AbstractHelper
{
    protected $entityService;
    
    public function __construct($entityService)
    {
        $this->setEntityService($entityService);
    }
    
    public function find($id)
    {
        return $this->getEntityService()->find($id);
    }
    
    public function getPaginator($criteria = null, $orderBy = null)
    {
        return $this->getEntityService()->getPaginator($criteria, $orderBy);
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