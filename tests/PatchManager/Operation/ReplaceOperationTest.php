<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

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

        $this->assertEquals('foo', $obj->one);
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     * @expectedExceptionMessage Element at path "/one" does not exist.
     */
    public function testShouldThrowIfPathDoesNotExists(): void
    {
        $obj = (object) [];
        $this->operation->execute($obj, (object) ['path' => '/one', 'value' => 'foo']);
    }
}
