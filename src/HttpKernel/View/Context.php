<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Context
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var UserInterface
     */
    private $user;

    public function __construct(Request $request, TokenStorageInterface $tokenStorage = null)
    {
        $this->request = $request;

        if (null === $tokenStorage ||
            null === ($token = $tokenStorage->getToken()) || ! is_object($token->getUser())) {
            $this->user = null;
        } else {
            $this->user = $token->getUser();
        }
    }

    public static function create(Request $request, TokenStorageInterface $tokenStorage = null)
    {
        return new static($request, $tokenStorage);
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
