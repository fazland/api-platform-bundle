<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Fazland\ApiPlatformBundle\Pagination\Exception\InvalidTokenException;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PageTokenTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if ($value instanceof PageToken) {
            return (string) $value;
        }

        if (\is_string($value)) {
            try {
                PageToken::parse($value);

                return $value;
            } catch (InvalidTokenException $e) {
            }
        }

        throw new TransformationFailedException('Invalid token provided');
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?PageToken
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof PageToken) {
            return $value;
        }

        try {
            return PageToken::parse($value);
        } catch (InvalidTokenException $e) {
            throw new TransformationFailedException('Invalid token provided');
        }
    }
}
