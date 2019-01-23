<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\DependencyInjection\CompilerPass;

use Fazland\ApiPlatformBundle\DependencyInjection\CompilerPass\OverrideDefaultRequestHandlerPass;
use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OverrideDefaultRequestHandlerPassTest extends TestCase
{
    /**
     * @var ContainerBuilder|ObjectProphecy
     */
    private $container;

    /**
     * @var OverrideDefaultRequestHandlerPass
     */
    private $compilerPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->compilerPass = new OverrideDefaultRequestHandlerPass();
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessShouldNotActIfAutoSubmitRequestHandlerIsDisabled(): void
    {
        $this->container->getParameter('fazland_api.auto_submit_request_handler_is_enabled')->willReturn(false);
        $this->container->findDefinition('form.type_extension.form.request_handler')->shouldNotBeCalled();

        $this->compilerPass->process($this->container->reveal());
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessShouldOverrideDefaultRequestHandlerWithAutoSubmitRequestHandler(): void
    {
        $definition = $this->prophesize(Definition::class);

        $this->container->getParameter('fazland_api.auto_submit_request_handler_is_enabled')->willReturn(true);
        $this->container->findDefinition('form.type_extension.form.request_handler')->willReturn($definition);
        $definition->setClass(AutoSubmitRequestHandler::class)->shouldBeCalled();

        $this->compilerPass->process($this->container->reveal());
    }
}
