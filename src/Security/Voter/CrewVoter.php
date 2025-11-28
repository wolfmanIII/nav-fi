<?php

namespace App\Security\Voter;

use App\Entity\Crew;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CrewVoter extends Voter
{
    public const EDIT = 'CREW_EDIT';
    public const VIEW = 'CREW_VIEW';
    public const DELETE = 'CREW_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Crew) {
            return false;
        }

        return in_array($attribute, [
            self::EDIT,
            self::VIEW,
            self::DELETE,
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
            default           => false,
        };
    }

    private function canView(Crew $crew, ?UserInterface $user = null): bool
    {
        return true;
    }

    private function canEdit(Crew $crew, ?UserInterface $user = null): bool
    {
        return !$crew->hasMortgageSigned();
    }

    private function canDelete(Crew $crew, ?UserInterface $user = null): bool
    {
        return $this->canEdit($crew, $user);
    }
}
