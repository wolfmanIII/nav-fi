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
    public function yearAttr(array $attr = []): array
    {
        return array_merge([
            'min' => $this->yearMin,
            'max' => $this->yearMax,
        ], $attr);
    }
}
