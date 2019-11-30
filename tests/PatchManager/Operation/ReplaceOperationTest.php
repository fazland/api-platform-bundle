<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Fazland\ApiPlatformBundle\PatchManager\Operation\ReplaceOperation;
use PHPUnit\Framework\TestCase;

class ReplaceOperationTest extends TestCase
{
    /**
     * @var ReplaceOperation
     */
    private $operation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new ReplaceOperation();
    }

    public function testShouldReplaceValueIfExists(): void
    {
        $obj = (object) ['one' => 'bar'];
        $this->operation->execute($obj, (object) ['path' => '/one', 'value' => 'foo']);

        self::assertEquals('foo', $obj->one);
    }

    public function testShouldThrowIfPathDoesNotExists(): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessage('Element at path "/one" does not exist.');
        $obj = (object) [];
        $this->operation->execute($obj, (object) ['path' => '/one', 'value' => 'foo']);
    }
}
