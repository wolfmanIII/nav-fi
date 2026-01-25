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
        'asset',
        'localLaw',
        'incomeCategory',
    ];

    /**
     * Tipi di dato per il casting automatico nel modello ed eventualmente nel form.
     */
    private array $fieldTypes = [
        // Interi
        'qty' => 'int',
        'pax' => 'int',
        'packageCount' => 'int',
        'startDay' => 'int',
        'startYear' => 'int',
        'endDay' => 'int',
        'endYear' => 'int',
        'pickupDay' => 'int',
        'pickupYear' => 'int',
        'deliveryDay' => 'int',
        'deliveryYear' => 'int',
        'deliveryProofDay' => 'int',
        'deliveryProofYear' => 'int',
        'departureDay' => 'int',
        'departureYear' => 'int',
        'arrivalDay' => 'int',
        'arrivalYear' => 'int',
        'dispatchDay' => 'int',
        'dispatchYear' => 'int',
        'incidentDay' => 'int',
        'incidentYear' => 'int',
        'deadlineDay' => 'int',
        'deadlineYear' => 'int',

        // Valuta / Decimali (mantentuti come stringhe per BC Math)
        'amount' => 'money',
        'unitPrice' => 'money',
        'deposit' => 'money',
        'bonus' => 'money',
        'subsidyAmount' => 'money',
        'estimatedValue' => 'money',
        'declaredValue' => 'money',
        'liabilityLimit' => 'money',
        'principal' => 'money',
        'interestRate' => 'money',
        'interestEarned' => 'money',
        'netPaid' => 'money',
        'verifiedLoss' => 'money',
        'deductible' => 'money',
        'qtyValue' => 'money',

        // Testo lungo
        'note' => 'longtext',
        'summary' => 'longtext',
        'policy' => 'longtext',
        'terms' => 'longtext',
        'requirements' => 'longtext',
        'description' => 'longtext',
        'objective' => 'longtext',
        'workSummary' => 'longtext',
        'partsMaterials' => 'longtext',
        'risks' => 'longtext',
        'hazards' => 'longtext',
        'recoveredItemsSummary' => 'longtext',
        'legalBasis' => 'longtext',
        'warrantyText' => 'longtext',
        'claimWindow' => 'longtext',
        'returnPolicy' => 'longtext',
        'manifestSummary' => 'longtext',
        'milestones' => 'longtext',
        'reportingRequirements' => 'longtext',
        'nonComplianceTerms' => 'longtext',
        'proofRequirements' => 'longtext',
        'cancellationTerms' => 'longtext',
        'failureTerms' => 'longtext',
        'paymentTerms' => 'longtext',
        'expensesPolicy' => 'longtext',
        'restrictions' => 'longtext',
        'confidentialityLevel' => 'longtext',
        'batchIds' => 'longtext',
    ];

    private array $optionalFields = [
        'CHARTER' => [
            'areaOrRoute',
            'purpose',
            'manifestSummary',
            'startDate',
            'endDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'paymentTerms',
            'deposit',
            'extras',
            'damageTerms',
            'cancellationTerms',
        ],
        'SUBSIDY' => [
            'programRef',
            'origin',
            'destination',
            'startDate',
            'endDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'serviceLevel',
            'subsidyAmount',
            'paymentTerms',
            'milestones',
            'reportingRequirements',
            'nonComplianceTerms',
            'proofRequirements',
            'cancellationTerms',
        ],
        'PRIZE' => [
            'caseRef',
            'legalBasis',
            'prizeDescription',
            'estimatedValue',
            'disposition',
            'paymentTerms',
            'shareSplit',
            'awardTrigger',
        ],
        'FREIGHT' => [
            'origin',
            'destination',
            'pickupDate',
            'deliveryDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'cargoDescription',
            'cargoQty',
            'declaredValue',
            'paymentTerms',
            'liabilityLimit',
            'cancellationTerms',
        ],
        'SERVICES' => [
            'location',
            'serviceType',
            'requestedBy',
            'startDate',
            'endDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'workSummary',
            'partsMaterials',
            'risks',
            'paymentTerms',
            'extras',
            'liabilityLimit',
            'cancellationTerms',
        ],
        'PASSENGERS' => [
            'origin',
            'destination',
            'departureDate',
            'arrivalDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'classOrBerth',
            'qty',
            'passengerNames',
            'passengerContact',
            'baggageAllowance',
            'extraBaggage',
            'paymentTerms',
            'refundChangePolicy',
        ],
        'CONTRACT' => [
            'jobType',
            'objective',
            'location',
            'successCondition',
            'startDate',
            'deadlineDate',
            'paymentTerms',
            'bonus',
            'expensesPolicy',
            'deposit',
            'restrictions',
            'confidentialityLevel',
            'failureTerms',
            'cancellationTerms',
        ],
        'INTEREST' => [
            'accountRef',
            'instrument',
            'principal',
            'interestRate',
            'startDate',
            'endDate',
            'calcMethod',
            'interestEarned',
            'netPaid',
            'paymentTerms',
            'disputeWindow',
        ],
        'MAIL' => [
            'origin',
            'destination',
            'dispatchDate',
            'deliveryDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'mailType',
            'packageCount',
            'totalMass',
            'securityLevel',
            'sealCodes',
            'paymentTerms',
            'proofOfDelivery',
            'liabilityLimit',
        ],
        'INSURANCE' => [
            'incidentRef',
            'incidentDate',
            'incidentLocation',
            'incidentCause',
            'lossType',
            'verifiedLoss',
            'deductible',
            'paymentTerms',
            'acceptanceEffect',
            'subrogationTerms',
            'coverageNotes',
        ],
        'SALVAGE' => [
            'caseRef',
            'source',
            'siteLocation',
            'recoveredItemsSummary',
            'qtyValue',
            'hazards',
            'paymentTerms',
            'splitTerms',
            'rightsBasis',
            'awardTrigger',
            'disputeProcess',
        ],
        'TRADE' => [
            'location',
            'transferPoint',
            'transferCondition',
            'goodsDescription',
            'qty',
            'grade',
            'batchIds',
            'unitPrice',
            'paymentTerms',
            'deliveryMethod',
            'deliveryDate',
            'deliveryProofRef',
            'deliveryProofDate',
            'deliveryProofReceivedBy',
            'asIsOrWarranty',
            'warrantyText',
            'claimWindow',
            'returnPolicy',
            'purchaseCost',
        ],
    ];

    /**
     * Placeholder opzionali per categoria e campo.
     *
     * @var array<string, array<string, string>>
     */
    private array $placeholders = [
        'CHARTER' => [
            'deliveryProofRef' => 'Ref / docket',
        ],
        'SUBSIDY' => [
            'programRef' => 'Program ref',
            'deliveryProofRef' => 'Ref / docket',
        ],
        'PRIZE' => [
            'caseRef' => 'Case ref',
        ],
        'FREIGHT' => [
            'deliveryProofRef' => 'Ref / docket',
        ],
        'SERVICES' => [
            'deliveryProofRef' => 'Ref / docket',
        ],
        'PASSENGERS' => [
            'deliveryProofRef' => 'Ref / docket',
        ],
        'CONTRACT' => [
            'jobType' => 'Job type',
        ],
        'INTEREST' => [
            'accountRef' => 'Account ref',
        ],
        'MAIL' => [
            'deliveryProofRef' => 'Ref / docket',
            'sealCodes' => 'Comma-separated',
        ],
        'INSURANCE' => [
            'incidentRef' => 'Incident ref',
        ],
        'SALVAGE' => [
            'caseRef' => 'Case ref',
        ],
        'TRADE' => [
            'transferPoint' => 'Port/berth',
            'deliveryProofRef' => 'Ref / docket',
            'batchIds' => 'Comma-separated',
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
     * Restituisce i placeholder opzionali per una categoria.
     *
     * @return array<string, string>
     */
    public function getPlaceholders(?string $categoryCode): array
    {
        if ($categoryCode === null) {
            return [];
        }

        return $this->placeholders[$categoryCode] ?? [];
    }

    /**
     * Restituisce la mappa completa dei tipi di campo.
     *
     * @return array<string, string>
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    /**
     * Restituisce il tipo di un singolo campo (string di default).
     */
    public function getFieldType(string $fieldName): string
    {
        return $this->fieldTypes[$fieldName] ?? 'string';
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
