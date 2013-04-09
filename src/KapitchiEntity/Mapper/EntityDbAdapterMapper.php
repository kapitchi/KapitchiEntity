<?php

namespace KapitchiEntity\Mapper;

use ReflectionProperty,
    Zend\Db\Sql\Select,
    Zend\Db\Sql\Sql,
    KapitchiBase\Mapper\DbAdapterMapper;

class EntityDbAdapterMapper extends DbAdapterMapper implements EntityMapperInterface
{
    /**
     * @var EntityDbAdapterMapperOptions
     */
    protected $options;
    protected $resultSetPrototype;

    public function __construct($dbAdapter, EntityDbAdapterMapperOptions $options) {
        parent::__construct($dbAdapter);
        $this->setOptions($options);
    }
            
    /**
     * @return Select
     */
    public function select()
    {
        $select = new Select();
        return $select;
    }
    
    /**
     * @param Select $select
     * @return HydratingResultSet
     */
    public function selectWith($select, $parametersOrQueryMode = null)
    {
        $adapter = $this->getReadDbAdapter();
        
        if($select instanceof Select) {
            $statement = $adapter->createStatement();
            $select->prepareStatement($adapter, $statement);
            $result = $statement->execute();
        } else {
            $result = $adapter->query($select, $parametersOrQueryMode);
        }

        $resultSet = clone $this->getResultSetPrototype();
        $resultSet->initialize($result);

        return $resultSet;
    }
    
    /**
     * @return \Zend\Db\ResultSet\ResultSetInterface
     */
    protected function getResultSetPrototype()
    {
        if($this->resultSetPrototype === null) {
            $resultSet = new \Zend\Db\ResultSet\HydratingResultSet();
            $resultSet->setHydrator($this->getHydrator());
            $resultSet->setObjectPrototype($this->getEntityPrototype());
            
            $this->resultSetPrototype = $resultSet;
        }
        
        return $this->resultSetPrototype;
    }

    public function persist($object)
    {
        if ($this->getIdentifier($object)) {
            $this->update($object);
        } else {
            $this->insert($object);
        }
    }

    /**
     * check if object has already an identifier
     *
     * @param object $object
     * @return mixed
     */
    protected function getIdentifier($object)
    {
        $property = new ReflectionProperty(get_class($object), $this->getPrimaryKey());
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function find($id)
    {
        $select = $this->select();
        $select->from($this->getTableName());
        $pkField = $this->getPrimaryKey();
        $select->where(array($pkField => $id));

        $results = $this->selectWith($select);
        $row = null;
        if ($results) {
            $row = $results->current();
        }
        return $row;
    }

    /**
     * @param object $object
     * @return bool
     */
    public function remove($object)
    {
        $value = $this->getIdentifier($object);
        if ($value) {
            $sql = new Sql($this->getReadDbAdapter(), $this->getTableName());
            $delete = $sql->delete();
            $pkField = $this->getFieldForProperty($this->getPrimaryKey());
            $delete->where(array($pkField => $value));
            $statement = $sql->prepareStatementForSqlObject($delete);
            $statement->execute();
            return true;
        }
        return false;
    }

    protected function insert($entity)
    {
        $hydrator = $this->getHydrator();
        $set = $hydrator->extract($entity);
        $pkField = $this->getFieldForProperty($this->getPrimaryKey());
        unset($set[$pkField]);

        $sql = new Sql($this->getWriteDbAdapter(), $this->getTableName());
        $insert = $sql->insert();
        $insert->values($set);

        $statement = $sql->prepareStatementForSqlObject($insert);
        $result = $statement->execute();
        $lastInsertValue = $this->getWriteDbAdapter()->getDriver()->getConnection()->getLastGeneratedValue();

        $property = new ReflectionProperty(get_class($entity), $this->getPrimaryKey());
        $property->setAccessible(true);
        $property->setValue($entity, $lastInsertValue);
    }

    protected function update($entity)
    {
        $hydrator = $this->getHydrator();
        $data = $hydrator->extract($entity);
        return $this->updateArray($data);
    }
    
    protected function updateArray(array $data)
    {
        $pk = $this->getPrimaryKey();
        $pkField = $this->getFieldForProperty($pk);
        $pkValue = $data[$pkField];
        unset($data[$pkField]);
        
        $sql = new Sql($this->getWriteDbAdapter(), $this->getTableName());
        $update = $sql->update();
        $update->set($data);
        $update->where(array($pkField => $pkValue));

        $statement = $sql->prepareStatementForSqlObject($update);
        $result = $statement->execute();
        return $result->getAffectedRows();
    }

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return \Zend\Paginator\Adapter\AdapterInterface
     */
    public function getPaginatorAdapter(array $criteria = null, array $orderBy = null)
    {
        $select = $this->select();
        $select->from($this->getTableName());
        $this->initPaginatorSelect($select, $criteria, $orderBy);
        return $this->createPaginatorAdapter($select);
    }
    
    protected function initPaginatorSelect(Select $select, array $criteria = null, array $orderBy = null)
    {
        if(is_array($criteria)) {
            $mappedCriteria = array();
            foreach ($criteria as $property => $value) {
                $field = $this->getFieldForProperty($property);
                $mappedCriteria[$field] = $value;
            }
            $select->where($mappedCriteria);
        }
        
        if($orderBy !== null) {
            $select->order($orderBy);
        }
        
    }
    
    protected function createPaginatorAdapter(Select $select, $parametersOrQueryMode = null)
    {
        return new \Zend\Paginator\Adapter\DbSelect($select, $this->getReadDbAdapter(), clone $this->getResultSetPrototype());
    }
    
    protected function getFieldForProperty($prop) {
        //TODO
        return $prop;
    }

    /**
     * @return MappingHydrator
     */
    public function getHydrator()
    {
        return $this->getOptions()->getHydrator();
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->getOptions()->getPrimaryKey();
    }
    
    public function getEntityPrototype()
    {
        return $this->getOptions()->getEntityPrototype();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getOptions()->getTableName();
    }

    /**
     * @param DefaultObjectManagerOptions $options
     */
    public function setOptions(EntityDbAdapterMapperOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @return DefaultObjectManagerOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

}
