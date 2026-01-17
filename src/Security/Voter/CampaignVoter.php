<?php

namespace App\Security\Voter;

use App\Entity\Campaign;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CampaignVoter extends Voter
{
    public const CREATE = 'campaign_create';
    public const VIEW = 'campaign_view';
    public const EDIT = 'campaign_edit';
    public const DELETE = 'campaign_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::CREATE, self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return $subject instanceof Campaign;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Campaign $campaign */
        $campaign = $subject;

        return match ($attribute) {
            self::CREATE => $this->canCreate($campaign),
            self::VIEW => $this->canView($campaign, $user),
            self::EDIT => $this->canEdit($campaign, $user),
            self::DELETE => $this->canDelete($campaign, $user),
            default => false,
        };
    }

    private function canCreate(Campaign $campaign): bool
    {
        return $campaign->getId() === null;
    }

    private function canView(Campaign $campaign, User $user): bool
    {
        return $this->isOwner($campaign, $user);
    }

    private function canEdit(Campaign $campaign, User $user): bool
    {
        return $this->isOwner($campaign, $user);
    }

    private function canDelete(Campaign $campaign, User $user): bool
    {
        if (!$this->isOwner($campaign, $user)) {
            return false;
        }

        return $campaign->getAssets()->count() === 0;
    }

    private function isOwner(Campaign $campaign, User $user): bool
    {
        return $campaign->getUser() instanceof User
            && $campaign->getUser()->getId() === $user->getId();
    }
}
