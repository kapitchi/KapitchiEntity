<?php
namespace KapitchiEntity\Model;

use KapitchiEntity\Entity\RevisionInterface;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class RevisionModel extends GenericEntityModel
{
    public function getRevision()
    {
        return $this->revision;
    }

    public function setRevision(RevisionInterface $revision)
    {
        $this->revision = $revision;
    }

}