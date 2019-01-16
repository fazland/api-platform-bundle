<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Exception;

use Symfony\Component\Form\FormInterface;

class FormNotSubmittedException extends BadRequestException
{
    /**
     * @var FormInterface
     */
    private $form;

    public function __construct(FormInterface $form, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->form = $form;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        return $this->form;
    }
}
