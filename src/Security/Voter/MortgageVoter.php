<?php

namespace App\Security\Voter;

use App\Entity\Mortgage;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class MortgageVoter extends Voter
{
    public const EDIT = 'MORTGAGE_EDIT';
    public const CREATE = 'MORTGAGE_CREATE';
    public const VIEW = 'MORTGAGE_VIEW';
    public const SIGN = 'MORTGAGE_SIGN';
    public const DELETE = 'MORTGAGE_DELETE';
    public const PAY_INSTALLMENT = 'MORTGAGE_PAY_INSTALLMENT';
    public const CREATE_PDF = 'MORTGAGE_CREATE_PDF';

    protected function supports(string $attribute, mixed $subject): bool
    {

        if (!$subject instanceof Mortgage) {
            return false;
        }

        return in_array($attribute, [
            self::CREATE,
            self::EDIT,
            self::VIEW,
            self::SIGN,
            self::DELETE,
            self::PAY_INSTALLMENT,
            self::CREATE_PDF,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::CREATE      => $this->canCreate($subject, $user),
            self::VIEW        => $this->canView($subject, $user),
            self::EDIT        => $this->canEdit($subject, $user),
            self::SIGN        => $this->canSign($subject, $user),
            self::DELETE      => $this->canDelete($subject, $user),
            self::PAY_INSTALLMENT => $this->canPayInstallment($subject, $user),
            self::CREATE_PDF  => $this->canCreatePdf($subject, $user),
            default           => false,
        };
    }

    private function canCreate(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $mortgage->getId() === null;
    }

    private function canView(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->isOwner($mortgage, $user);
    }

    private function canEdit(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->isOwner($mortgage, $user);
    }

    private function canDelete(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->canEdit($mortgage, $user);
    }

    private function canSign(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->isOwner($mortgage, $user) && !$mortgage->isSigned() && $mortgage->getId();
    }

    private function canPayInstallment(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->isOwner($mortgage, $user) && $mortgage->isSigned();
    }

    private function canCreatePdf(Mortgage $mortgage, ?UserInterface $user = null): bool
    {
        return $this->isOwner($mortgage, $user);
    }

    private function isOwner(Mortgage $mortgage, UserInterface $user): bool
    {
        return $mortgage->getUser() instanceof User
            && $user instanceof User
            && $mortgage->getUser()->getId() === $user->getId();
    }
}
