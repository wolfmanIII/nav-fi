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

        $asset = $value->getAsset();

        if ($asset === null) {
            return;
        }

        // Cerca se esiste già un capitano su quella nave
        $existingCaptain = $this->crewRepository->findOneByCaptainOnAsset($asset, $value);

        if ($existingCaptain !== null) {

            $assetName = $asset->getName() ?? ('ID ' . $asset->getId());
            $captainName = trim(($existingCaptain->getName() ?? '') . ' ' . ($existingCaptain->getSurname() ?? ''));

            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ asset }}', $assetName)
                ->setParameter('{{ name }}', $captainName)
                ->atPath('assetRoles')
                ->addViolation();
        }
    }
}
