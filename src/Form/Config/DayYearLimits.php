<?php

namespace App\Form\Config;

class DayYearLimits
{
    public function __construct(
        private readonly int $dayMin,
        private readonly int $dayMax,
        private readonly int $yearMin,
        private readonly int $yearMax,
    ) {
    }

    /**
     * Restituisce gli attributi HTML per un campo "Day".
     *
     * @param array<string, mixed> $attr
     * @return array<string, mixed>
     */
    public function dayAttr(array $attr = []): array
    {
        return array_merge([
            'min' => $this->dayMin,
            'max' => $this->dayMax,
        ], $attr);
    }

    /**
     * Restituisce gli attributi HTML per un campo "Year".
     *
     * @param array<string, mixed> $attr
     * @return array<string, mixed>
     */
    public function yearAttr(array $attr = [], ?int $campaignStartYear = null): array
    {
        $min = $this->yearMin;
        if ($campaignStartYear !== null) {
            $min = max($this->yearMin, $campaignStartYear);
        }

        return array_merge([
            'min' => $min,
            'max' => $this->yearMax,
            'data-year-limit-target' => 'yearInput',
        ], $attr);
    }

    public function getYearMin(): int
    {
        return $this->yearMin;
    }

    public function getYearMax(): int
    {
        return $this->yearMax;
    }
}
