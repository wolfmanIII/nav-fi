<?php

namespace App\Security\Voter;

use App\Entity\Route;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Autorità per il controllo degli accessi e dei vincoli operativi sulle rotte.
 * Impedisce l'eliminazione di rotte con Nav-Link attivo per preservare l'integrità del log.
 */
class RouteVoter extends Voter
{
    public const DELETE = 'route_delete';
    public const EDIT = 'route_edit';
    public const VIEW = 'route_view';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW])
            && $subject instanceof Route;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Route $route */
        $route = $subject;

        // Controllo ownership (l'asset deve appartenere all'utente)
        if ($route->getAsset()->getUser() !== $user) {
            return false;
        }

        return match ($attribute) {
            self::DELETE => $this->canDelete($route),
            self::EDIT, self::VIEW => true,
            default => false,
        };
    }

    /**
     * Una rotta può essere eliminata solo se non è attualmente attiva.
     */
    private function canDelete(Route $route): bool
    {
        return !$route->isActive();
    }
}
