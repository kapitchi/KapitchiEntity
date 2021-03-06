<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Service;

use Zend\EventManager\EventManagerInterface,
    KapitchiEntity\Entity\RevisionInterface,
    KapitchiEntity\Model\EntityModelInterface;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class RevisionService extends EntityService
{
    protected $entityService;
    
    public function __construct($mapper, $entityService)
    {
        //TODO
        $entityPrototype = new \KapitchiEntity\Entity\Revision();
        $hydrator = new \KapitchiEntity\Entity\RevisionHydrator(false);
        //END
        
        parent::__construct($mapper, $entityPrototype, $hydrator);
        
        $this->setEntityService($entityService);
    }
    
    public function createEntityRevision($entity)
    {
        //TODO - how do we know what entity id is? EntityInterface???
        $entityId = $entity->getId();
        
        //increase rev number
        $revisionNumber = 0;
        $latest = $this->findLatestByEntityId($entityId);
        if($latest) {
            $revisionNumber = $latest->getRevision();
        }
        $revisionNumber++;
        
        $revision = $this->createEntityFromArray(array(
            'revision' => $revisionNumber,
            'revisionEntityId' => $entityId,
            'revisionCreated' => new \DateTime(),
            'revisionLog' => ''
        ));
        $this->persist($revision);
        
        $this->getMapper()->persistEntityRevision($revision, $entity);
        
        return $revision;
    }
    
    public function rollbackToRevision(RevisionInterface $revision)
    {
        $model = $this->loadModel($revision);
        throw new \Exception("n/i");
    }
    
    public function loadModel($revision, $options = array(), EntityModelInterface $model = null)
    {
        if(!$revision instanceof RevisionInterface) {
            throw new \Exception("Not RevisionInterface instance");
        }
        
        $entityId = $revision->getRevisionEntityId();
        $entity = $this->getEntityService()->find($entityId);
        if(!$entity) {
            throw new \Exception("Can't find an entity [id: $entityId]");
        }
        
        $mapper = $this->getMapper();
        $revisionEntity = $mapper->findEntityByRevision($revision);
        $this->mergeEntities($entity, $revisionEntity);
        
        $model = new \KapitchiEntity\Model\RevisionModel();
        $model->setEntity($entity);
        $model->setRevision($revision);
        
        parent::loadModel($entity, $options, $model);
        
        return $model;
    }
    
    protected function mergeEntities($entity, $revisionEntity) {
        $hydrator = $this->getEntityService()->getHydrator();
        $hydrator->hydrate($hydrator->extract($revisionEntity), $entity);
        return $entity;
    }
    
    public function findLatestByEntityId($entityId) {
        $paginator = $this->getPaginator(array(
                'revisionEntityId' => $entityId,
            ),
            array('revisionCreated DESC')
        );
        $items = $paginator->getAdapter()->getItems(0, 1);
        $entityRevision = current($items);
        return $entityRevision;
    }
    
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array('KapitchiEntity\Service\EntityService', __CLASS__, get_called_class()));
        $this->eventManager = $events;
        $this->attachDefaultListeners();
        return $this;
    }
    
    /**
     * 
     * @return EntityService
     */
    public function getEntityService()
    {
        return $this->entityService;
    }

    public function setEntityService(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }
}