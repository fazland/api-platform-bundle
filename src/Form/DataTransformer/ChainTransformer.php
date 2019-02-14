<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ChainTransformer implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface[]
     */
    private $transformers;

    public function __construct(DataTransformerInterface ...$transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        foreach ($this->transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        foreach ($this->transformers as $transformer) {
            $value = $transformer->reverseTransform($value);
        }

        return $value;
    }
}
