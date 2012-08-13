<?php
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