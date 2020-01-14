<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\ChainTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\DataTransformerInterface;

class ChainTransformerTest extends TestCase
{
    /**
     * @var DataTransformerInterface|ObjectProphecy
     */
    private object $innerTransformer1;

    /**
     * @var DataTransformerInterface|ObjectProphecy
     */
    private object $innerTransformer2;

    private ChainTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->innerTransformer1 = $this->prophesize(DataTransformerInterface::class);
        $this->innerTransformer2 = $this->prophesize(DataTransformerInterface::class);
        $this->transformer = new ChainTransformer($this->innerTransformer1->reveal(), $this->innerTransformer2->reveal());
    }

    public function testTransformShouldCallInnerTransformersAndReturnLastTransformedValue(): void
    {
        $value = 'the value';
        $transformedValue1 = 'the first transformation';
        $transformedValue2 = 'the last transformation';

        $this->innerTransformer1->transform($value)
            ->shouldBeCalled()
            ->willReturn($transformedValue1)
        ;

        $this->innerTransformer2->transform($transformedValue1)
            ->shouldBeCalled()
            ->willReturn($transformedValue2)
        ;

        self::assertEquals($transformedValue2, $this->transformer->transform($value));
    }

    public function testReverseTransformShouldCallInnerTransformersAndReturnLastReverseTransformedValue(): void
    {
        $value = 'the value';
        $reverseTransformedValue1 = 'the first reverse transformation';
        $reverseTransformedValue2 = 'the last reverse transformation';

        $this->innerTransformer1->reverseTransform($value)
            ->shouldBeCalled()
            ->willReturn($reverseTransformedValue1)
        ;

        $this->innerTransformer2->reverseTransform($reverseTransformedValue1)
            ->shouldBeCalled()
            ->willReturn($reverseTransformedValue2)
        ;

        self::assertEquals($reverseTransformedValue2, $this->transformer->reverseTransform($value));
    }
}
