<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle\Controller;

use Fazland\ApiPlatformBundle\Annotation\View;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class TestController extends Controller
{
    use VarDumperTestTrait;

    /**
     * @return mixed
     *
     * @View()
     */
    public function indexAction(): array
    {
        return [
            'test_foo' => 'foo.test',
        ];
    }

    public function bodyAction(Request $request): Response
    {
        return new Response(
            $this->getDump($request->request->all())."\n".
            $this->getDump($request->getContent())
        );
    }

    /**
     * @View()
     */
    public function formInvalidExceptionAction(): void
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
    public function formNotSubmittedExceptionAction(): void
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
    public function invalidJsonExceptionAction(): void
    {
        throw new InvalidJSONException('Invalid.');
    }
}
