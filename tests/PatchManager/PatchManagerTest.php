<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager;

use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\OperationNotAllowedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\UnmergeablePatchException;
use Fazland\ApiPlatformBundle\PatchManager\MergeablePatchableInterface;
use Fazland\ApiPlatformBundle\PatchManager\PatchableInterface;
use Fazland\ApiPlatformBundle\PatchManager\PatchManager;
use Fazland\ApiPlatformBundle\PatchManager\PatchManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PatchManagerTest extends TestCase
{
    /**
     * @var FormFactoryInterface|ObjectProphecy
     */
    private $formFactory;

    /**
     * @var PatchManager
     */
    private $patchManager;

    /**
     * @var CacheItemPoolInterface
     */
    private static $cache;

    /**
     * @var ValidatorInterface|ObjectProphecy
     */
    private $validator;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        self::$cache = new ArrayAdapter();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formFactory = $this->prophesize(FormFactoryInterface::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->validator->validate(Argument::any())->willReturn(new ConstraintViolationList());

        $this->patchManager = $this->createPatchManager();
    }

    public function testPatchShouldRaiseAnErrorIfNotImplementingPatchInterface(): void
    {
        $this->expectException(\TypeError::class);
        $this->patchManager->patch(new \stdClass(), $this->prophesize(Request::class)->reveal());
    }

    public function testPatchShouldOperateMergePatchIfContentTypeIsCorrect(): void
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(MergeablePatchableInterface::class);
        $patchable->getTypeClass()->willReturn('Test\\TestType');
        $patchable->commit()->shouldBeCalled();

        $form = $this->prophesize(FormInterface::class);
        $form->handleRequest($request)->willReturn();
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(true);

        $this->formFactory->createNamed(null, 'Test\\TestType', $patchable, Argument::any())
            ->shouldBeCalled()
            ->willReturn($form);

        $this->patchManager->patch($patchable->reveal(), $request->reveal());
    }

    public function testMergePatchShouldThrowIfFormIsNotSubmitted(): void
    {
        $this->expectException(FormNotSubmittedException::class);
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(MergeablePatchableInterface::class);
        $patchable->getTypeClass()->willReturn('Test\\TestType');
        $patchable->commit()->shouldNotBeCalled();

        $form = $this->prophesize(FormInterface::class);
        $form->handleRequest($request)->willReturn();
        $form->isSubmitted()->willReturn(false);

        $this->formFactory->createNamed(null, 'Test\\TestType', $patchable, Argument::any())
            ->shouldBeCalled()
            ->willReturn($form);

        $this->patchManager->patch($patchable->reveal(), $request->reveal());
    }

    public function testMergePatchShouldThrowIfFormIsNotValid(): void
    {
        $this->expectException(FormInvalidException::class);
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(MergeablePatchableInterface::class);
        $patchable->getTypeClass()->willReturn('Test\\TestType');
        $patchable->commit()->shouldNotBeCalled();

        $form = $this->prophesize(FormInterface::class);
        $form->handleRequest($request)->willReturn();
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(false);

        $this->formFactory->createNamed(null, 'Test\\TestType', $patchable, Argument::any())
            ->shouldBeCalled()
            ->willReturn($form);

        $this->patchManager->patch($patchable->reveal(), $request->reveal());
    }

    public function getInvalidJson(): iterable
    {
        yield [[]];
        yield [[
            ['op' => 'test', 'value' => 'foo'],
        ]];
    }

    /**
     * @dataProvider getInvalidJson
     */
    public function testPatchShouldThrowIfDocumentIsInvalid(array $params): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessage('Invalid document.');
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $patchable = $this->prophesize(PatchableInterface::class);
        $patchable->commit()->shouldNotBeCalled();

        $this->patchManager->patch($patchable->reveal(), $request->reveal());
    }

    public function getInvalidJsonAndObject(): iterable
    {
        yield [
            [
                ['op' => 'test', 'path' => '/a', 'value' => 'foo'],
            ],
            new class() implements PatchableInterface {
                public $b;

                public function commit(): void
                {
                }
            },
        ];

        yield [
            [
                ['op' => 'test', 'path' => '/a/b', 'value' => 'foo'],
            ],
            new class() implements PatchableInterface {
                public $a = 'foobar';

                public function commit(): void
                {
                }
            },
        ];
    }

    /**
     * @dataProvider getInvalidJsonAndObject
     */
    public function testPatchShouldThrowIfOperationErrored(array $params, $object): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessageMatches('/Operation failed at path/');
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $this->patchManager->patch($object, $request->reveal());
    }

    public function testPatchShouldCommitModifications(): void
    {
        $object = $this->prophesize(PatchableInterface::class);
        $object->commit()->shouldBeCalled();

        $object->reveal()->a = ['b' => ['c' => 'foo']];

        $params = [
            ['op' => 'test', 'path' => '/a/b/c', 'value' => 'foo'],
            ['op' => 'remove', 'path' => '/a/b/c'],
            ['op' => 'add', 'path' => '/a/b/c', 'value' => ['foo', 'bar']],
            ['op' => 'add', 'path' => '/a/b/b', 'value' => ['fooz', 'barz']],
            ['op' => 'replace', 'path' => '/a/b/c', 'value' => 42],
            ['op' => 'move', 'from' => '/a/b/c', 'path' => '/a/b/d'],
            ['op' => 'copy', 'from' => '/a/b/d', 'path' => '/a/b/e'],
        ];

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $this->patchManager->patch($object->reveal(), $request->reveal());

        self::assertSame([
            'b' => [
                'b' => ['fooz', 'barz'],
                'd' => 42,
                'e' => 42,
            ],
        ], $object->reveal()->a);
    }

    public function testPatchShouldThrowInvalidJSONExceptionIfObjectIsInvalid(): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessageMatches('/Invalid entity: /');
        $object = $this->prophesize(PatchableInterface::class);
        $object->a = ['b' => ['c' => 'foo']];

        $this->validator->validate($object)->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'non-patched-property', 'invalid'),
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'property', 'invalid'),
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'a[b]', 'invalid'),
        ]));

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag([
            ['op' => 'test', 'path' => '/a/b/c', 'value' => 'foo'],
        ]);

        $this->patchManager->patch($object->reveal(), $request->reveal());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testPatchShouldNotThrowOnObjectInvalidForNonPatchedProperty(): void
    {
        $object = $this->prophesize(PatchableInterface::class);
        $object->a = ['b' => ['c' => 'foo']];

        $this->validator->validate($object)->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'non-patched-property', 'invalid'),
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'property', 'invalid'),
        ]));

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag([
            ['op' => 'test', 'path' => '/a/b/c', 'value' => 'foo'],
        ]);

        $this->patchManager->patch($object->reveal(), $request->reveal());
    }

    public function testPatchShouldNotIgnoreRootErrors(): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessageMatches('/Invalid entity: /');
        $object = $this->prophesize(PatchableInterface::class);
        $object->a = ['b' => ['c' => 'foo']];

        $this->validator->validate($object)->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', null, 'invalid'),
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'property', 'invalid'),
        ]));

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag([
            ['op' => 'test', 'path' => '/a/b/c', 'value' => 'foo'],
        ]);

        $this->patchManager->patch($object->reveal(), $request->reveal());
    }

    public function testPatchShouldThrowInvalidJSONExceptionOnOperationNotAllowedException(): void
    {
        $this->expectException(InvalidJSONException::class);
        $this->expectExceptionMessageMatches('/Operation failed at path /');
        $params = [
            [
                'op' => 'remove',
                'path' => '/items/0',
            ],
        ];

        $object = new class() implements PatchableInterface {
            private $items;

            public function __construct()
            {
                $this->items = ['this-is-an-item'];
            }

            public function getItems(): array
            {
                return $this->items;
            }

            public function addItem($item): void
            {
                $this->items[] = $item;
            }

            public function removeItem($item): void
            {
                throw new OperationNotAllowedException();
            }

            public function commit(): void
            {
            }
        };

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $this->patchManager->patch($object, $request->reveal());
    }

    public function testPatchShouldThrowIfObjectIsNotInstanceOfMergeablePatchableInterface(): void
    {
        $this->expectException(UnmergeablePatchException::class);
        $this->expectExceptionMessage('Resource cannot be merge patched.');
        $object = $this->prophesize(PatchableInterface::class);
        $request = $this->prophesize(Request::class);

        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $this->patchManager->patch($object->reveal(), $request->reveal());
    }

    protected function createPatchManager(): PatchManagerInterface
    {
        $manager = new PatchManager($this->formFactory->reveal(), $this->validator->reveal());
        $manager->setCache(self::$cache);

        return $manager;
    }
}
