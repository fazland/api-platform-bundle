<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager;

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

    public static function setUpBeforeClass()
    {
        self::$cache = new ArrayAdapter();
    }

    protected function setUp()
    {
        $this->formFactory = $this->prophesize(FormFactoryInterface::class);
        $this->validator = $this->prophesize(ValidatorInterface::class);
        $this->validator->validate(Argument::any())->willReturn(new ConstraintViolationList());

        $this->patchManager = $this->createPatchManager();
    }

    /**
     * @expectedException \TypeError
     */
    public function testPatchShouldRaiseAnErrorIfNotImplementingPatchInterface()
    {
        $this->patchManager->patch(new \stdClass(), $this->prophesize(Request::class)->reveal());
    }

    public function testPatchShouldOperateMergePatchIfContentTypeIsCorrect()
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(PatchableInterface::class);
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

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException
     */
    public function testMergePatchShouldThrowIfFormIsNotSubmitted()
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(PatchableInterface::class);
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

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException
     */
    public function testMergePatchShouldThrowIfFormIsNotValid()
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag([
            'content-type' => 'application/merge-patch+json',
        ]);

        $patchable = $this->prophesize(PatchableInterface::class);
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

    public function getInvalidJson()
    {
        yield [[]];
        yield [[
            ['op' => 'test', 'value' => 'foo'],
        ]];
    }

    /**
     * @dataProvider getInvalidJson
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     * @expectedExceptionMessage Invalid document.
     */
    public function testPatchShouldThrowIfDocumentIsInvalid($params)
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $patchable = $this->prophesize(PatchableInterface::class);
        $patchable->commit()->shouldNotBeCalled();

        $this->patchManager->patch($patchable->reveal(), $request->reveal());
    }

    public function getInvalidJsonAndObject()
    {
        yield [
            [
                ['op' => 'test', 'path' => '/a', 'value' => 'foo'],
            ],
            new class() implements PatchableInterface {
                public $b;

                public function getTypeClass(): string
                {
                    return '';
                }

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

                public function getTypeClass(): string
                {
                    return '';
                }

                public function commit(): void
                {
                }
            },
        ];
    }

    /**
     * @dataProvider getInvalidJsonAndObject
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     * @expectedExceptionMessageRegExp #Operation failed at path#
     */
    public function testPatchShouldThrowIfOperationErrored($params, $object)
    {
        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag($params);

        $this->patchManager->patch($object, $request->reveal());
    }

    public function testPatchShouldCommitModifications()
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

        $this->assertSame([
            'b' => [
                'b' => ['fooz', 'barz'],
                'd' => 42,
                'e' => 42,
            ],
        ], $object->reveal()->a);
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     * @expectedExceptionMessageRegExp /Invalid entity: /
     */
    public function testPatchShouldThrowInvalidJSONExceptionIfObjectIsInvalid()
    {
        $object = $this->prophesize(PatchableInterface::class);
        $object->reveal()->a = ['b' => ['c' => 'foo']];

        $this->validator->validate($object)->willReturn(new ConstraintViolationList([
            new ConstraintViolation('Invalid', 'Invalid', ['a'], '', 'property', 'invalid'),
        ]));

        $request = $this->prophesize(Request::class);
        $request->reveal()->headers = new HeaderBag();
        $request->reveal()->request = new ParameterBag([
            ['op' => 'test', 'path' => '/a/b/c', 'value' => 'foo'],
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
