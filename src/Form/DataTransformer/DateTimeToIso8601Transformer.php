<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer;

class DateTimeToIso8601Transformer extends BaseDateTimeTransformer
{
    private bool $asImmutable;

    public function __construct(?string $inputTimezone = null, ?string $outputTimezone = null, bool $asImmutable = false)
    {
        parent::__construct($inputTimezone, $outputTimezone);
        $this->asImmutable = $asImmutable;
    }

    /**
     * Transforms a normalized date into a localized date.
     *
     * @param \DateTimeInterface $dateTime A DateTimeInterface object
     *
     * @return string The formatted date
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeInterface
     */
    public function transform($dateTime): string
    {
        if (null === $dateTime) {
            return '';
        }

        if (! $dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        if ($this->inputTimezone !== $this->outputTimezone) {
            if (! $dateTime instanceof \DateTimeImmutable) {
                $dateTime = clone $dateTime;
            }

            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        }

        return \preg_replace('/\+00:?00$/', 'Z', $dateTime->format('c'));
    }

    /**
     * Transforms a formatted string following RFC 3339 into a normalized date.
     *
     * @param string $iso8601 Formatted string
     *
     * @return \DateTime Normalized date
     *
     * @throws TransformationFailedException If the given value is not a string,
     *                                       if the value could not be transformed
     */
    public function reverseTransform($iso8601): ?\DateTimeInterface
    {
        if (null === $iso8601 || $iso8601 instanceof \DateTimeInterface) {
            return $iso8601;
        }

        if (! \is_string($iso8601)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $iso8601) {
            return null;
        }

        if (! \preg_match('/^(\d{4})-(\d{2})-(\d{2})T\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|(?:(?:\+|-)\d{2}:?\d{2}))$/', $iso8601, $matches)) {
            throw new TransformationFailedException(\sprintf('The date "%s" is not a valid date.', $iso8601));
        }

        try {
            if (! $this->asImmutable) {
                $dateTime = new \DateTime($iso8601);
            } else {
                $dateTime = new \DateTimeImmutable($iso8601);
            }
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->inputTimezone !== $dateTime->getTimezone()->getName()) {
            $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
        }

        if (! \checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new TransformationFailedException(\sprintf('The date "%s-%s-%s" is not a valid date.', $matches[1], $matches[2], $matches[3]));
        }

        return $dateTime;
    }
}
