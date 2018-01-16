<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\View\Controller;

use Fazland\ApiPlatformBundle\Annotation\View;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class TestController extends Controller
{
    /**
     * @return mixed
     *
     * @View()
     */
    public function indexAction()
    {
        return [
            'test_foo' => 'foo.test',
        ];
    }

    /**
     * @View()
     */
    public function formInvalidExceptionAction()
    {
        /** @var FormInterface $form */
        $form = $this->createFormBuilder()
            ->add('first')
            ->add('second')
            ->getForm();

        $form->submit(['first' => 'one', 'second' => 'two']);
        $form['first']->addError(new FormError('Foo error.'));

        throw new FormInvalidException($form);
    }

    /**
     * @View()
     */
    public function formNotSubmittedExceptionAction()
    {
        /** @var FormInterface $form */
        $form = $this->createFormBuilder()
            ->add('first')
            ->add('second')
            ->getForm();

        throw new FormNotSubmittedException($form);
    }

    /**
     * @View(serializationType="stdClass")
     */
    public function invalidJsonExceptionAction()
    {
        throw new InvalidJSONException('Invalid.');
    }

    /**
     * @View(serializationType="array<FooObject>")
     */
    public function customSerializationTypeAction()
    {
        return [
            ['data' => 'foobar'],
            ['test' => 'barbar']
        ];
    }

    /**
     * @View(serializationType="array<FooObject>")
     */
    public function customSerializationTypeWithIteratorAction()
    {
        return new \ArrayIterator([
            ['data' => 'foobar'],
            ['test' => 'barbar']
        ]);
    }
}
