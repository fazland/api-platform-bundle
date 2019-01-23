<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\Extension;

use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\Tests\Extension\HttpFoundation\HttpFoundationRequestHandlerTest;
use Symfony\Component\HttpFoundation\Request;

class AutoSubmitRequestHandlerTest extends HttpFoundationRequestHandlerTest
{
    /**
     * {@inheritdoc}
     */
    protected function getRequestHandler(): RequestHandlerInterface
    {
        return new AutoSubmitRequestHandler($this->serverParams);
    }

    /**
     * @dataProvider methodProvider
     */
    public function testDoNotSubmitFormWithEmptyNameIfNoFieldInRequest($method): void
    {
        $form = $this->getMockForm('', $method);
        $form->expects(self::any())
            ->method('all')
            ->will(self::returnValue([
                'param1' => $this->getMockForm('param1'),
                'param2' => $this->getMockForm('param2'),
            ]))
        ;

        $this->setRequestData($method, ['paramx' => 'submitted value']);

        $form->expects(self::once())
            ->method('submit')
            ->with(['paramx' => 'submitted value'], Request::METHOD_PATCH !== $method)
        ;

        $this->requestHandler->handleRequest($form, $this->request);
    }
}
