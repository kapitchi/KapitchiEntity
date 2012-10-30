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

        $resultSet = new \Zend\Db\ResultSet\HydratingResultSet();
        $resultSet->setHydrator($this->getHydrator());
        $resultSet->setObjectPrototype($this->getEntityPrototype());
        $resultSet->initialize($result);

        return $resultSet;
    }

    public function persist($object)
    {
//        $className = $this->getOptions()->getClassName();
//        if (!$object instanceof $className) {
//            throw new Exception\InvalidArgumentException(
//                '$entity must be an instance of ' . $className
//            );
//        }
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
//        $className = $this->getOptions()->getClassName();
//        if (!$object instanceof $className) {
//            throw new Exception\InvalidArgumentException(
//                '$entity must be an instance of ' . $className
//            );
//        }
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
        $pk = $this->getPrimaryKey();
        $hydrator = $this->getHydrator();
        $set = $hydrator->extract($entity);
        
        $pkField = $this->getFieldForProperty($pk);
        $pkValue = $set[$pkField];
        unset($set[$pkField]);

        $sql = new Sql($this->getWriteDbAdapter(), $this->getTableName());
        $update = $sql->update();
        $update->set($set);
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
    
    protected function createPaginatorAdapter($select, $parametersOrQueryMode = null)
    {
        //TODO Until DbAdapter adapter workst we use array
        $ret = $this->selectWith($select, $parametersOrQueryMode);
        $arr = array();
        foreach ($ret as $item) {
            $arr[] = $item;
        }
        return new \Zend\Paginator\Adapter\ArrayAdapter($arr);
        //END
        
        //return new \Zend\Paginator\Adapter\DbSelect($select);
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
