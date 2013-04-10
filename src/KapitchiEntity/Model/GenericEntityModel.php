<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity\Model;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class GenericEntityModel implements EntityModelInterface
{
    protected $entity;
    protected $exts = array();
    
    public function __construct($entity = null)
    {
        $this->setEntity($entity);
    }
            
    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    
    public function getExts()
    {
        return $this->exts;
    }
    
    public function setExts(array $loadedExts)
    {
        $this->exts = $loadedExts;
    }
    
    public function setExt($handle, $ext) {
        if($this->hasExt($handle)) {
            throw new \Exception("Extension '$handle' set already - possible confict?");
        }
        $this->exts[$handle] = $ext;
    }
    
    public function getExt($handle) {
        if(!$this->hasExt($handle)) {
            throw new \Exception("No extension '$handle'");
        }
        return $this->exts[$handle];
    }
    
    public function hasExt($handle) {
        return array_key_exists($handle, $this->exts);
    }
    
}