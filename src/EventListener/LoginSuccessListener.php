<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
class LoginSuccessListener
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $user instanceof User) {
            $session = $request->getSession();

            if ($user->getFullName()) {
                $session->getFlashBag()->add('success', 'Bon retour ' . $user->getFullName() . ' ! 👋');
            } else {
                $session->getFlashBag()->add('success', 'Connexion réussie ! Bienvenue sur mi3cda-chat. 🎉');
                $session->getFlashBag()->add('info', 'Vous pouvez <a href="/profile/edit">compléter votre profil</a> pour que vos contacts vous identifient plus facilement.');
            }
        }
    }
}
