<?php

namespace App\Security\Voter;

use App\Entity\RouteWaypoint;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class RouteWaypointVoter extends Voter
{
    public const DELETE = 'waypoint_delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DELETE && $subject instanceof RouteWaypoint;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var RouteWaypoint $waypoint */
        $waypoint = $subject;

        return match ($attribute) {
            self::DELETE => $this->canDelete($waypoint, $user),
            default => false,
        };
    }

    private function canDelete(RouteWaypoint $waypoint, User $user): bool
    {
        // 1. Verify ownership
        $route = $waypoint->getRoute();
        if (!$route) {
            return false;
        }

        $asset = $route->getAsset();
        if (!$asset || $asset->getUser()->getId() !== $user->getId()) {
            return false;
        }

        // 2. Prevent deletion of starting waypoint (position 1)
        return $waypoint->getPosition() !== 1;
    }
}
