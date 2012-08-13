<?php
namespace KapitchiEntity\Model;

/**
 * 
 * @author Matus Zeman <mz@kapitchi.com>
 */
interface EntityModelInterface {
    public function getEntity();
    public function getExts();
    public function getExt($handle);
}