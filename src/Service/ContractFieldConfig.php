<?php

namespace App\Service;

/**
 * Fornisce la mappa dei campi opzionali per ciascuna categoria di contratto (IncomeCategory.code).
 * I campi elencati sono quelli da esporre dinamicamente nella form a seconda della categoria scelta.
 */
class ContractFieldConfig
{
    /**
     * Campi comuni che esistono già su Income e che possono essere sempre esposti.
     *
     * @var string[]
     */
    private array $baseFields = [
        'title',
        'amount',
        'signingDay',
        'signingYear',
        'paymentDay',
        'paymentYear',
        'expirationDay',
        'expirationYear',
        'cancelDay',
        'cancelYear',
        'note',
        'company',
        'ship',
        'localLaw',
        'incomeCategory',
    ];

    /**
     * Mappa categoria → elenco campi opzionali aggiuntivi da mostrare.
     *
     * @var array<string, string[]>
     */
    private array $optionalFields = [
        'CHARTER' => [
            'areaOrRoute', 'startDay', 'startYear', 'endDay', 'endYear',
            'purpose', 'manifestSummary', 'paymentTerms', 'deposit', 'extras',
            'damageTerms', 'cancellationTerms',
        ],
        'SUBSIDY' => [
            'programRef', 'origin', 'destination', 'startDay', 'startYear',
            'endDay', 'endYear', 'serviceLevel', 'subsidyAmount', 'paymentTerms',
            'milestones', 'reportingRequirements', 'nonComplianceTerms',
            'proofRequirements', 'cancellationTerms',
        ],
        'PRIZE' => [
            'prizeId', 'caseRef', 'jurisdiction', 'legalBasis',
            'prizeDescription', 'estimatedValue', 'disposition',
            'paymentTerms', 'shareSplit', 'awardTrigger',
        ],
        'FREIGHT' => [
            'origin', 'destination', 'pickupDay', 'pickupYear',
            'deliveryDay', 'deliveryYear', 'cargoDescription', 'cargoQty',
            'declaredValue', 'paymentTerms', 'liabilityLimit', 'cancellationTerms',
        ],
        'SERVICES' => [
            'location', 'vesselId', 'serviceType', 'requestedBy',
            'startDay', 'startYear', 'endDay', 'endYear', 'workSummary',
            'partsMaterials', 'risks', 'paymentTerms', 'extras',
            'liabilityLimit', 'cancellationTerms',
        ],
        'PASSENGERS' => [
            'origin', 'destination', 'departureDay', 'departureYear',
            'arrivalDay', 'arrivalYear', 'classOrBerth', 'qty', 'passengerNames',
            'passengerContact', 'baggageAllowance', 'extraBaggage',
            'paymentTerms', 'refundChangePolicy',
        ],
        'CONTRACT' => [
            'jobType', 'objective', 'location', 'successCondition',
            'startDay', 'startYear', 'deadlineDay', 'deadlineYear',
            'paymentTerms', 'bonus', 'expensesPolicy', 'deposit', 'restrictions',
            'confidentialityLevel', 'failureTerms', 'cancellationTerms',
        ],
        'INTEREST' => [
            'receiptId', 'accountRef', 'instrument', 'principal', 'interestRate',
            'startDay', 'startYear', 'endDay', 'endYear', 'calcMethod',
            'interestEarned', 'netPaid', 'paymentTerms', 'disputeWindow',
        ],
        'MAIL' => [
            'runId', 'authorityRef', 'origin', 'destination', 'dispatchDay',
            'dispatchYear', 'deliveryDay', 'deliveryYear', 'mailType',
            'packageCount', 'totalMass', 'securityLevel', 'sealCodes',
            'paymentTerms', 'proofOfDelivery', 'liabilityLimit',
        ],
        'INSURANCE' => [
            'claimId', 'policyNumber', 'incidentRef', 'incidentDay', 'incidentYear',
            'incidentLocation', 'incidentCause', 'lossType', 'verifiedLoss',
            'deductible', 'paymentTerms', 'acceptanceEffect',
            'subrogationTerms', 'coverageNotes',
        ],
        'SALVAGE' => [
            'claimId', 'caseRef', 'source', 'siteLocation', 'recoveredItemsSummary',
            'qtyValue', 'hazards', 'paymentTerms', 'splitTerms', 'rightsBasis',
            'awardTrigger', 'disputeProcess',
        ],
        'TRADE' => [
            'location', 'transferPoint', 'transferCondition',
            'goodsDescription', 'qty', 'grade', 'batchIds', 'unitPrice',
            'paymentTerms', 'deliveryMethod', 'deliveryDay', 'deliveryYear',
            'asIsOrWarranty', 'warrantyText', 'claimWindow', 'returnPolicy',
        ],
    ];

    /**
     * Restituisce i campi di base sempre disponibili.
     *
     * @return string[]
     */
    public function getBaseFields(): array
    {
        return $this->baseFields;
    }

    /**
     * Restituisce i campi opzionali previsti per la categoria indicata.
     *
     * @param string|null $categoryCode IncomeCategory.code
     *
     * @return string[]
     */
    public function getOptionalFields(?string $categoryCode): array
    {
        if ($categoryCode === null) {
            return [];
        }

        return $this->optionalFields[$categoryCode] ?? [];
    }

    /**
     * Restituisce l’intera mappa categoria → campi opzionali.
     *
     * @return array<string, string[]>
     */
    public function getAll(): array
    {
        return $this->optionalFields;
    }
}
