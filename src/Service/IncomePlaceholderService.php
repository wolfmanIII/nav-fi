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

        switch ($detailCode) {
            case 'CHARTER':
                $d = $income->getCharterDetails();
                $map = array_merge($map, [
                    '{{CHARTER_ID}}' => $income->getCode(),
                    '{{CHARTERER_NAME}}' => $companyName,
                    '{{CHARTERER_CONTACT}}' => $companyContact,
                    '{{CHARTERER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{CHARTER_TYPE}}' => $fallback($d?->getType()),
                    '{{START_DATE}}' => $fmtDate($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $fmtDate($d?->getEndDay(), $d?->getEndYear()),
                    '{{AREA_OR_ROUTE}}' => $fallback($d?->getAreaOrRoute()),
                    '{{PURPOSE}}' => $fallback($d?->getPurpose()),
                    '{{MANIFEST_SUMMARY}}' => $fallback($d?->getManifestSummary()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{DEPOSIT}}' => $fmtMoney($d?->getDeposit(), $currency),
                    '{{EXTRAS}}' => $fallback($d?->getExtras()),
                    '{{DAMAGE_TERMS}}' => $fallback($d?->getDamageTerms()),
                    '{{CANCELLATION_TERMS}}' => $fallback($d?->getCancellationTerms()),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                ]);
                break;

            case 'SUBSIDY':
                $d = $income->getSubsidyDetails();
                $map = array_merge($map, [
                    '{{SUBSIDY_ID}}' => $income->getCode(),
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_CONTACT}}' => $companyContact,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_CONTACT}}' => $asset?->getCaptain(),
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain(),
                    '{{PROGRAM_REF}}' => $fallback($d?->getProgramRef()),
                    '{{ORIGIN}}' => $fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $fallback($d?->getDestination()),
                    '{{START_DATE}}' => $fmtDate($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $fmtDate($d?->getEndDay(), $d?->getEndYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{SERVICE_LEVEL}}' => $fallback($d?->getServiceLevel()),
                    '{{SUBSIDY_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{MILESTONES}}' => $fallback($d?->getMilestones()),
                    '{{REPORTING_REQUIREMENTS}}' => $fallback($d?->getReportingRequirements()),
                    '{{PROOF_REQUIREMENTS}}' => $fallback($d?->getProofRequirements()),
                    '{{NON_COMPLIANCE_TERMS}}' => $fallback($d?->getNonComplianceTerms()),
                    '{{CANCELLATION_TERMS}}' => $fallback($d?->getCancellationTerms()),
                ]);
                break;

            case 'PRIZE':
                $d = $income->getPrizeDetails();
                $map = array_merge($map, [
                    '{{PRIZE_ID}}' => $income->getCode(),
                    '{{CAPTOR_NAME}}' => $asset?->getName(),
                    '{{CAPTOR_CONTACT}}' => $companyContact,
                    '{{CAPTOR_SIGN}}' => $asset?->getCaptain(),
                    '{{AUTHORITY_NAME}}' => $fallback($income->getLocalLaw()?->getShortDescription()),
                    '{{AUTHORITY_SIGN}}' => $fallback($income->getLocalLaw()?->getDescription()),
                    '{{CASE_REF}}' => $fallback($d?->getCaseRef()),
                    '{{JURISDICTION}}' => $fallback($income->getLocalLaw()?->getCode()),
                    '{{SEIZURE_LOCATION}}' => $fallback(null),
                    '{{SEIZURE_DATE}}' => $fmtDate(null, null),
                    '{{LEGAL_BASIS}}' => $fallback($d?->getLegalBasis()),
                    '{{PRIZE_DESCRIPTION}}' => $fallback($d?->getPrizeDescription()),
                    '{{ESTIMATED_VALUE}}' => $fmtMoney($d?->getEstimatedValue(), $currency),
                    '{{DISPOSITION}}' => $fallback($d?->getDisposition()),
                    '{{PRIZE_AWARD}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{SHARE_SPLIT}}' => $fallback($d?->getShareSplit()),
                    '{{AWARD_TRIGGER}}' => $fallback($d?->getAwardTrigger()),
                ]);
                break;

            case 'FREIGHT':
                $d = $income->getFreightDetails();
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{SHIPPER_NAME}}' => $companyName,
                    '{{SHIPPER_CONTACT}}' => $companyContact,
                    '{{SHIPPER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{ORIGIN}}' => $fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $fallback($d?->getDestination()),
                    '{{PICKUP_DATE}}' => $fmtDate($d?->getPickupDay(), $d?->getPickupYear()),
                    '{{DELIVERY_DATE}}' => $fmtDate($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{CARGO_DESCRIPTION}}' => $fallback($d?->getCargoDescription()),
                    '{{CARGO_QTY}}' => $fallback($d?->getCargoQty()),
                    '{{DECLARED_VALUE}}' => $fmtMoney($d?->getDeclaredValue(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d?->getLiabilityLimit(), $currency),
                    '{{CANCELLATION_TERMS}}' => $fallback($d?->getCancellationTerms()),
                ]);
                break;

            case 'SERVICES':
                $d = $income->getServicesDetails();
                $map = array_merge($map, [
                    '{{SERVICE_ID}}' => $income->getCode(),
                    '{{CUSTOMER_NAME}}' => $companyName,
                    '{{CUSTOMER_CONTACT}}' => $companyContact,
                    '{{CUSTOMER_SIGN}}' => $companySign,
                    '{{PROVIDER_NAME}}' => $asset?->getName() ?? '—',
                    '{{PROVIDER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{LOCATION}}' => $fallback($d?->getLocation()),
                    '{{VESSEL_NAME}}' => $asset?->getName() ?? '—',
                    '{{VESSEL_ID}}' => $income->getCode(),
                    '{{REQUESTED_BY}}' => $fallback($d?->getRequestedBy()),
                    '{{SERVICE_TYPE}}' => $fallback($d?->getServiceType()),
                    '{{START_DATE}}' => $fmtDate($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $fmtDate($d?->getEndDay(), $d?->getEndYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{WORK_SUMMARY}}' => $fallback($d?->getWorkSummary()),
                    '{{PARTS_MATERIALS}}' => $fallback($d?->getPartsMaterials()),
                    '{{RISKS}}' => $fallback($d?->getRisks()),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{EXTRAS}}' => $fallback($d?->getExtras()),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d?->getLiabilityLimit(), $currency),
                    '{{CANCELLATION_TERMS}}' => $fallback($d?->getCancellationTerms()),
                ]);
                break;

            case 'PASSENGERS':
                $d = $income->getPassengersDetails();
                $map = array_merge($map, [
                    '{{TICKET_ID}}' => $income->getCode(),
                    '{{PASSENGER_NAMES}}' => $fallback($d?->getPassengerNames()),
                    '{{PASSENGER_CONTACT}}' => $fallback($d?->getPassengerContact()),
                    '{{PASSENGER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{ORIGIN}}' => $fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $fallback($d?->getDestination()),
                    '{{DEPARTURE_DATE}}' => $fmtDate($d?->getDepartureDay(), $d?->getDepartureYear()),
                    '{{ARRIVAL_DATE}}' => $fmtDate($d?->getArrivalDay(), $d?->getArrivalYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{CLASS_OR_BERTH}}' => $fallback($d?->getClassOrBerth()),
                    '{{QTY}}' => (string) ($d?->getQty() ?? '—'),
                    '{{BAGGAGE_ALLOWANCE}}' => $fallback($d?->getBaggageAllowance()),
                    '{{EXTRA_BAGGAGE}}' => $fallback($d?->getExtraBaggage()),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{REFUND_CHANGE_POLICY}}' => $fallback($d?->getRefundChangePolicy()),
                ]);
                break;

            case 'CONTRACT':
                $d = $income->getContractDetails();
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{PATRON_NAME}}' => $companyName,
                    '{{PATRON_CONTACT}}' => $companyContact,
                    '{{PATRON_SIGN}}' => $companySign,
                    '{{CONTRACTOR_NAME}}' => $asset?->getName() ?? '—',
                    '{{CONTRACTOR_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{JOB_TYPE}}' => $fallback($d?->getJobType()),
                    '{{LOCATION}}' => $fallback($d?->getLocation()),
                    '{{OBJECTIVE}}' => $fallback($d?->getObjective()),
                    '{{START_DATE}}' => $fmtDate($d?->getStartDay(), $d?->getStartYear()),
                    '{{DEADLINE}}' => $fmtDate($d?->getDeadlineDay(), $d?->getDeadlineYear()),
                    '{{SUCCESS_CONDITION}}' => $fallback($d?->getSuccessCondition()),
                    '{{PAY_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{BONUS}}' => $fallback($d?->getBonus()),
                    '{{EXPENSES_POLICY}}' => $fallback($d?->getExpensesPolicy()),
                    '{{DEPOSIT}}' => $fmtMoney($d?->getDeposit(), $currency),
                    '{{RESTRICTIONS}}' => $fallback($d?->getRestrictions()),
                    '{{CONFIDENTIALITY_LEVEL}}' => $fallback($d?->getConfidentialityLevel()),
                    '{{FAILURE_TERMS}}' => $fallback($d?->getFailureTerms()),
                    '{{CANCELLATION_TERMS}}' => $fallback($d?->getCancellationTerms()),
                ]);
                break;

            case 'INTEREST':
                $d = $income->getInterestDetails();
                $map = array_merge($map, [
                    '{{RECEIPT_ID}}' => $income->getCode(),
                    '{{PAYER_NAME}}' => $companyName,
                    '{{PAYEE_NAME}}' => $asset?->getName() ?? '—',
                    '{{PAYEE_CONTACT}}' => $companyContact,
                    '{{ACCOUNT_REF}}' => $fallback($d?->getAccountRef()),
                    '{{INSTRUMENT}}' => $fallback($d?->getInstrument()),
                    '{{PRINCIPAL}}' => $fmtMoney($d?->getPrincipal(), $currency),
                    '{{INTEREST_RATE}}' => $fallback($d?->getInterestRate()),
                    '{{START_DATE}}' => $fmtDate($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $fmtDate($d?->getEndDay(), $d?->getEndYear()),
                    '{{CALC_METHOD}}' => $fallback($d?->getCalcMethod()),
                    '{{INTEREST_EARNED}}' => $fmtMoney($d?->getInterestEarned(), $currency),
                    '{{NET_PAID}}' => $fmtMoney($d?->getNetPaid(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{DISPUTE_WINDOW}}' => $fallback($d?->getDisputeWindow()),
                ]);
                break;

            case 'MAIL':
                $d = $income->getMailDetails();
                $map = array_merge($map, [
                    '{{CARRIER_NAME}}' => $asset?->getName() ?? '—',
                    '{{VESSEL_NAME}}' => $asset?->getName() ?? '—',
                    '{{ORIGIN}}' => $fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $fallback($d?->getDestination()),
                    '{{DISPATCH_DATE}}' => $fmtDate($d?->getDispatchDay(), $d?->getDispatchYear()),
                    '{{DELIVERY_DATE}}' => $fmtDate($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{MAIL_TYPE}}' => $fallback($d?->getMailType()),
                    '{{PACKAGE_COUNT}}' => (string) ($d?->getPackageCount() ?? '—'),
                    '{{TOTAL_MASS}}' => $fmtMoney($d?->getTotalMass(), "dT"),
                    '{{SECURITY_LEVEL}}' => $fallback($d?->getSecurityLevel()),
                    '{{SEAL_CODES}}' => $fallback($d?->getSealCodes()),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{MAIL_FEE}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{LIABILITY_LIMIT}}' => $fmtMoney($d?->getLiabilityLimit(), $currency),
                    '{{PROOF_OF_DELIVERY}}' => $fallback($d?->getProofOfDelivery()),
                    '{{CARRIER_SIGN}}' => $asset?->getCaptain() ?? '—',
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                ]);
                break;

            case 'INSURANCE':
                $d = $income->getInsuranceDetails();
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{INSURER_NAME}}' => $companyName,
                    '{{INSURED_NAME}}' => $asset?->getName() ?? '—',
                    '{{INSURED_CONTACT}}' => $companyContact,
                    '{{POLICY_NUMBER}}' => $income->getCode(),
                    '{{INCIDENT_REF}}' => $fallback($d?->getIncidentRef()),
                    '{{INCIDENT_DATE}}' => $fmtDate($d?->getIncidentDay(), $d?->getIncidentYear()),
                    '{{INCIDENT_LOCATION}}' => $fallback($d?->getIncidentLocation()),
                    '{{INCIDENT_CAUSE}}' => $fallback($d?->getIncidentCause()),
                    '{{LOSS_TYPE}}' => $fallback($d?->getLossType()),
                    '{{VERIFIED_LOSS}}' => $fmtMoney($d?->getVerifiedLoss(), $currency),
                    '{{PAYOUT_AMOUNT}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{DEDUCTIBLE}}' => $fmtMoney($d?->getDeductible(), $currency),
                    '{{COVERAGE_NOTES}}' => $fallback($d?->getCoverageNotes()),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{ACCEPTANCE_EFFECT}}' => $fallback($d?->getAcceptanceEffect()),
                    '{{SUBROGATION_TERMS}}' => $fallback($d?->getSubrogationTerms()),
                    '{{NOTES}}' => $fallback($income->getNote()),
                    '{{INSURER_SIGN}}' => $companySign,
                    '{{INSURED_SIGN}}' => $asset?->getCaptain() ?? '—',
                ]);
                break;

            case 'SALVAGE':
                $d = $income->getSalvageDetails();
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{SALVAGE_TEAM_NAME}}' => $asset?->getName(),
                    '{{SALVAGE_CONTACT}}' => $companyContact,
                    '{{SALVAGE_SIGN}}' => $asset?->getCaptain(),
                    '{{AUTHORITY_OR_OWNER_NAME}}' => $company,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                    '{{CASE_REF}}' => $fallback($d?->getCaseRef()),
                    '{{SITE_LOCATION}}' => $fallback($d?->getSiteLocation()),
                    '{{SOURCE}}' => $fallback($d?->getSource()),
                    '{{START_DATE}}' => $fmtDate(null, null),
                    '{{END_DATE}}' => $fmtDate(null, null),
                    '{{RECOVERED_ITEMS_SUMMARY}}' => $fallback($d?->getRecoveredItemsSummary()),
                    '{{QTY_VALUE}}' => $fmtMoney($d?->getQtyValue(), $currency),
                    '{{HAZARDS}}' => $fallback($d?->getHazards()),
                    '{{SALVAGE_AWARD}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{SPLIT_TERMS}}' => $fallback($d?->getSplitTerms()),
                    '{{RIGHTS_BASIS}}' => $fallback($d?->getRightsBasis()),
                    '{{AWARD_TRIGGER}}' => $fallback($d?->getAwardTrigger()),
                    '{{DISPUTE_PROCESS}}' => $fallback($d?->getDisputeProcess()),
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
                $d = $income->getTradeDetails();
                $map = array_merge($map, [
                    '{{DEAL_ID}}' => $income->getCode(),
                    '{{BUYER_NAME}}' => $companyName,
                    '{{BUYER_CONTACT}}' => $companyContact,
                    '{{BUYER_SIGN}}' => $companySign,
                    '{{SELLER_NAME}}' => $asset?->getName() ?? '—',
                    '{{SELLER_CONTACT}}' => $companyContact,
                    '{{SELLER_SIGN}}' => $asset?->getName() ?? '—',
                    '{{LOCATION}}' => $fallback($d?->getLocation()),
                    '{{GOODS_DESCRIPTION}}' => $fallback($d?->getGoodsDescription()),
                    '{{QTY}}' => (string) ($d?->getQty() ?? '—'),
                    '{{GRADE}}' => $fallback($d?->getGrade()),
                    '{{BATCH_IDS}}' => $fallback($d?->getBatchIds()),
                    '{{UNIT_PRICE}}' => $fmtMoney($d?->getUnitPrice(), $currency),
                    '{{TOTAL_PRICE}}' => $fmtMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $fallback($d?->getPaymentTerms()),
                    '{{DELIVERY_METHOD}}' => $fallback($d?->getDeliveryMethod()),
                    '{{DELIVERY_DATE}}' => $fmtDate($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{DELIVERY_PROOF_REF}}' => $fallback($d?->getDeliveryProofRef()),
                    '{{DELIVERY_PROOF_DATE}}' => $fmtDate($d?->getDeliveryProofDay(), $d?->getDeliveryProofYear()),
                    '{{DELIVERY_PROOF_RECEIVED_BY}}' => $fallback($d?->getDeliveryProofReceivedBy()),
                    '{{TRANSFER_POINT}}' => $fallback($d?->getTransferPoint()),
                    '{{TRANSFER_CONDITION}}' => $fallback($d?->getTransferCondition()),
                    '{{AS_IS_OR_WARRANTY}}' => $fallback($d?->getAsIsOrWarranty()),
                    '{{WARRANTY}}' => $fallback($d?->getWarrantyText()),
                    '{{CLAIM_WINDOW}}' => $fallback($d?->getClaimWindow()),
                    '{{CANCEL_RETURN_POLICY}}' => $fallback($d?->getReturnPolicy()),
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
