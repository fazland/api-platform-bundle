<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures;

use Fazland\ApiPlatformBundle\HttpKernel\View\Context;

class TestObject
{
    public function testGroupProvider(Context $context)
    {
        return ['foobar'];
    }
}
