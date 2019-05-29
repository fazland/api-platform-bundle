<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Accessor;

final class DateTimeValueAccessor implements ValueAccessorInterface
{
    /**
     * @var ValueAccessorInterface
     */
    private $decorated;

    public function __construct(ValueAccessorInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(object $object, string $propertyPath)
    {
        $value = $this->decorated->getValue($object, $propertyPath);
        if ($value instanceof \DateTimeInterface) {
            $value = $value->getTimestamp();
        }

        return $value;
    }
}
