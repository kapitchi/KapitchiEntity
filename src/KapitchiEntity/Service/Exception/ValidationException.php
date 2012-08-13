<?php

namespace KapitchiEntity\Service\Exception;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class ValidationException extends \InvalidArgumentException 
    implements ExceptionInterface
{
    protected $inputFilter;
    protected $entity;
    
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    public function setInputFilter($inputFilter)
    {
        $this->inputFilter = $inputFilter;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
    
}