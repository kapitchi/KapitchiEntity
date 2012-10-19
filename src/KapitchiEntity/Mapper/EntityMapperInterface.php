<?php

namespace KapitchiEntity\Mapper;

/**
 * @author Matus Zeman <mz@kapitchi.com>
 */
interface EntityMapperInterface
{
    public function persist($entity);

    public function remove($entity);

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param int $id The identifier.
     * @return object The object.
     */
    public function find($id);

    /**
     * @param array $criteria
     * @param array $orderBy
     * @return \Zend\Paginator\Adapter\AdapterInterface
     */
    public function getPaginatorAdapter(array $criteria, array $orderBy = null);
}