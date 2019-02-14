<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\NullableMappingTransformer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\DataTransformerInterface;

class NullableMappingTransformerTest extends TestCase
{
    /**
     * @var DataTransformerInterface|ObjectProphecy
     */
    private $innerTransformer;

    /**
     * @var NullableMappingTransformer
     */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->innerTransformer = $this->prophesize(DataTransformerInterface::class);
        $this->transformer = new NullableMappingTransformer($this->innerTransformer->reveal());
    }

    public function testTransformShouldReturnNullOnNull(): void
    {
        $this->innerTransformer->transform(Argument::any())->shouldNotBeCalled();

        self::assertNull($this->transformer->transform(null));
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

    public function testReverseTransformShouldReturnNullOnNull(): void
    {
        $this->innerTransformer->reverseTransform(Argument::any())->shouldNotBeCalled();

        self::assertNull($this->transformer->reverseTransform(null));
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
