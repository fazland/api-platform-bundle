<?php

declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager;

use JsonSchema\Validator;
use Kcs\ApiPlatformBundle\Exception\TypeError;
use Kcs\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Kcs\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

class PatchManager implements PatchManagerInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    private $operationsFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($patchable, Request $request): void
    {
        if (! $patchable instanceof PatchableInterface) {
            throw TypeError::createArgumentInvalid(1, __METHOD__, PatchableInterface::class, $patchable);
        }

        if (preg_match('#application/merge-patch\\+json#i', $request->headers->get('Content-Type', ''))) {
            $this->mergePatch($patchable, $request);

            return;
        }

        $object = (array) Validator::arrayToObjectRecursive($request->request->all());

        $validator = new Validator();
        $validator->validate($object, (object) ['$ref' => 'file://'.realpath(__DIR__.'/data/schema.json')]);

        if (! $validator->isValid()) {
            throw new InvalidJSONException('Invalid document.');
        }

        $factory = $this->getOperationsFactory();

        foreach ($object as $operation) {
            $op = $factory->factory($operation->op);

            try {
                $op->execute($patchable, $operation);
            } catch (NoSuchPropertyException | UnexpectedTypeException $exception) {
                throw new InvalidJSONException('Operation failed at path "'.$operation->path.'"');
            }
        }

        $this->commit($patchable);
    }

    /**
     * Gets an instance of OperationFactory.
     *
     * @return OperationFactory
     */
    protected function getOperationsFactory(): OperationFactory
    {
        if (null === $this->operationsFactory) {
            $this->operationsFactory = new OperationFactory();
        }

        return $this->operationsFactory;
    }

    /**
     * Executes a merge-PATCH.
     *
     * @param PatchableInterface $patchable
     * @param Request            $request
     *
     * @throws FormInvalidException
     * @throws FormNotSubmittedException
     */
    protected function mergePatch(PatchableInterface $patchable, Request $request): void
    {
        $form = $this->formFactory
            ->createNamed(null, $patchable->getTypeClass(), $patchable, [
                'method' => Request::METHOD_PATCH,
            ]);

        $form->handleRequest($request);
        if (! $form->isSubmitted()) {
            throw new FormNotSubmittedException($form);
        } elseif (! $form->isValid()) {
            throw new FormInvalidException($form);
        }

        $this->commit($patchable);
    }

    /**
     * Commit modifications.
     *
     * @param PatchableInterface $patchable
     */
    protected function commit(PatchableInterface $patchable): void
    {
        $patchable->commit();
    }
}
