<?php

namespace App\Service\Cost;

use App\DTO\CostDetailItem;
use App\Entity\Cost;
use Doctrine\ORM\EntityManagerInterface;

class CostDetailManager
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @return CostDetailItem[]
     */
    public function getDetails(Cost $cost): array
    {
        $data = $cost->getDetailItems();
        $dtos = [];

        foreach ($data as $item) {
            if (is_array($item)) {
                $dtos[] = CostDetailItem::fromArray($item);
            }
        }

        return $dtos;
    }

    public function getTotalQuantity(Cost $cost): float
    {
        $details = $this->getDetails($cost);
        $qty = 0.0;
        foreach ($details as $detail) {
            $qty += $detail->quantity;
        }
        return $qty;
    }

    public function markAsSold(Cost $cost): void
    {
        $details = $this->getDetails($cost);
        $updatedDetails = [];

        foreach ($details as $detail) {
            // Pattern DTO immutabile: crea una nuova istanza con stato aggiornato
            $updatedDetails[] = $detail->withSoldStatus(true)->toArray();
        }

        $cost->setDetailItems($updatedDetails);

        // Persistiamo esplicitamente qui poiché è un'azione di business logic
        $this->em->persist($cost);
        $this->em->flush();
    }
}
