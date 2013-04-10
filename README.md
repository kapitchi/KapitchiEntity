Zend Framework 2 - Kapitchi Entity module
=================================================

__Version:__ 0.1-dev  
__Author:__  [Kapitchi Team](http://kapitchi.com/team)  
__Website:__ [http://kapitchi.com](http://kapitchi.com)  
__Demo:__    [http://kapitchi.com/showcase](http://kapitchi.com/showcase)  

__README.md status:__ INCOMPLETE  

Licence
=======

TODO


Introduction
============

Set of common classes/interfaces to provide generic entity API and event triggering - services, mappers, controllers and helpers to work and manage entities.
Entity is defined as POPO implementing one method getId() and having empty constructor.

Installation
============

TODO

Basic Usage
===========

Find entity/entities
---------------------

Find by entity ID
```
    $entity = $service->find(1); //returns entity object or null if not found
    //OR
    $entity = $service->get(1); //throws exception if not found
```

Get entity paginator by multiple criteria
```
    $paginator = $service->getPaginator(array(
        'displayName' => 'kapitchi'
    ), array(
        'created' => 'ASC' //order by created ascending
    ));
    
    $entities = $paginator->getCurrentItems();
```

Persist entity
--------------

Persist entity object. Note that new entity is inserted when getId() is not 'empty' value otherwise a mapper performs update

```
    $entity = $service->createEntityFromArray(array( //creates entity from array
        'displayName' => 'kapitchi'
    ));
    $persistEvent = $service->persist($entity);
    //$entity->getId() is populated now
```

Persist entity from array
```
    $persistEvent = $service->persist(array(
        'displayName' => 'kapitchi'
    ));
```

Remove entity
-------------

```
$service->remove($entity);
```

Events
======

Example presumes using MyModule\Service\MyEntity service class name.

$service->persist($entity)
--------------------------
Event: MyModule\Service\MyEntity:persist
Params:
 - entity - entity object
 - data - when entity is persisted from array
 - origEntity - when persisting/updating an existing entity we load from mapper original entity first

TODO

Service manager configuration
=============================

Entity service with Zend\Db mapper
----------------------------------

```
    'MyModule\Service\MyEntity' => function ($sm) {
        $s = new Service\MyEntity(
            $sm->get('MyModule\Mapper\MyEntity'),
            $sm->get('MyModule\Entity\MyEntity'),
            $sm->get('MyModule\Entity\MyEntityHydrator')
        );
        return $s;
    },
    'MyModule\Mapper\MyEntityDbAdapter' => function ($sm) {
        return new \KapitchiEntity\Mapper\MyEntityDbAdapter(
            $sm->get('Zend\Db\Adapter\Adapter'),
            new EntityDbAdapterMapperOptions(array(
                'tableName' => 'identity_auth_credential',
                'primaryKey' => 'id',
                'hydrator' => $sm->get('MyModule\Entity\MyEntityHydrator'),
                'entityPrototype' => $sm->get('MyModule\Entity\MyEntity'),
            ))
        );
    },
    'MyModule\Entity\MyEntityHydrator' => function ($sm) {
        //needed here because hydrator tranforms camelcase to underscore
        return new \Zend\Stdlib\Hydrator\ClassMethods(false);
    },

```

Entity service with Doctrine mapper
-----------------------------------

TODO
