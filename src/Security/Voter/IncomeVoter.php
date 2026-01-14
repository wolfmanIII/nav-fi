<?php

namespace App\Security\Voter;

use App\Entity\Income;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class IncomeVoter extends Voter
{
    public const CREATE = 'income_create';
    public const VIEW = 'income_view';
    public const EDIT = 'income_edit';
    public const DELETE = 'income_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof Income;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Income $income */
        $income = $subject;

        return match ($attribute) {
            self::CREATE => $this->canCreate($income),
            self::VIEW => $this->canView($income, $user),
            self::EDIT => $this->canEdit($income, $user),
            self::DELETE => $this->canDelete($income, $user),
            default => false,
        };
    }

    private function canCreate(Income $income): bool
    {
        return $income->getId() === null;
    }

    private function canView(Income $income, User $user): bool
    {
        return $this->isOwner($income, $user);
    }

    private function canEdit(Income $income, User $user): bool
    {
        return $this->isOwner($income, $user);
    }

    private function canDelete(Income $income, User $user): bool
    {
        return $this->canEdit($income, $user);
    }

    private function isOwner(Income $income, User $user): bool
    {
        return $income->getUser() instanceof User
            && $income->getUser()->getId() === $user->getId();
    }
}
