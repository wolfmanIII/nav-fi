<?php

namespace App\Twig;

use App\Entity\Route;
use App\Service\RouteMathHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RouteExtension extends AbstractExtension
{
    public function __construct(
        private readonly RouteMathHelper $routeMathHelper
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('route_total_fuel', [$this, 'getTotalFuel']),
        ];
    }

    public function getTotalFuel(Route $route): ?string
    {
        return $this->routeMathHelper->totalRequiredFuel($route);
    }
}
