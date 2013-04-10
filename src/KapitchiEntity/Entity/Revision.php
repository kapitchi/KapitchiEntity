<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Entity;

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