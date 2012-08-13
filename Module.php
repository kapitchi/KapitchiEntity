<?php

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