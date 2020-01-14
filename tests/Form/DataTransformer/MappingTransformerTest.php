<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\MappingTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\DataTransformerInterface;

class MappingTransformerTest extends TestCase
{
    /**
     * @var DataTransformerInterface|ObjectProphecy
     */
    private object $innerTransformer;

    private MappingTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->innerTransformer = $this->prophesize(DataTransformerInterface::class);
        $this->transformer = new MappingTransformer($this->innerTransformer->reveal());
    }

    public function provideEmptyValues(): iterable
    {
        yield [null];
        yield [''];
        yield [[]];
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testTransformShouldReturnEmptyArrayOnEmptyValues($value): void
    {
        $this->innerTransformer->transform(Argument::any())->shouldNotBeCalled();

        self::assertEquals([], $this->transformer->transform($value));
    }

    public function provideElements(): iterable
    {
        yield [['we', 'are', 'the', 'elements', 123, [], new \stdClass()]];
    }

    /**
     * @dataProvider provideElements
     */
    public function testTransformShouldCallInnerTransformForEachElement(array $elements): void
    {
        foreach ($elements as $element) {
            $this->innerTransformer->transform($element)->shouldBeCalled();
        }

        $this->transformer->transform($elements);
    }

    /**
     * @dataProvider provideEmptyValues
     */
    public function testReverseTransformShouldReturnEmptyArrayOnEmptyValues($value): void
    {
        $this->innerTransformer->reverseTransform(Argument::any())->shouldNotBeCalled();

        self::assertEquals([], $this->transformer->reverseTransform($value));
    }

    /**
     * @dataProvider provideElements
     */
    public function testReverseTransformShouldCallInnerTransformForEachElement(array $elements): void
    {
        foreach ($elements as $element) {
            $this->innerTransformer->reverseTransform($element)->shouldBeCalled();
        }

        $this->transformer->reverseTransform($elements);
    }
}
