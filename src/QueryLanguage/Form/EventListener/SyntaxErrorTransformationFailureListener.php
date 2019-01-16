<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form\EventListener;

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\SyntaxError;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

class SyntaxErrorTransformationFailureListener implements EventSubscriberInterface
{
    /**
     * @var null|TranslatorInterface
     */
    private $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => ['convertTransformationFailureToFormError', 100],
        ];
    }

    /**
     * Converts an AQL syntax error into a form error, or process a transformation failure exception normally.
     *
     * @param FormEvent $event
     */
    public function convertTransformationFailureToFormError(FormEvent $event): void
    {
        $form = $event->getForm();
        $failure = $form->getTransformationFailure();

        if (null === $failure || ! $form->isValid()) {
            return;
        }

        foreach ($form as $child) {
            if (! $child->isSynchronized()) {
                return;
            }
        }

        $clientDataAsString = \is_scalar($form->getViewData()) ? (string) $form->getViewData() : \gettype($form->getViewData());
        $previous = $failure;

        while ((null !== $previous = $previous->getPrevious()) && ! $previous instanceof SyntaxError) {
            // Intentionally empty. Just cycle all previous exceptions until a SyntaxError is found.
        }

        if (null === $previous) {
            return;
        }

        $messageTemplate = $previous->getMessage();

        if (null !== $this->translator) {
            $message = $this->translator->trans($messageTemplate, ['{{ value }}' => $clientDataAsString]);
        } else {
            $message = \strtr($messageTemplate, ['{{ value }}' => $clientDataAsString]);
        }

        $form->addError(new FormError($message, $messageTemplate, ['{{ value }}' => $clientDataAsString], null, $form->getTransformationFailure()));
    }
}
