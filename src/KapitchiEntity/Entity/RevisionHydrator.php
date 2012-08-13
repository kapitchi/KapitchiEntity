<?php
namespace KapitchiEntity\Entity;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class RevisionHydrator extends \Zend\Stdlib\Hydrator\ClassMethods
{
    public function extract($object) {
        $data = parent::extract($object);
        if($data['revisionCreated'] instanceof \DateTime) {
            $data['revisionCreated'] = $data['revisionCreated']->format('Y-m-d\TH:i:sP');//UTC
        }
        
        return $data;
    }

    public function hydrate(array $data, $object) {
        if(!$data['revisionCreated'] instanceof \DateTime) {
            //UTC
            $data['revisionCreated'] = new \DateTime($data['revisionCreated']);
        }
        return parent::hydrate($data, $object);
    }
}