<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Mapper;

use Zend\Db\Sql\Sql,
    KapitchiEntity\Entity\RevisionInterface;

class RevisionDbAdapterMapper extends EntityDbAdapterMapper implements RevisionMapperInterface
{
    protected $entityRevisionHydrator;
    protected $entityRevisionPrototype;
    
    public function __construct($dbAdapter, EntityDbAdapterMapperOptions $options, $entityRevisionPrototype, $entityRevisionHydrator) {
        parent::__construct($dbAdapter, $options);
        
        $this->setEntityRevisionPrototype($entityRevisionPrototype);
        $this->setEntityRevisionHydrator($entityRevisionHydrator);
    }
    
    public function findEntityByRevision(RevisionInterface $revision)
    {
        //TODO
        $select = $this->select();
        $select->from($this->getTableName());
        $select->where(array(
            'id' => $revision->getId(),
        ));
        
        $adapter = $this->getReadDbAdapter();
        $statement = $adapter->createStatement();
        $select->prepareStatement($adapter, $statement);
        $result = $statement->execute();
        $data = $result->current();
        if(!$data) {
            return null;
        }
        unset($data[$this->getPrimaryKey()]);
        
        $item = $this->getEntityRevisionHydrator()->hydrate($data, $this->getEntityRevisionPrototype());
        return $item;
    }
    
    public function persistEntityRevision(RevisionInterface $revision, $entity)
    {
        //TODO
        $data = $this->getEntityRevisionHydrator()->extract($entity);
        unset($data['id']);
        
        $sql = new Sql($this->getWriteDbAdapter(), $this->getTableName());
        $update = $sql->update();
        $update->set($data);
        $update->where(array($this->getPrimaryKey() => $revision->getId()));

        $statement = $sql->prepareStatementForSqlObject($update);
        $result = $statement->execute();
        return $result->getAffectedRows();
    }

    /**
     * 
     * @return \Zend\Stdlib\Hydrator\HydratorInterface
     */
    public function getEntityRevisionHydrator()
    {
        return $this->entityRevisionHydrator;
    }

    public function setEntityRevisionHydrator($entityRevisionHydrator)
    {
        $this->entityRevisionHydrator = $entityRevisionHydrator;
    }

    public function getEntityRevisionPrototype()
    {
        return clone $this->entityRevisionPrototype;
    }

    public function setEntityRevisionPrototype($entityRevisionPrototype)
    {
        $this->entityRevisionPrototype = $entityRevisionPrototype;
    }

}
