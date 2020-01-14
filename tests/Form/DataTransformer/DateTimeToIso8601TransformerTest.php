<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Form\DataTransformer\DateTimeToIso8601Transformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeToIso8601TransformerTest extends TestCase
{
    private ?\DateTimeInterface $dateTime;
    private ?\DateTimeInterface $dateTimeWithoutSeconds;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->dateTime = new \DateTime('2010-02-03 04:05:06 UTC');
        $this->dateTimeWithoutSeconds = new \DateTime('2010-02-03 04:05:00 UTC');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->dateTime = null;
        $this->dateTimeWithoutSeconds = null;
    }

    public function allProvider(): iterable
    {
        return [
            ['UTC', 'UTC', '2010-02-03 04:05:06 UTC', '2010-02-03T04:05:06Z'],
            ['UTC', 'UTC', null, ''],
            ['America/New_York', 'Asia/Hong_Kong', '2010-02-03 04:05:06 America/New_York', '2010-02-03T17:05:06+08:00'],
            ['America/New_York', 'Asia/Hong_Kong', null, ''],
            ['UTC', 'Asia/Hong_Kong', '2010-02-03 04:05:06 UTC', '2010-02-03T12:05:06+08:00'],
            ['America/New_York', 'UTC', '2010-02-03 04:05:06 America/New_York', '2010-02-03T09:05:06Z'],
        ];
    }

    public function transformProvider(): iterable
    {
        return $this->allProvider();
    }

    public function reverseTransformProvider(): iterable
    {
        return \array_merge($this->allProvider(), [
            // format without seconds, as appears in some browsers
            ['UTC', 'UTC', '2010-02-03 04:05:00 UTC', '2010-02-03T04:05Z'],
            ['UTC', 'UTC', '2010-02-03 05:06:00 UTC', '2010-02-03T05:06+0000'],
            ['America/New_York', 'Asia/Hong_Kong', '2010-02-03 04:05:00 America/New_York', '2010-02-03T17:05+08:00'],
            ['Europe/Amsterdam', 'Europe/Amsterdam', '2013-08-21 10:30:00 Europe/Amsterdam', '2013-08-21T08:30:00Z'],
        ]);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform(string $fromTz, string $toTz, ?string $from, string $to): void
    {
        $transformer = new DateTimeToIso8601Transformer($fromTz, $toTz);

        self::assertSame($to, $transformer->transform(null !== $from ? new \DateTime($from) : null));
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransformDateTimeImmutable(string $fromTz, string $toTz, ?string $from, string $to): void
    {
        $transformer = new DateTimeToIso8601Transformer($fromTz, $toTz);

        self::assertSame($to, $transformer->transform(null !== $from ? new \DateTimeImmutable($from) : null));
    }

    public function testTransformRequiresValidDateTime(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new DateTimeToIso8601Transformer();
        $transformer->transform('2010-01-01');
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform(string $toTz, string $fromTz, ?string $to, string $from): void
    {
        $transformer = new DateTimeToIso8601Transformer($toTz, $fromTz);

        if (null !== $to) {
            self::assertEquals(new \DateTime($to), $transformer->reverseTransform($from));
        } else {
            self::assertNull($transformer->reverseTransform($from));
        }
    }

    public function testReverseTransformRequiresString(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new DateTimeToIso8601Transformer();
        $transformer->reverseTransform(12345);
    }

    public function testReverseTransformWithNonExistingDate(): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new DateTimeToIso8601Transformer('UTC', 'UTC');

        $transformer->reverseTransform('2010-04-31T04:05Z');
    }

    /**
     * @dataProvider invalidDateStringProvider
     */
    public function testReverseTransformExpectsValidDateString(string $date): void
    {
        $this->expectException(TransformationFailedException::class);
        $transformer = new DateTimeToIso8601Transformer('UTC', 'UTC');

        $transformer->reverseTransform($date);
    }

    public function invalidDateStringProvider(): iterable
    {
        return [
            'invalid month' => ['2010-2010-01'],
            'invalid day' => ['2010-10-2010'],
            'no date' => ['x'],
            'cookie format' => ['Saturday, 01-May-2010 04:05:00 Z'],
            'RFC 822 format' => ['Sat, 01 May 10 04:05:00 +0000'],
            'RSS format' => ['Sat, 01 May 2010 04:05:00 +0000'],
        ];
    }
}
