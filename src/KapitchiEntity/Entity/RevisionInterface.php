<?php
namespace KapitchiEntity\Entity;

/**
 * 
 * @author Matus Zeman <mz@kapitchi.com>
 */
interface RevisionInterface
{
    public function getRevisionEntityId();
    public function getRevisionLog();
    public function getRevision();
    public function getRevisionCreated();
}