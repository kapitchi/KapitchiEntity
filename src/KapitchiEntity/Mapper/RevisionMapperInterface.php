<?php

namespace KapitchiEntity\Mapper;

use KapitchiEntity\Entity\RevisionInterface;

/**
 * @author Matus Zeman <mz@kapitchi.com>
 */
interface RevisionMapperInterface extends EntityMapperInterface
{
    public function findEntityByRevision(RevisionInterface $revision);
    public function persistEntityRevision(RevisionInterface $revision, $entity);
}