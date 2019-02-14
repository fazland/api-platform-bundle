<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class MappingTransformer implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface
     */
    private $innerTransformer;

    public function __construct(DataTransformerInterface $innerTransformer)
    {
        $this->innerTransformer = $innerTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value): ?array
    {
        if (empty($value)) {
            return [];
        }

        $transformed = [];
        foreach ($value as $key => $item) {
            $transformed[$key] = $this->innerTransformer->transform($item);
        }

        return $transformed;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?array
    {
        if (empty($value)) {
            return [];
        }

        $transformed = [];
        foreach ($value as $key => $item) {
            $transformed[$key] = $this->innerTransformer->reverseTransform($item);
        }

        return $transformed;
    }
}
