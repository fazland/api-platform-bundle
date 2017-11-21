<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures;

use Kcs\ApiPlatformBundle\HttpKernel\View\Context;

class TestObject
{
    public function testGroupProvider(Context $context)
    {
        return ['foobar'];
    }
}
