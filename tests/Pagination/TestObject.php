<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Pagination;

class TestObject
{
    /**
     * @var mixed
     */
    public $id;

    /**
     * @var mixed
     */
    public $timestamp;

    public function __construct($id, $timestamp)
    {
        $this->id = $id;
        $this->timestamp = $timestamp;
    }
}
