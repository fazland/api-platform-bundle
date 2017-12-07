<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

use Fazland\ApiPlatformBundle\Exception\TypeError;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use JsonSchema\Validator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PatchManager implements PatchManagerInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var OperationFactory
     */
    private $operationsFactory;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(FormFactoryInterface $formFactory, ValidatorInterface $validator)
    {
        $this->formFactory = $formFactory;
        $this->validator = $validator;
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
        $validator->validate($object, $this->getSchema());

        if (! $validator->isValid()) {
            throw new InvalidJSONException('Invalid document.');
        }

        $factory = $this->getOperationsFactory();

        foreach ($object as $operation) {
            $op = $factory->factory($operation->op);

            try {
                $op->execute($patchable, $operation);
            } catch (NoSuchPropertyException | UnexpectedTypeException $exception) {
                throw new InvalidJSONException('Operation failed at path "'.$operation->path.'"', 0, $exception);
            }
        }

        $this->validate($patchable);
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

        $schema = json_decode(file_get_contents(realpath(__DIR__.'/data/schema.json')));

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
     * Calls the validator service and throws an InvalidJSONException
     * if the object is invalid.
     *
     * @param PatchableInterface $patchable
     *
     * @throws InvalidJSONException
     */
    protected function validate(PatchableInterface $patchable): void
    {
        $violations = $this->validator->validate($patchable);
        if (count($violations) === 0) {
            return;
        }

        throw new InvalidJSONException("Invalid entity: ".(string)$violations);
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
