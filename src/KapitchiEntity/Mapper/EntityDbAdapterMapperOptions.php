<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Mapper;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class EntityDbAdapterMapperOptions extends \Zend\Stdlib\AbstractOptions
{
    protected $hydrator;
    protected $entityPrototype;
    protected $tableName;
    protected $primaryKey;
    
    public function getHydrator()
    {
        return $this->hydrator;
    }

    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    public function getEntityPrototype()
    {
        return $this->entityPrototype;
    }

    public function setEntityPrototype($entityPrototype)
    {
        $this->entityPrototype = $entityPrototype;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
    }

}