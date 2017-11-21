<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Exception;

class TypeError extends \TypeError
{
    public static function createArgumentInvalid(int $no, string $function, $expected, $given)
    {
        $message = sprintf(
            'Argument %u passed to %s must be of type %s, %s given',
            $no, $function,
            self::formatExpected($expected),
            is_object($given) ? get_class($given) : gettype($given)
        );

        return new self($message);
    }

    private static function formatExpected($expected)
    {
        if (! is_array($expected)) {
            return $expected;
        }

        $last = array_pop($expected);

        return implode(', ', $expected).' or '.$last;
    }
}
