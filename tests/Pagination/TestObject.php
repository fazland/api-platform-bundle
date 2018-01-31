<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Pagination;

class TestObject
{
    public function __construct($id, $timestamp)
    {
        $this->id = $id;
        $this->timestamp = $timestamp;
    }

    public $id;
    public $timestamp;
}
