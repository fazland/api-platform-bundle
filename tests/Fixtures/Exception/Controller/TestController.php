<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\Exception\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class TestController extends Controller
{
    public function indexAction(Request $request)
    {
    }
}
