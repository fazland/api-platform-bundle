<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\InvalidArgumentException;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

trait ExpressionTrait
{
    private static function assertLiteral($value, $argNo = null): void
    {
        if ($value instanceof LiteralExpression) {
            return;
        }

        throw new InvalidArgumentException(self::getShortName().' accepts only literal expressions as argument'.(null !== $argNo ? ' #'.$argNo : '').'. Passed '.(string) $value);
    }

    private static function getShortName(): string
    {
        $reflClass = new \ReflectionClass(self::class);

        return \preg_replace('/Expression$/', '', $reflClass->getShortName());
    }
}
