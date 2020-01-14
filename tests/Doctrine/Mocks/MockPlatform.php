<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\Mocks;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class MockPlatform extends AbstractPlatform
{
    /**
     * {@inheritdoc}
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeDoctrineTypeMappings(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getClobTypeDeclarationSQL(array $field): string
    {
        return 'CLOB';
    }

    /**
     * {@inheritdoc}
     */
    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed): string
    {
        $type = $fixed ? 'CHAR' : 'VARCHAR';
        $length = $length ?? 255;

        return "$type($length)";
    }

    /**
     * {@inheritdoc}
     */
    public function getBlobTypeDeclarationSQL(array $field): string
    {
        return 'DUMMY_BINARY';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'dummy';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed): string
    {
        return $fixed ? 'DUMMY_BINARY('.($length ?: 255).')' : 'DUMMY_VARBINARY('.($length ?: 255).')';
    }
}
