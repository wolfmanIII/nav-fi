<?php

namespace App\Security\Voter;

use App\Entity\Ship;
use App\Entity\User;
use App\Repository\AnnualBudgetRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ShipVoter extends Voter
{
    public const CREATE = 'SHIP_CREATE';
    public const VIEW = 'SHIP_VIEW';
    public const EDIT = 'SHIP_EDIT';
    public const DELETE = 'SHIP_DELETE';
    public const CREW_REMOVE = 'SHIP_CREW_REMOVE';
    public const CAMPAIGN_REMOVE = 'SHIP_CAMPAIGN_REMOVE';

    public function __construct(
        private AnnualBudgetRepository $annualBudgetRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Ship) {
            return false;
        }

        return in_array($attribute, [
            self::CREATE,
            self::EDIT,
            self::VIEW,
            self::DELETE,
            self::CREW_REMOVE,
            self::CAMPAIGN_REMOVE,
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
            self::CREW_REMOVE => $this->canCrewRemove($subject, $user),
            self::CAMPAIGN_REMOVE => $this->canCampaignRemove($subject, $user),
            default           => false,
        };
    }

    private function canCreate(Ship $ship, ?UserInterface $user = null): bool
    {
        return $ship->getId() === null;
    }

    private function canView(Ship $ship, ?UserInterface $user = null): bool
    {
        return $this->isOwner($ship, $user);
    }

    private function canEdit(Ship $ship, ?UserInterface $user = null): bool
    {
        return $this->isOwner($ship, $user);
    }

    private function canDelete(Ship $ship, ?UserInterface $user = null): bool
    {
        if (!$this->isOwner($ship, $user)) {
            return false;
        }

        if ($ship->getCampaign() !== null) {
            return false;
        }

        if ($ship->getCrews()->count() > 0) {
            return false;
        }

        if ($ship->getIncomes()->count() > 0) {
            return false;
        }

        if ($ship->getCosts()->count() > 0) {
            return false;
        }

        if ($ship->getMortgage() !== null) {
            return false;
        }

        if ($this->annualBudgetRepository->count(['ship' => $ship]) > 0) {
            return false;
        }

        return true;
    }

    private function canCrewRemove(Ship $ship, ?UserInterface $user = null): bool
    {
        return $this->canEdit($ship, $user);
    }

    private function canCampaignRemove(Ship $ship, ?UserInterface $user = null): bool
    {
        if (!$this->isOwner($ship, $user)) {
            return false;
        }

        if ($ship->getIncomes()->count() > 0) {
            return false;
        }

        if ($ship->getCosts()->count() > 0) {
            return false;
        }

        if ($ship->getMortgage() !== null) {
            return false;
        }

        return $this->annualBudgetRepository->count(['ship' => $ship]) === 0;
    }

    private function isOwner(Ship $ship, UserInterface $user): bool
    {
        return $ship->getUser() instanceof User
            && $user instanceof User
            && $ship->getUser()->getId() === $user->getId();
    }
}
