<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

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