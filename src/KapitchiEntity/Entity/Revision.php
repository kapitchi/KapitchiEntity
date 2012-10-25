<?php
namespace KapitchiEntity\Entity;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class Revision implements RevisionInterface
{
    protected $id;
    protected $revisionEntityId;
    protected $revision;
    protected $revisionLog;
    protected $revisionCreated;
    protected $revisionOwnerId;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getRevisionEntityId()
    {
        return $this->revisionEntityId;
    }

    public function setRevisionEntityId($revisionEntityId)
    {
        $this->revisionEntityId = $revisionEntityId;
    }

    public function getRevision()
    {
        return $this->revision;
    }

    public function setRevision($revision)
    {
        $this->revision = $revision;
    }

    public function getRevisionLog()
    {
        return $this->revisionLog;
    }

    public function setRevisionLog($revisionLog)
    {
        $this->revisionLog = $revisionLog;
    }

    public function getRevisionCreated()
    {
        return $this->revisionCreated;
    }

    public function setRevisionCreated($revisionCreated)
    {
        $this->revisionCreated = $revisionCreated;
    }
    
    public function getRevisionOwnerId()
    {
        return $this->revisionOwnerId;
    }

    public function setRevisionOwnerId($revisionOwnerId)
    {
        $this->revisionOwnerId = $revisionOwnerId;
    }

}