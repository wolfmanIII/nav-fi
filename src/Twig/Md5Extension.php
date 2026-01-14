<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Md5Extension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('md5', [$this, 'md5Hash']),
        ];
    }

    public function md5Hash(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return md5($value);
    }
}
