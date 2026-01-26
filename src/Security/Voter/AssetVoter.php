<?php

namespace App\Security\Voter;

use App\Entity\Asset;
use App\Entity\User;
use App\Repository\AnnualBudgetRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class AssetVoter extends Voter
{
    public const CREATE = 'ASSET_CREATE';
    // Rinominiamoli in ASSET_
    public const VIEW = 'ASSET_VIEW';
    public const EDIT = 'ASSET_EDIT';
    public const DELETE = 'ASSET_DELETE';
    public const CREW_REMOVE = 'ASSET_CREW_REMOVE';
    public const CAMPAIGN_REMOVE = 'ASSET_CAMPAIGN_REMOVE';

    public function __construct(
        private AnnualBudgetRepository $annualBudgetRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Asset) {
            return false;
        }

        return in_array($attribute, [
            self::CREATE, // Nota: CREATE su un'istanza di solito verifica subject o classe? Nel controller: canCreate si aspetta un'istanza?
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

    private function canCreate(Asset $asset, ?UserInterface $user = null): bool
    {
        return $asset->getId() === null;
    }

    private function canView(Asset $asset, ?UserInterface $user = null): bool
    {
        return $this->isOwner($asset, $user);
    }

    private function canEdit(Asset $asset, ?UserInterface $user = null): bool
    {
        return $this->isOwner($asset, $user);
    }

    private function canDelete(Asset $asset, ?UserInterface $user = null): bool
    {
        if (!$this->isOwner($asset, $user)) {
            return false;
        }

        if ($asset->getCampaign() !== null) {
            return false;
        }

        if ($asset->getCrews()->count() > 0) {
            return false;
        }

        $account = $asset->getFinancialAccount();
        if ($account) {
            if ($account->getIncomes()->count() > 0) {
                return false;
            }

            if ($account->getCosts()->count() > 0) {
                return false;
            }

            if ($this->annualBudgetRepository->count(['financialAccount' => $account]) > 0) {
                return false;
            }
        }

        if ($asset->getMortgage() !== null) {
            return false;
        }

        return true;
    }

    private function canCrewRemove(Asset $asset, ?UserInterface $user = null): bool
    {
        return $this->canEdit($asset, $user);
    }

    private function canCampaignRemove(Asset $asset, ?UserInterface $user = null): bool
    {
        if (!$this->isOwner($asset, $user)) {
            return false;
        }

        $account = $asset->getFinancialAccount();
        if ($account) {
            if ($account->getIncomes()->count() > 0) {
                return false;
            }

            if ($account->getCosts()->count() > 0) {
                return false;
            }

            if ($this->annualBudgetRepository->count(['financialAccount' => $account]) > 0) {
                return false;
            }
        }

        if ($asset->getMortgage() !== null) {
            return false;
        }

        return true;
    }

    private function isOwner(Asset $asset, UserInterface $user): bool
    {
        return $asset->getUser() instanceof User
            && $user instanceof User
            && $asset->getUser()->getId() === $user->getId();
    }
}
