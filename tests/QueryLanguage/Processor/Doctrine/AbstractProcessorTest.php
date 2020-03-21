<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Processor\Doctrine;

use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\QueryType;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\AbstractProcessor;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\OrderWalker;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class AbstractProcessorTest extends TestCase
{
    /**
     * @var ObjectProphecy|FormFactoryInterface
     */
    private ObjectProphecy $formFactory;

    protected function setUp(): void
    {
        $this->formFactory = $this->prophesize(FormFactoryInterface::class);
    }

    public function testCustomOrderValidationWalker(): void
    {
        $processor = new ConcreteProcessor($this->formFactory->reveal(), [
            'order_field' => 'order',
            'order_validation_walker' => $orderWalker = new OrderWalker(['test', 'foo']),
        ]);

        $this->formFactory->createNamed('', QueryType::class, Argument::type(Query::class), Argument::withEntry(
            'order_validation_walker', $orderWalker
        ))
            ->shouldBeCalled()
            ->willReturn($form = $this->prophesize(FormInterface::class));

        $form->handleRequest(Argument::any())->willReturn();
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(true);

        $processor->handleRequest(new Request([
            'order' => '$order(test, asc)',
        ]));
    }
}

class ConcreteProcessor extends AbstractProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createColumn(string $fieldName): ColumnInterface
    {
        return new DummyColumn();
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifierFieldNames(): array
    {
        return ['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request)
    {
        return parent::handleRequest($request);
    }
}
