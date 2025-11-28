<?php

namespace App\Security\Voter;

use App\Entity\Ship;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ShipVoter extends Voter
{
    public const EDIT = 'SHIP_EDIT';
    public const VIEW = 'SHIP_VIEW';
    public const DELETE = 'SHIP_DELETE';
    public const CREW_REMOVE = 'SHIP_CREW_REMOVE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Ship) {
            return false;
        }

        return in_array($attribute, [
            self::EDIT,
            self::VIEW,
            self::DELETE,
            self::CREW_REMOVE,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        #TODO implementare l'autenticazione
        #if (!$user instanceof UserInterface) {
        #    return true;
        #}

        return match ($attribute) {
            self::VIEW        => $this->canView($subject, $user),
            self::EDIT        => $this->canEdit($subject, $user),
            self::DELETE      => $this->canDelete($subject, $user),
            self::CREW_REMOVE => $this->canCrewRemove($subject, $user),
            default           => false,
        };
    }

    private function canView(Ship $ship, ?UserInterface $user = null): bool
    {
        return true;
    }

    private function canEdit(Ship $ship, ?UserInterface $user = null): bool
    {
        return !$ship->hasMortgageSigned();
    }

    private function canDelete(Ship $ship, ?UserInterface $user = null): bool
    {
        if (
            $ship->getCrews()->count() >= 0
            && $ship->hasMortgage()
        ) {
            return false;
        }

        return $this->canEdit($ship, $user);
    }

    private function canCrewRemove(Ship $ship, ?UserInterface $user = null): bool
    {
        return $this->canEdit($ship, $user);
    }
}
