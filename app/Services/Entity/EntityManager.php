<?php

namespace App\Services\Entity;

interface EntityManager
{
    public function save($entity, $data);
    public function findAll($entity, $options = null);
    public function findOne($entity, $options = null);
}