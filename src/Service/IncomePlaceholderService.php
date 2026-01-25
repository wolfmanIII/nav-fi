<?php

namespace App\Service;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IncomePlaceholderService
{
    public function __construct(
        private readonly ImperialDateHelper $imperialDateHelper
    ) {}

    public function resolveTemplatePath(Income $income): string
    {
        $code = $income->getIncomeCategory()?->getCode();
        return match ($code) {
            'CHARTER' => 'pdf/contracts/CHARTER.html.twig',
            'SUBSIDY' => 'pdf/contracts/SUBSIDY.html.twig',
            'PRIZE' => 'pdf/contracts/PRIZE.html.twig',
            'FREIGHT' => 'pdf/contracts/FREIGHT.html.twig',
            'SERVICES' => 'pdf/contracts/SERVICES.html.twig',
            'PASSENGERS' => 'pdf/contracts/PASSENGERS.html.twig',
            'CONTRACT' => 'pdf/contracts/CONTRACT.html.twig',
            'INTEREST' => 'pdf/contracts/INTEREST.html.twig',
            'MAIL' => 'pdf/contracts/MAIL.html.twig',
            'INSURANCE' => 'pdf/contracts/INSURANCE.html.twig',
            'SALVAGE' => 'pdf/contracts/SALVAGE.html.twig',
            'TRADE' => 'pdf/contracts/TRADE.html.twig',
            default => throw new NotFoundHttpException('No template for category'),
        };
    }

    /**
     * @return array<string, string>
     */
    public function buildPlaceholderMap(Income $income): array
    {
        $currency = 'Cr';
        $asset = $income->getAsset();
        $company = $income->getCompany();
        $detailCode = $income->getIncomeCategory()?->getCode();

        $common = [
            '{{DATE}}' => $this->formatDayYear($income->getSigningDay(), $income->getSigningYear()),
            '{{CONTRACT_ID}}' => $income->getCode(),
            '{{DEAL_ID}}' => $income->getCode(),
            '{{RECEIPT_ID}}' => $income->getCode(),
            '{{RUN_ID}}' => $income->getCode(),
            '{{CLAIM_ID}}' => $income->getCode(),
            '{{SUBSIDY_ID}}' => $income->getCode(),
            '{{PRIZE_ID}}' => $income->getCode(),
            '{{SERVICE_ID}}' => $income->getCode(),
            '{{PROGRAM_REF}}' => $this->fallback($income->getCode()),
            '{{STATUS}}' => $income->getStatus() ?: Income::STATUS_DRAFT,
            '{{VESSEL_NAME}}' => $asset?->getName() ?? '—',
            '{{CURRENCY}}' => $currency,
            '{{NOTES}}' => $this->fallback($income->getNote()),
            '{{PAYMENT}}' => $this->formatMoney($income->getAmount(), $currency),
            '{{WATERMARK}}' => $income->isCancelled() ? '<div class=\"watermark\">CANCELLED // VOID</div>' : '',
        ];

        $companyName = $company?->getName() ?? '—';
        $companyContact = $company?->getContact() ?? '—';
        $companySign = $company?->getSignLabel() ?? $companyName;

        $map = $common;

        $localLaw = $income->getLocalLaw();
        $map['{{LOCAL_LAW_SHORT_DESCRIPTION}}'] = $this->fallback($localLaw?->getShortDescription());
        $map['{{LOCAL_LAW_DESCRIPTION}}'] = $this->fallback($localLaw?->getDescription());
        $map['{{LOCAL_LAW_DISCLAIMER}}'] = $this->fallback($localLaw?->getDisclaimer());

        // Helper per brevità dentro lo switch
        $fmtDate = fn($d, $y) => $this->formatDayYear($d, $y);
        $fmtMoney = fn($a, $c = 'Cr') => $this->formatMoney($a, $c);
        $fallback = fn($v) => $this->fallback($v);

        $d = $income->getDetailsData();
        $fmtImpDate = fn(?\App\Model\ImperialDate $date) => $this->formatDayYear($date?->getDay(), $date?->getYear());

        switch ($detailCode) {
            case 'CHARTER':
                $map = array_merge($map, [
                    '{{CHARTER_ID}}' => $income->getCode(),
                    '{{CHARTERER_NAME}}' => $companyName,
                    '{{CHARTERER_CONTACT}}' => $companyContact,
                    '{{CHARTERER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{CHARTER_TYPE}}' => $fallback($d->type),
                    '{{START_DATE}}' => $fmtImpDate($d->getStartDate()),
                    '{{END_DATE}}' => $fmtImpDate($d->getEndDate()),
                    '{{AREA_OR_ROUTE}}' => $fallback($d->areaOrRoute),
                    '{{PURPOSE}}' => $fallback($d->purpose),
                    '{{MANIFEST_SUMMARY}}' => $fallback($d->manifestSummary),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{DEPOSIT}}' => $fmtMoney($d->deposit, $currency),
                    '{{EXTRAS}}' => $fallback($d->extras),
                    '{{DAMAGE_TERMS}}' => $fallback($d->damageTerms),
                    '{{CANCELLATION_TERMS}}' => $fallback($d->cancellationTerms),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                ]);
                break;

            case 'SUBSIDY':
                $map = array_merge($map, [
                    '{{SUBSIDY_ID}}' => $income->getCode(),
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_CONTACT}}' => $companyContact,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_CONTACT}}' => $asset?->getCaptain(),
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain(),
                    '{{PROGRAM_REF}}' => $fallback($d->programRef),
                    '{{ORIGIN}}' => $fallback($d->origin),
                    '{{DESTINATION}}' => $fallback($d->destination),
                    '{{START_DATE}}' => $fmtImpDate($d->getStartDate()),
                    '{{END_DATE}}' => $fmtImpDate($d->getEndDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{SERVICE_LEVEL}}' => $fallback($d->serviceLevel),
                    '{{SUBSIDY_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{MILESTONES}}' => $fallback($d->milestones),
                    '{{REPORTING_REQUIREMENTS}}' => $fallback($d->reportingRequirements),
                    '{{PROOF_REQUIREMENTS}}' => $fallback($d->proofRequirements),
                    '{{NON_COMPLIANCE_TERMS}}' => $fallback($d->nonComplianceTerms),
                    '{{CANCELLATION_TERMS}}' => $fallback($d->cancellationTerms),
                ]);
                break;

            case 'PRIZE':
                $map = array_merge($map, [
                    '{{PRIZE_ID}}' => $income->getCode(),
                    '{{CAPTOR_NAME}}' => $asset?->getName(),
                    '{{CAPTOR_CONTACT}}' => $companyContact,
                    '{{CAPTOR_SIGN}}' => $asset?->getCaptain(),
                    '{{AUTHORITY_NAME}}' => $fallback($income->getLocalLaw()?->getShortDescription()),
                    '{{AUTHORITY_SIGN}}' => $fallback($income->getLocalLaw()?->getDescription()),
                    '{{CASE_REF}}' => $fallback($d->caseRef),
                    '{{JURISDICTION}}' => $fallback($income->getLocalLaw()?->getCode()),
                    '{{SEIZURE_LOCATION}}' => $fallback(null),
                    '{{SEIZURE_DATE}}' => $fmtDate(null, null),
                    '{{LEGAL_BASIS}}' => $fallback($d->legalBasis),
                    '{{PRIZE_DESCRIPTION}}' => $fallback($d->prizeDescription),
                    '{{ESTIMATED_VALUE}}' => $fmtMoney($d->estimatedValue, $currency),
                    '{{DISPOSITION}}' => $fallback($d->disposition),
                    '{{PRIZE_AWARD}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{SHARE_SPLIT}}' => $fallback($d->shareSplit),
                    '{{AWARD_TRIGGER}}' => $fallback($d->awardTrigger),
                ]);
                break;

            case 'FREIGHT':
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{SHIPPER_NAME}}' => $companyName,
                    '{{SHIPPER_CONTACT}}' => $companyContact,
                    '{{SHIPPER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{ORIGIN}}' => $fallback($d->origin),
                    '{{DESTINATION}}' => $fallback($d->destination),
                    '{{PICKUP_DATE}}' => $fmtImpDate($d->getPickupDate()),
                    '{{DELIVERY_DATE}}' => $fmtImpDate($d->getDeliveryDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{CARGO_DESCRIPTION}}' => $fallback($d->cargoDescription),
                    '{{CARGO_QTY}}' => $fallback($d->cargoQty),
                    '{{DECLARED_VALUE}}' => $fmtMoney($d->declaredValue, $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d->liabilityLimit, $currency),
                    '{{CANCELLATION_TERMS}}' => $fallback($d->cancellationTerms),
                ]);
                break;

            case 'SERVICES':
                $map = array_merge($map, [
                    '{{SERVICE_ID}}' => $income->getCode(),
                    '{{CUSTOMER_NAME}}' => $companyName,
                    '{{CUSTOMER_CONTACT}}' => $companyContact,
                    '{{CUSTOMER_SIGN}}' => $companySign,
                    '{{PROVIDER_NAME}}' => $asset?->getName() ?? '—',
                    '{{PROVIDER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{LOCATION}}' => $fallback($d->location),
                    '{{VESSEL_NAME}}' => $asset?->getName() ?? '—',
                    '{{VESSEL_ID}}' => $income->getCode(),
                    '{{REQUESTED_BY}}' => $fallback($d->requestedBy),
                    '{{SERVICE_TYPE}}' => $fallback($d->serviceType),
                    '{{START_DATE}}' => $fmtImpDate($d->getStartDate()),
                    '{{END_DATE}}' => $fmtImpDate($d->getEndDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{WORK_SUMMARY}}' => $fallback($d->workSummary),
                    '{{PARTS_MATERIALS}}' => $fallback($d->partsMaterials),
                    '{{RISKS}}' => $fallback($d->risks),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{EXTRAS}}' => $fallback($d->extras),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d->liabilityLimit, $currency),
                    '{{CANCELLATION_TERMS}}' => $fallback($d->cancellationTerms),
                ]);
                break;

            case 'PASSENGERS':
                $map = array_merge($map, [
                    '{{TICKET_ID}}' => $income->getCode(),
                    '{{PASSENGER_NAMES}}' => $fallback($d->passengerNames),
                    '{{PASSENGER_CONTACT}}' => $fallback($d->passengerContact),
                    '{{PASSENGER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{ORIGIN}}' => $fallback($d->origin),
                    '{{DESTINATION}}' => $fallback($d->destination),
                    '{{DEPARTURE_DATE}}' => $fmtImpDate($d->getDepartureDate()),
                    '{{ARRIVAL_DATE}}' => $fmtImpDate($d->getArrivalDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{CLASS_OR_BERTH}}' => $fallback($d->classOrBerth),
                    '{{QTY}}' => (string) ($d->qty ?? '—'),
                    '{{BAGGAGE_ALLOWANCE}}' => $fallback($d->baggageAllowance),
                    '{{EXTRA_BAGGAGE}}' => $fallback($d->extraBaggage),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{REFUND_CHANGE_POLICY}}' => $fallback($d->refundChangePolicy),
                ]);
                break;

            case 'CONTRACT':
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{PATRON_NAME}}' => $companyName,
                    '{{PATRON_CONTACT}}' => $companyContact,
                    '{{PATRON_SIGN}}' => $companySign,
                    '{{CONTRACTOR_NAME}}' => $asset?->getName() ?? '—',
                    '{{CONTRACTOR_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{JOB_TYPE}}' => $fallback($d->jobType),
                    '{{LOCATION}}' => $fallback($d->location),
                    '{{OBJECTIVE}}' => $fallback($d->objective),
                    '{{START_DATE}}' => $fmtImpDate($d->getStartDate()),
                    '{{DEADLINE}}' => $fmtImpDate($d->getDeadlineDate()),
                    '{{SUCCESS_CONDITION}}' => $fallback($d->successCondition),
                    '{{PAY_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{BONUS}}' => $fallback($d->bonus),
                    '{{EXPENSES_POLICY}}' => $fallback($d->expensesPolicy),
                    '{{DEPOSIT}}' => $fmtMoney($d->deposit, $currency),
                    '{{RESTRICTIONS}}' => $fallback($d->restrictions),
                    '{{CONFIDENTIALITY_LEVEL}}' => $fallback($d->confidentialityLevel),
                    '{{FAILURE_TERMS}}' => $fallback($d->failureTerms),
                    '{{CANCELLATION_TERMS}}' => $fallback($d->cancellationTerms),
                ]);
                break;

            case 'INTEREST':
                $map = array_merge($map, [
                    '{{RECEIPT_ID}}' => $income->getCode(),
                    '{{PAYER_NAME}}' => $companyName,
                    '{{PAYEE_NAME}}' => $asset?->getName() ?? '—',
                    '{{PAYEE_CONTACT}}' => $companyContact,
                    '{{ACCOUNT_REF}}' => $fallback($d->accountRef),
                    '{{INSTRUMENT}}' => $fallback($d->instrument),
                    '{{PRINCIPAL}}' => $fmtMoney($d->principal, $currency),
                    '{{INTEREST_RATE}}' => $fallback($d->interestRate),
                    '{{START_DATE}}' => $fmtImpDate($d->getStartDate()),
                    '{{END_DATE}}' => $fmtImpDate($d->getEndDate()),
                    '{{CALC_METHOD}}' => $fallback($d->calcMethod),
                    '{{INTEREST_EARNED}}' => $fmtMoney($d->interestEarned, $currency),
                    '{{NET_PAID}}' => $fmtMoney($d->netPaid, $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{DISPUTE_WINDOW}}' => $fallback($d->disputeWindow),
                ]);
                break;

            case 'MAIL':
                $map = array_merge($map, [
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{VESSEL_NAME}}' => $asset?->getName() ?? '—',
                    '{{ORIGIN}}' => $fallback($d->origin),
                    '{{DESTINATION}}' => $fallback($d->destination),
                    '{{DISPATCH_DATE}}' => $fmtImpDate($d->getDispatchDate()),
                    '{{DELIVERY_DATE}}' => $fmtImpDate($d->getDeliveryDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{MAIL_TYPE}}' => $fallback($d->mailType),
                    '{{PACKAGE_COUNT}}' => (string) ($d->packageCount ?? '—'),
                    '{{TOTAL_MASS}}' => $fmtMoney($d->totalMass, "dT"),
                    '{{SECURITY_LEVEL}}' => $fallback($d->securityLevel),
                    '{{SEAL_CODES}}' => $fallback($d->sealCodes),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{MAIL_FEE}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d->liabilityLimit, $currency),
                    '{{PROOF_OF_DELIVERY}}' => $fallback($d->proofOfDelivery),
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                ]);
                break;

            case 'INSURANCE':
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{INSURER_NAME}}' => $companyName,
                    '{{INSURED_NAME}}' => $asset?->getName() ?? '—',
                    '{{INSURED_CONTACT}}' => $companyContact,
                    '{{POLICY_NUMBER}}' => $income->getCode(),
                    '{{INCIDENT_REF}}' => $fallback($d->incidentRef),
                    '{{INCIDENT_DATE}}' => $fmtImpDate($d->getIncidentDate()),
                    '{{INCIDENT_LOCATION}}' => $fallback($d->incidentLocation),
                    '{{INCIDENT_CAUSE}}' => $fallback($d->incidentCause),
                    '{{LOSS_TYPE}}' => $fallback($d->lossType),
                    '{{VERIFIED_LOSS}}' => $fmtMoney($d->verifiedLoss, $currency),
                    '{{PAYOUT_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{DEDUCTIBLE}}' => $fmtMoney($d->deductible, $currency),
                    '{{COVERAGE_NOTES}}' => $fallback($d->coverageNotes),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{ACCEPTANCE_EFFECT}}' => $fallback($d->acceptanceEffect),
                    '{{SUBROGATION_TERMS}}' => $fallback($d->subrogationTerms),
                    '{{NOTES}}' => $fallback($income->getNote()),
                    '{{INSURER_SIGN}}' => $companySign,
                    '{{INSURED_SIGN}}' => $asset?->getCaptain() ?? '—',
                ]);
                break;

            case 'SALVAGE':
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{SALVAGE_TEAM_NAME}}' => $asset?->getName(),
                    '{{SALVAGE_CONTACT}}' => $companyContact,
                    '{{SALVAGE_SIGN}}' => $asset?->getCaptain(),
                    '{{AUTHORITY_OR_OWNER_NAME}}' => $company,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                    '{{CASE_REF}}' => $fallback($d->caseRef),
                    '{{SITE_LOCATION}}' => $fallback($d->siteLocation),
                    '{{SOURCE}}' => $fallback($d->source),
                    '{{START_DATE}}' => $fmtDate(null, null),
                    '{{END_DATE}}' => $fmtDate(null, null),
                    '{{RECOVERED_ITEMS_SUMMARY}}' => $fallback($d->recoveredItemsSummary),
                    '{{QTY_VALUE}}' => $fmtMoney($d->qtyValue, $currency),
                    '{{HAZARDS}}' => $fallback($d->hazards),
                    '{{SALVAGE_AWARD}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{SPLIT_TERMS}}' => $fallback($d->splitTerms),
                    '{{RIGHTS_BASIS}}' => $fallback($d->rightsBasis),
                    '{{AWARD_TRIGGER}}' => $fallback($d->awardTrigger),
                    '{{DISPUTE_PROCESS}}' => $fallback($d->disputeProcess),
                    '{{NOTES}}' => $fallback($income->getNote()),
                ]);
                if ($income->getAsset()) {
                    $map['{{ asset_name }}'] = $fallback($income->getAsset()->getName());
                    $map['{{ asset_code }}'] = $fallback((string) $income->getAsset()->getCode());
                    $map['{{ asset_type }}'] = $fallback($income->getAsset()->getType());
                    $map['{{ asset_class }}'] = $fallback($income->getAsset()->getClass());
                    $map['{{ asset_hull_tons }}'] = $fmtMoney((string) $income->getAsset()->getHullTons(), '') . ' dt';
                } else {
                    $map['{{ asset_name }}'] = 'NA';
                    $map['{{ asset_code }}'] = 'NA';
                    $map['{{ asset_type }}'] = 'NA';
                    $map['{{ asset_class }}'] = 'NA';
                    $map['{{ asset_hull_tons }}'] = '0 dt';
                }
                break;

            case 'TRADE':
                $map = array_merge($map, [
                    '{{DEAL_ID}}' => $income->getCode(),
                    '{{BUYER_NAME}}' => $companyName,
                    '{{BUYER_CONTACT}}' => $companyContact,
                    '{{BUYER_SIGN}}' => $companySign,
                    '{{SELLER_NAME}}' => $asset?->getName() ?? '—',
                    '{{SELLER_CONTACT}}' => $companyContact,
                    '{{SELLER_SIGN}}' => $asset?->getName() ?? '—',
                    '{{LOCATION}}' => $fallback($d->location),
                    '{{GOODS_DESCRIPTION}}' => $fallback($d->goodsDescription),
                    '{{QTY}}' => (string) ($d->qty ?? '—'),
                    '{{GRADE}}' => $fallback($d->grade),
                    '{{BATCH_IDS}}' => $fallback($d->batchIds),
                    '{{UNIT_PRICE}}' => $fmtMoney($d->unitPrice, $currency),
                    '{{TOTAL_PRICE}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d->paymentTerms),
                    '{{DELIVERY_METHOD}}' => $fallback($d->deliveryMethod),
                    '{{DELIVERY_DATE}}' => $fmtImpDate($d->getDeliveryDate()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d->deliveryProofRef),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtImpDate($d->getDeliveryProofDate()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d->deliveryProofReceivedBy),
                    '{{TRANSFER_POINT}}' => $fallback($d->transferPoint),
                    '{{TRANSFER_CONDITION}}' => $fallback($d->transferCondition),
                    '{{AS_IS_OR_WARRANTY}}' => $fallback($d->asIsOrWarranty),
                    '{{WARRANTY}}' => $fallback($d->warrantyText),
                    '{{CLAIM_WINDOW}}' => $fallback($d->claimWindow),
                    '{{CANCEL_RETURN_POLICY}}' => $fallback($d->returnPolicy),
                    '{{NOTES}}' => $fallback($income->getNote()),
                ]);
                break;
        }

        return $map;
    }

    private function formatDayYear(?int $day, ?int $year): string
    {
        return $this->imperialDateHelper->format($day, $year) ?? '—';
    }

    private function formatMoney(?string $amount, string $currency): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }
        return number_format((float) $amount, 2, ',', '.') . ' ' . $currency;
    }

    private function fallback(?string $value): string
    {
        return $value === null || $value === '' ? '—' : $value;
    }
}
