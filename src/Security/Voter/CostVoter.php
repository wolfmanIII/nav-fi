<?php

namespace App\Security\Voter;

use App\Entity\Cost;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CostVoter extends Voter
{
    public const CREATE = 'cost_create';
    public const VIEW = 'cost_view';
    public const EDIT = 'cost_edit';
    public const DELETE = 'cost_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof Cost;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Cost $cost */
        $cost = $subject;

        return match ($attribute) {
            self::CREATE => $this->canCreate($cost),
            self::VIEW => $this->canView($cost, $user),
            self::EDIT => $this->canEdit($cost, $user),
            self::DELETE => $this->canDelete($cost, $user),
            default => false,
        };
    }

    private function canCreate(Cost $cost): bool
    {
        return $cost->getId() === null;
    }

    private function canView(Cost $cost, User $user): bool
    {
        return $this->isOwner($cost, $user);
    }

    private function canEdit(Cost $cost, User $user): bool
    {
        return $this->isOwner($cost, $user);
    }

    private function canDelete(Cost $cost, User $user): bool
    {
        return $this->canEdit($cost, $user);
    }

    private function isOwner(Cost $cost, User $user): bool
    {
        return $cost->getUser() instanceof User
            && $cost->getUser()->getId() === $user->getId();
    }
}
