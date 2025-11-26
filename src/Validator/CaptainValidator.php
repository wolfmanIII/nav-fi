<?php

namespace App\Validator;

use App\Entity\Crew;
use App\Repository\CrewRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CaptainValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CrewRepository $crewRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Captain) {
            throw new UnexpectedTypeException($constraint, Captain::class);
        }

        if (!$value instanceof Crew) {
            return; // non dovrebbe accadere
        }

        // Se NON è capitano, nessun controllo
        if (!$value->isCaptain()) {
            return;
        }

        $ship = $value->getShip();

        if ($ship === null) {
            return;
        }

        // Cerca se esiste già un capitano su quella nave
        $existingCaptain = $this->crewRepository->findOneByCaptainOnShip($ship, $value);

        if ($existingCaptain !== null) {

            $shipName = $ship->getName() ?? ('ID '.$ship->getId());
            $captainName = trim(($existingCaptain->getName() ?? '') . ' ' . ($existingCaptain->getSurname() ?? ''));

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ ship }}', $shipName)
                ->setParameter('{{ name }}', $captainName)
                ->atPath('shipRoles')
                ->addViolation();
        }
    }
}
