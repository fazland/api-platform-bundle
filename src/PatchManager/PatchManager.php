<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

use Fazland\ApiPlatformBundle\Exception\TypeError;
use Fazland\ApiPlatformBundle\JSONPointer\Path;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\OperationNotAllowedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\UnmergeablePatchException;
use JsonSchema\Validator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PatchManager implements PatchManagerInterface
{
    private FormFactoryInterface $formFactory;
    private ValidatorInterface $validator;
    private OperationFactory $operationsFactory;
    protected ?CacheItemPoolInterface $cache;

    public function __construct(FormFactoryInterface $formFactory, ValidatorInterface $validator)
    {
        $this->formFactory = $formFactory;
        $this->validator = $validator;
        $this->operationsFactory = new OperationFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function patch($patchable, Request $request): void
    {
        if (! $patchable instanceof PatchableInterface) {
            throw TypeError::createArgumentInvalid(1, __METHOD__, PatchableInterface::class, $patchable);
        }

        if (\preg_match('#application/merge-patch\\+json#i', $request->headers->get('Content-Type', ''))) {
            if (! $patchable instanceof MergeablePatchableInterface) {
                throw new UnmergeablePatchException('Resource cannot be merge patched.');
            }

            $this->mergePatch($patchable, $request);

            return;
        }

        $object = (array) Validator::arrayToObjectRecursive($request->request->all());

        $validator = new Validator();
        $validator->validate($object, $this->getSchema());

        if (! $validator->isValid()) {
            throw new InvalidJSONException('Invalid document.');
        }

        $factory = $this->getOperationsFactory();

        foreach ($object as $operation) {
            if (isset($operation->value)) {
                $operation->value = \json_decode(\json_encode($operation->value), true);
            }

            $op = $factory->factory($operation->op);

            try {
                $op->execute($patchable, $operation);
            } catch (OperationNotAllowedException | NoSuchPropertyException | UnexpectedTypeException | TransformationFailedException $exception) {
                throw new InvalidJSONException('Operation failed at path "'.$operation->path.'"', 0, $exception);
            }
        }

        $this->validate($object, $patchable);
        $this->commit($patchable);
    }

    /**
     * Sets the cache pool.
     * Used to store parsed validator schema, for example.
     *
     * @param CacheItemPoolInterface $cache
     *
     * @required
     */
    public function setCache(?CacheItemPoolInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Gets the validation schema.
     *
     * @return object
     */
    protected function getSchema()
    {
        if (null !== $this->cache) {
            $item = $this->cache->getItem('patch_manager_schema');
            if ($item->isHit()) {
                return $item->get();
            }
        }

        $schema = \json_decode(\file_get_contents(\realpath(__DIR__.'/data/schema.json')));

        if (isset($item)) {
            $item->set($schema);
            $this->cache->saveDeferred($item);
        }

        return $schema;
    }

    /**
     * Gets an instance of OperationFactory.
     *
     * @return OperationFactory
     */
    protected function getOperationsFactory(): OperationFactory
    {
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
        $form = $this->formFactory->createNamed('', $patchable->getTypeClass(), $patchable, [
            'method' => Request::METHOD_PATCH,
        ]);

        $form->handleRequest($request);
        if (! $form->isSubmitted()) {
            throw new FormNotSubmittedException($form);
        }

        if (! $form->isValid()) {
            throw new FormInvalidException($form);
        }

        $this->commit($patchable);
    }

    /**
     * Calls the validator service and throws an InvalidJSONException
     * if the object is invalid.
     *
     * @param array              $operations
     * @param PatchableInterface $patchable
     *
     * @throws InvalidJSONException
     */
    protected function validate(array $operations, PatchableInterface $patchable): void
    {
        $violations = $this->validator->validate($patchable);
        if (0 === \count($violations)) {
            return;
        }

        $paths = [];
        foreach ($operations as $operation) {
            $path = new Path($operation->path);
            $paths[] = $path->getElement(0);
        }

        $paths = \array_unique($paths);
        foreach ($violations as $i => $violation) {
            $path = $violation->getPropertyPath();
            if (! $path) {
                continue;
            }

            $path = new PropertyPath($path);
            if (! \in_array($path->getElement(0), $paths, true)) {
                $violations->remove($i);
            }
        }

        if (0 === \count($violations)) {
            return;
        }

        throw new InvalidJSONException('Invalid entity: '.(string) $violations);
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
