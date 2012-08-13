<?php
namespace KapitchiEntity\Entity;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class RevisionInputFilter extends \KapitchiBase\InputFilter\EventManagerAwareInputFilter
{
    public function __construct()
    {
        $this->add(array(
            'name'       => 'revisionLog',
            'required'   => false,
        ));
    }
}