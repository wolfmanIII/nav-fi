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

        // 2. Prevent deletion if Route is active and waypoint is active or first
        if ($route->isActive()) {
            if ($waypoint->isActive()) {
                return false;
            }
            if ($waypoint->getPosition() === 1) {
                return false;
            }
        }

        // 3. Prevent deletion of starting waypoint (position 1) - General rule (unless route is not active and we allow editing start?)
        // Actually, start hex is usually fixed in Route entity, but waypoint list mirrors it.
        // Let's keep the general rule: position 1 cannot be deleted via list if it breaks the route structure?
        // But for now, user specifically asked to protect active/first WHEN active.
        // However, the original code already had `return $waypoint->getPosition() !== 1;`.
        // I will keep it but refine it.
        
        return $waypoint->getPosition() !== 1;
    }
}
