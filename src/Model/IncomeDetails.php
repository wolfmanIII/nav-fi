<?php

namespace App\Model;

use App\Model\ImperialDate;

/**
 * Smart Wrapper per i dettagli JSON degli Income.
 * Fornisce accesso tipizzato e helper per le date imperiali.
 */
class IncomeDetails
{
    public function __construct(private array $data = []) {}

    /**
     * Restituisce l'array grezzo dei dati.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Accesso generico con fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Restituisce un valore come stringa.
     */
    public function getString(string $key, string $default = ''): string
    {
        return (string)$this->get($key, $default);
    }

    /**
     * Restituisce un valore come intero.
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int)$this->get($key, $default);
    }

    /**
     * Restituisce un valore come stringa decimale (per BC Math).
     */
    public function getMoney(string $key, string $default = '0.00'): string
    {
        $val = $this->get($key, $default);
        if ($val === null || $val === '') return $default;
        return (string)$val;
    }

    /**
     * Helper per costruire un oggetto ImperialDate da due chiavi (day/year).
     */
    public function getDate(string $dayKey, string $yearKey): ?ImperialDate
    {
        $day = $this->get($dayKey);
        $year = $this->get($yearKey);

        if ($day === null && $year === null) {
            return null;
        }

        return new ImperialDate($year, $day);
    }

    // --- Helper specifici per le date comuni ---

    public function getStartDate(): ?ImperialDate
    {
        return $this->getDate('startDay', 'startYear');
    }
    public function getEndDate(): ?ImperialDate
    {
        return $this->getDate('endDay', 'endYear');
    }
    public function getPickupDate(): ?ImperialDate
    {
        return $this->getDate('pickupDay', 'pickupYear');
    }
    public function getDeliveryDate(): ?ImperialDate
    {
        return $this->getDate('deliveryDay', 'deliveryYear');
    }
    public function getDeliveryProofDate(): ?ImperialDate
    {
        return $this->getDate('deliveryProofDay', 'deliveryProofYear');
    }
    public function getDepartureDate(): ?ImperialDate
    {
        return $this->getDate('departureDay', 'departureYear');
    }
    public function getArrivalDate(): ?ImperialDate
    {
        return $this->getDate('arrivalDay', 'arrivalYear');
    }
    public function getDispatchDate(): ?ImperialDate
    {
        return $this->getDate('dispatchDay', 'dispatchYear');
    }
    public function getIncidentDate(): ?ImperialDate
    {
        return $this->getDate('incidentDay', 'incidentYear');
    }
    public function getDeadlineDate(): ?ImperialDate
    {
        return $this->getDate('deadlineDay', 'deadlineYear');
    }

    /**
     * Magic getter per accedere alle proprietà come $details->qty.
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Verifica se una chiave esiste.
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
}
