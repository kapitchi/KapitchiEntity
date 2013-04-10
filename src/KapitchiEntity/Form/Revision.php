<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

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