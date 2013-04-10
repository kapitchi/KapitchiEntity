<?php
/**
 * Kapitchi Zend Framework 2 Modules (http://kapitchi.com/)
 *
 * @copyright Copyright (c) 2012-2013 Kapitchi Open Source Team (http://kapitchi.com/open-source-team)
 * @license   http://opensource.org/licenses/LGPL-3.0 LGPL 3.0
 */

namespace KapitchiEntity;

use Zend\ModuleManager\Feature\ServiceProviderInterface,
    KapitchiBase\ModuleManager\AbstractModule;

class Module extends AbstractModule implements ServiceProviderInterface
{

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'KapitchiEntity\Entity\Revision' => 'KapitchiEntity\Entity\Revision',
                'KapitchiEntity\Entity\RevisionInputFilter' => 'KapitchiEntity\Entity\RevisionInputFilter',
                'KapitchiEntity\Form\Revision' => 'KapitchiEntity\Form\Revision',
            ),
            'factories' => array(
                'KapitchiEntity\Entity\RevisionHydrator' => function($sm) {
                    return new Entity\RevisionHydrator(false);
                }
            )
        );
    }
    
    public function getDir() {
        return __DIR__;
    }

    public function getNamespace() {
        return __NAMESPACE__;
    }

}