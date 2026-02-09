<?php

namespace App\Security\Voter;

use App\Entity\Crew;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CrewVoter extends Voter
{
    public const EDIT = 'CREW_EDIT';
    public const CREATE = 'CREW_CREATE';
    public const VIEW = 'CREW_VIEW';
    public const DELETE = 'CREW_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Crew) {
            return false;
        }

        return in_array($attribute, [
            self::CREATE,
            self::EDIT,
            self::VIEW,
            self::DELETE,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::CREATE      => $this->canCreate($subject, $user),
            self::VIEW        => $this->canView($subject, $user),
            self::EDIT        => $this->canEdit($subject, $user),
            self::DELETE      => $this->canDelete($subject, $user),
            default           => false,
        };
    }

    private function canCreate(Crew $crew, ?UserInterface $user = null): bool
    {
        return $crew->getId() === null;
    }

    private function canView(Crew $crew, ?UserInterface $user = null): bool
    {
        return $this->isOwner($crew, $user);
    }

    private function canEdit(Crew $crew, ?UserInterface $user = null): bool
    {
        return $this->isOwner($crew, $user);
    }

    private function canDelete(Crew $crew, ?UserInterface $user = null): bool
    {
        return $this->isOwner($crew, $user);
    }

    private function isOwner(Crew $crew, UserInterface $user): bool
    {
        return $crew->getUser() instanceof User
            && $user instanceof User
            && $crew->getUser()->getId() === $user->getId();
    }
}
