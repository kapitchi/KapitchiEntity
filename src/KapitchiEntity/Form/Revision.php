<?php
namespace KapitchiEntity\Form;

use KapitchiBase\Form\EventManagerAwareForm;

/**
 *
 * @author Matus Zeman <mz@kapitchi.com>
 */
class Revision extends EventManagerAwareForm
{
    public function __construct()
    {
        parent::__construct('revision');
        
        $this->add(array(
            'name' => 'revisionLog',
            'options' => array(
                'label' => 'Poznamka ku zmene',
            ),
            'attributes' => array(
                'type' => 'textarea',
            ),
        ));
    }
}