<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\Base64ToUploadedFileTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;

class Base64ToUploadedFileTransformerTest extends TestCase
{
    private const TEST_GIF_DATA = 'data:image/gif;base64,R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs=';
    private const TEST_TXT_DATA = 'data:text/plain,K%C3%A9vin%20Dunglas%0A';
    private const TEST_TXT_CONTENT = "Kévin Dunglas\n";

    private Base64ToUploadedFileTransformer $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->transformer = new Base64ToUploadedFileTransformer();
    }

    public function testReverseTransformShouldReturnNullOnNullValues(): void
    {
        self::assertNull($this->transformer->reverseTransform(null));
    }

    public function testReverseTransformShouldNotTouchFileObjects(): void
    {
        $file = new File(__FILE__);

        self::assertSame($file, $this->transformer->reverseTransform($file));
    }

    public function provideNonString(): iterable
    {
        yield [0.23];
        yield [47];
        yield [['foobar']];
        yield [new \stdClass()];
    }

    /**
     * @dataProvider provideNonString
     */
    public function testReverseTransformShouldThrowOnNonStringValues($value): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform($value);
    }

    public function testReverseTransformShouldThrowOnNonDataUri(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->transformer->reverseTransform(self::TEST_TXT_CONTENT);
    }

    public function testReverseTransformShouldTransformPlainData(): void
    {
        $file = $this->transformer->reverseTransform(self::TEST_TXT_DATA);

        self::assertInstanceOf(File::class, $file);

        $handle = $file->openFile();
        self::assertEquals(self::TEST_TXT_CONTENT, $handle->fread($handle->getSize()));
    }

    public function testReverseTransformShouldTransformBase64Data(): void
    {
        $file = $this->transformer->reverseTransform(self::TEST_GIF_DATA);

        self::assertInstanceOf(File::class, $file);

        $handle = $file->openFile();
        self::assertStringEqualsFile(__DIR__.'/../../Fixtures/test.gif', $handle->fread($handle->getSize()));
        self::assertEquals('image/gif', $file->getMimeType());
    }
}
