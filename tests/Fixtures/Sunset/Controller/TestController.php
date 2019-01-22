<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Sunset\Controller;

use Fazland\ApiPlatformBundle\Annotation\Sunset;
use Fazland\ApiPlatformBundle\Annotation\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestController extends Controller
{
    /**
     * @return mixed
     *
     * @View()
     * @Sunset("2019-03-01")
     */
    public function indexAction(): array
    {
        return [
            'test_foo' => 'foo.test',
        ];
    }
}
