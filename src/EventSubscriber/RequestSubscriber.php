<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestSubscriber implements EventSubscriberInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var TokenStorageInterface */
    protected $token_storage;

    /**
     * RequestSubscriber constructor.
     *
     * @param RouterInterface       $router
     * @param TokenStorageInterface $token_storage
     */
    public function __construct(RouterInterface $router, TokenStorageInterface $token_storage)
    {
        $this->router        = $router;
        $this->token_storage = $token_storage;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (null === $token = $this->token_storage->getToken()) {
            return;
        }

        /** @var User $user */
        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        if (empty($user->getTelegramUsername())) {
            $route = 'profile_telegram';

            if ($route === $event->getRequest()->get('_route')) {
                return;
            }

            $response = new RedirectResponse($this->router->generate($route));
            $event->setResponse($response);
        }
    }
}
