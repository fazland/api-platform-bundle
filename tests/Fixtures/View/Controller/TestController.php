<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\View\Controller;

use Kcs\ApiPlatformBundle\Annotation\View;
use Kcs\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Kcs\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
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
     * @View()
     */
    public function invalidJsonExceptionAction()
    {
        throw new InvalidJSONException('Invalid.');
    }
}
