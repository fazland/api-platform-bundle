<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;

class TestOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $value = $this->accessor->getValue($subject, $operation->path);

        if (! $this->isEqual($value, $operation->value)) {
            throw new InvalidJSONException('Test operation on "'.$operation->path.'" failed.');
        }
    }

    private function isEqual($objectValue, $value): bool
    {
        if ('true' === $value) {
            $value = true;
        }

        if ('false' === $value) {
            $value = false;
        }

        if (is_bool($value)) {
            return is_bool($objectValue) && $objectValue === $value;
        }

        if ($objectValue == $value) {
            // Easy: int/float to numeric string
            return true;
        }

        if (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (is_array($value)) {
            if (is_object($objectValue)) {
                $objectValue = json_decode(json_encode($objectValue), true);
            }

            $this->sort($value);
            $this->sort($objectValue);

            return $value === $objectValue;
        }

        return false;
    }

    /**
     * Recursive key-sorting to have comparable arrays.
     *
     * @param array $json
     */
    private function sort(array &$json): void
    {
        ksort($json);

        foreach ($json as &$value) {
            if (! is_array($value)) {
                continue;
            }

            $this->sort($value);
        }
    }
}
