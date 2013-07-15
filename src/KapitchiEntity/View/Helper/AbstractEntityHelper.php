<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\View\Helper;

class AbstractEntityHelper extends \Zend\View\Helper\AbstractHelper
{
    protected $entityService;
    protected $entity;
    protected $eventManager;

    public function __construct($entityService)
    {
        $this->setEntityService($entityService);
    }
    
    public function __invoke($entity = null)
    {
        if($entity !== null) {
            $this->setEntity($entity);
        }
        
        return $this;
    }
    
    public function getFieldValue($field)
    {
        return $this->getEntityService()->getFieldValues($this->getEntity(), $field);
    }
    
    public function renderFieldValue($field)
    {
        $value = $this->getFieldValue($field);
        return empty($value) ? $this->getView()->translate('N/A') : $value;
    }
    
    public function find($id)
    {
        return $this->getEntityService()->find($id);
    }
    
    public function get($id)
    {
        return $this->getEntityService()->get($id);
    }
    
    public function toArray($entity, array $data = null)
    {
        $ret = $this->getEntityService()->createArrayFromEntity($entity);
        if($data !== null) {
            $ret = array_merge($ret, $data);
        }
        
        return $ret;
    }
    
    public function getPaginator($criteria = null, $orderBy = null)
    {
        return $this->getEntityService()->getPaginator($criteria, $orderBy);
    }
    
    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    
    protected function fetchEntity($entity = null)
    {
        if(is_object($entity)) {
            return $entity;
        }
        
        if(empty($entity)) {
            return $this->getEntity();
        }
        
        return $this->getEntityService()->get($entity);
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