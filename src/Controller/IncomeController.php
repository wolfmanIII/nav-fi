<?php

namespace App\Controller;

use App\Entity\Income;
use App\Entity\Campaign;
use App\Entity\Ship;
use App\Form\IncomeType;
use App\Security\Voter\IncomeVoter;
use App\Entity\IncomeCategory;
use App\Service\PdfGenerator;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class IncomeController extends BaseController
{
    public const CONTROLLER_NAME = 'IncomeController';

    /**
     * @var array<string, string>
     */
    private const DETAIL_FIELDS = [
        'CHARTER' => 'charterDetails',
        'SUBSIDY' => 'subsidyDetails',
        'FREIGHT' => 'freightDetails',
        'PASSENGERS' => 'passengersDetails',
        'SERVICES' => 'servicesDetails',
        'INSURANCE' => 'insuranceDetails',
        'MAIL' => 'mailDetails',
        'INTEREST' => 'interestDetails',
        'TRADE' => 'tradeDetails',
        'SALVAGE' => 'salvageDetails',
        'PRIZE' => 'prizeDetails',
        'CONTRACT' => 'contractDetails',
    ];

    #[Route('/income/index', name: 'app_income_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'title',
            'category' => ['type' => 'int'],
            'ship' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $incomes = [];
        $total = 0;
        $totalPages = 1;
        $categories = [];
        $ships = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Income::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $incomes = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Income::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $incomes = $result['items'];
            }

            $categories = $em->getRepository(IncomeCategory::class)->findBy([], ['code' => 'ASC']);
            $ships = $em->getRepository(Ship::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('income/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'incomes' => $incomes,
            'filters' => $filters,
            'categories' => $categories,
            'ships' => $ships,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/income/new', name: 'app_income_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = new Income();
        $categoryParam = $request->query->get('category');
        if ($categoryParam) {
            $category = $em->getRepository(IncomeCategory::class)->find($categoryParam);
            if ($category) {
                $income->setIncomeCategory($category);
                $this->clearUnusedDetails($income, $em);
            }
        }
        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearUnusedDetails($income, $em);
            $em->persist($income);
            $em->flush();

            return $this->redirectToRoute('app_income_index');
        }

        return $this->renderTurbo('income/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'income' => $income,
            'form' => $form,
        ]);
    }

    #[Route('/income/edit/{id}', name: 'app_income_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = $em->getRepository(Income::class)->findOneForUser($id, $user);
        if (!$income) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(IncomeVoter::EDIT, $income);

        $categoryParam = $request->query->get('category');
        if ($categoryParam) {
            $category = $em->getRepository(IncomeCategory::class)->find($categoryParam);
            if ($category) {
                $income->setIncomeCategory($category);
            }
        }

        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearUnusedDetails($income, $em);
            $em->flush();

            return $this->redirectToRoute('app_income_index');
        }

        return $this->renderTurbo('income/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'income' => $income,
            'form' => $form,
        ]);
    }

    #[Route('/income/delete/{id}', name: 'app_income_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = $em->getRepository(Income::class)->findOneForUser($id, $user);
        if (!$income) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(IncomeVoter::DELETE, $income);

        $em->remove($income);
        $em->flush();

        return $this->redirectToRoute('app_income_index');
    }

    #[Route('/income/{id}/pdf', name: 'app_income_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        PdfGenerator $pdfGenerator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = $em->getRepository(Income::class)->findOneForUser($id, $user);
        if (!$income) {
            throw new NotFoundHttpException();
        }

        $template = $this->resolveTemplate($income);
        $placeholders = $this->buildPlaceholderMap($income);

        $html = $this->renderView($template);
        $html = strtr($html, $placeholders);

        $pdf = $pdfGenerator->renderFromHtml($html, [
            'margin-top' => '18mm',
            'margin-bottom' => '20mm',
            'margin-left' => '10mm',
            'margin-right' => '10mm',
            'footer-right' => 'Page [page] / [toPage]',
            'footer-font-size' => 8,
            'footer-spacing' => 8,
            'disable-smart-shrinking' => true,
            'print-media-type' => true,
            'enable-local-file-access' => true,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"income-%s.pdf\"', $income->getCode()),
        ]);
    }

    private function clearUnusedDetails(Income $income, EntityManagerInterface $em): void
    {
        $currentCode = $income->getIncomeCategory()?->getCode();
        if (!$currentCode) {
            return;
        }

        foreach (self::DETAIL_FIELDS as $code => $property) {
            if ($code === $currentCode) {
                continue;
            }

            $getter = 'get' . ucfirst($property);
            $setter = 'set' . ucfirst($property);

            if (!method_exists($income, $getter) || !method_exists($income, $setter)) {
                continue;
            }

            $detail = $income->$getter();
            if ($detail) {
                $em->remove($detail);
                $income->$setter(null);
            }
        }
    }

    private function resolveTemplate(Income $income): string
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
    private function buildPlaceholderMap(Income $income): array
    {
        $currency = 'Cr';
        $ship = $income->getShip();
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
            '{{VESSEL_NAME}}' => $ship?->getName() ?? '—',
            '{{CURRENCY}}' => $currency,
            '{{NOTES}}' => $this->fallback($income->getNote()),
            '{{PAYMENT}}' => $this->formatMoney($income->getAmount(), $currency),
        ];

        $companyName = $company?->getName() ?? '—';
        $companyContact = $company?->getContact() ?? '—';
        $companySign = $company?->getSignLabel() ?? $companyName;

        $map = $common;

        $localLaw = $income->getLocalLaw();
        $map['{{LOCAL_LAW_SHORT_DESCRIPTION}}'] = $this->fallback($localLaw?->getShortDescription());
        $map['{{LOCAL_LAW_DESCRIPTION}}'] = $this->fallback($localLaw?->getDescription());
        $map['{{LOCAL_LAW_DISCLAIMER}}'] = $this->fallback($localLaw?->getDisclaimer());

        switch ($detailCode) {
            case 'CHARTER':
                $d = $income->getCharterDetails();
                $map = array_merge($map, [
                    '{{CHARTER_ID}}' => $income->getCode(),
                    '{{CHARTERER_NAME}}' => $companyName,
                    '{{CHARTERER_CONTACT}}' => $companyContact,
                    '{{CHARTERER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $ship?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{CHARTER_TYPE}}' => $this->fallback(null),
                    '{{START_DATE}}' => $this->formatDayYear($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $this->formatDayYear($d?->getEndDay(), $d?->getEndYear()),
                    '{{AREA_OR_ROUTE}}' => $this->fallback($d?->getAreaOrRoute()),
                    '{{PURPOSE}}' => $this->fallback($d?->getPurpose()),
                    '{{MANIFEST_SUMMARY}}' => $this->fallback($d?->getManifestSummary()),
                    '{{DEPOSIT}}' => $this->formatMoney($d?->getDeposit(), $currency),
                    '{{EXTRAS}}' => $this->fallback($d?->getExtras()),
                    '{{DAMAGE_TERMS}}' => $this->fallback($d?->getDamageTerms()),
                    '{{CANCELLATION_TERMS}}' => $this->fallback($d?->getCancellationTerms()),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                ]);
                break;
            case 'SUBSIDY':
                $d = $income->getSubsidyDetails();
                $map = array_merge($map, [
                    '{{SUBSIDY_ID}}' => $income->getCode(),
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_CONTACT}}' => $companyContact,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $ship?->getName() ?? '—',
                    '{{CARRIER_CONTACT}}' => $companyContact,
                    '{{CARRIER_SIGN}}' => $companySign,
                    '{{PROGRAM_REF}}' => $this->fallback($d?->getProgramRef()),
                    '{{ORIGIN}}' => $this->fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $this->fallback($d?->getDestination()),
                    '{{START_DATE}}' => $this->formatDayYear($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $this->formatDayYear($d?->getEndDay(), $d?->getEndYear()),
                    '{{SERVICE_LEVEL}}' => $this->fallback($d?->getServiceLevel()),
                    '{{SUBSIDY_AMOUNT}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{MILESTONES}}' => $this->fallback($d?->getMilestones()),
                    '{{REPORTING_REQUIREMENTS}}' => $this->fallback($d?->getReportingRequirements()),
                    '{{PROOF_REQUIREMENTS}}' => $this->fallback($d?->getProofRequirements()),
                    '{{NON_COMPLIANCE_TERMS}}' => $this->fallback($d?->getNonComplianceTerms()),
                    '{{CANCELLATION_TERMS}}' => $this->fallback($d?->getCancellationTerms()),
                ]);
                break;
            case 'PRIZE':
                $d = $income->getPrizeDetails();
                $map = array_merge($map, [
                    '{{PRIZE_ID}}' => $income->getCode(),
                    '{{CAPTOR_NAME}}' => $companyName,
                    '{{CAPTOR_CONTACT}}' => $companyContact,
                    '{{CAPTOR_SIGN}}' => $companySign,
                    '{{AUTHORITY_NAME}}' => $this->fallback($income->getLocalLaw()?->getShortDescription()),
                    '{{AUTHORITY_SIGN}}' => $this->fallback($income->getLocalLaw()?->getDescription()),
                    '{{CASE_REF}}' => $this->fallback($d?->getCaseRef()),
                    '{{JURISDICTION}}' => $this->fallback($income->getLocalLaw()?->getCode()),
                    '{{SEIZURE_LOCATION}}' => $this->fallback(null),
                    '{{SEIZURE_DATE}}' => $this->formatDayYear(null, null),
                    '{{LEGAL_BASIS}}' => $this->fallback($d?->getLegalBasis()),
                    '{{PRIZE_DESCRIPTION}}' => $this->fallback($d?->getPrizeDescription()),
                    '{{ESTIMATED_VALUE}}' => $this->formatMoney($d?->getEstimatedValue(), $currency),
                    '{{DISPOSITION}}' => $this->fallback($d?->getDisposition()),
                    '{{PRIZE_AWARD}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{SHARE_SPLIT}}' => $this->fallback($d?->getShareSplit()),
                    '{{AWARD_TRIGGER}}' => $this->fallback($d?->getAwardTrigger()),
                ]);
                break;
            case 'FREIGHT':
                $d = $income->getFreightDetails();
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{SHIPPER_NAME}}' => $companyName,
                    '{{SHIPPER_CONTACT}}' => $companyContact,
                    '{{SHIPPER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $ship?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{ORIGIN}}' => $this->fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $this->fallback($d?->getDestination()),
                    '{{PICKUP_DATE}}' => $this->formatDayYear($d?->getPickupDay(), $d?->getPickupYear()),
                    '{{DELIVERY_DATE}}' => $this->formatDayYear($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{CARGO_DESCRIPTION}}' => $this->fallback($d?->getCargoDescription()),
                    '{{CARGO_QTY}}' => $this->fallback($d?->getCargoQty()),
                    '{{DECLARED_VALUE}}' => $this->formatMoney($d?->getDeclaredValue(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{LIABILITY_LIMIT}}' => $this->formatMoney($d?->getLiabilityLimit(), $currency),
                    '{{CANCELLATION_TERMS}}' => $this->fallback($d?->getCancellationTerms()),
                ]);
                break;
            case 'SERVICES':
                $d = $income->getServicesDetails();
                $map = array_merge($map, [
                    '{{SERVICE_ID}}' => $income->getCode(),
                    '{{CUSTOMER_NAME}}' => $companyName,
                    '{{CUSTOMER_CONTACT}}' => $companyContact,
                    '{{CUSTOMER_SIGN}}' => $companySign,
                    '{{PROVIDER_NAME}}' => $ship?->getName() ?? '—',
                    '{{PROVIDER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{LOCATION}}' => $this->fallback($d?->getLocation()),
                    '{{VESSEL_NAME}}' => $ship?->getName() ?? '—',
                    '{{VESSEL_ID}}' => $income->getCode(),
                    '{{REQUESTED_BY}}' => $this->fallback($d?->getRequestedBy()),
                    '{{SERVICE_TYPE}}' => $this->fallback($d?->getServiceType()),
                    '{{START_DATE}}' => $this->formatDayYear($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $this->formatDayYear($d?->getEndDay(), $d?->getEndYear()),
                    '{{WORK_SUMMARY}}' => $this->fallback($d?->getWorkSummary()),
                    '{{PARTS_MATERIALS}}' => $this->fallback($d?->getPartsMaterials()),
                    '{{RISKS}}' => $this->fallback($d?->getRisks()),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{EXTRAS}}' => $this->fallback($d?->getExtras()),
                    '{{LIABILITY_LIMIT}}' => $this->formatMoney($d?->getLiabilityLimit(), $currency),
                    '{{CANCELLATION_TERMS}}' => $this->fallback($d?->getCancellationTerms()),
                ]);
                break;
            case 'PASSENGERS':
                $d = $income->getPassengersDetails();
                $map = array_merge($map, [
                    '{{TICKET_ID}}' => $income->getCode(),
                    '{{PASSENGER_NAMES}}' => $this->fallback($d?->getPassengerNames()),
                    '{{PASSENGER_CONTACT}}' => $this->fallback($d?->getPassengerContact()),
                    '{{PASSENGER_SIGN}}' => $companySign,
                    '{{CARRIER_NAME}}' => $ship?->getName() ?? '—',
                    '{{CARRIER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{ORIGIN}}' => $this->fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $this->fallback($d?->getDestination()),
                    '{{DEPARTURE_DATE}}' => $this->formatDayYear($d?->getDepartureDay(), $d?->getDepartureYear()),
                    '{{ARRIVAL_DATE}}' => $this->formatDayYear($d?->getArrivalDay(), $d?->getArrivalYear()),
                    '{{CLASS_OR_BERTH}}' => $this->fallback($d?->getClassOrBerth()),
                    '{{QTY}}' => (string) ($d?->getQty() ?? '—'),
                    '{{BAGGAGE_ALLOWANCE}}' => $this->fallback($d?->getBaggageAllowance()),
                    '{{EXTRA_BAGGAGE}}' => $this->fallback($d?->getExtraBaggage()),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{REFUND_CHANGE_POLICY}}' => $this->fallback($d?->getRefundChangePolicy()),
                ]);
                break;
            case 'CONTRACT':
                $d = $income->getContractDetails();
                $map = array_merge($map, [
                    '{{CONTRACT_ID}}' => $income->getCode(),
                    '{{PATRON_NAME}}' => $companyName,
                    '{{PATRON_CONTACT}}' => $companyContact,
                    '{{PATRON_SIGN}}' => $companySign,
                    '{{CONTRACTOR_NAME}}' => $ship?->getName() ?? '—',
                    '{{CONTRACTOR_SIGN}}' => $ship?->getName() ?? '—',
                    '{{JOB_TYPE}}' => $this->fallback($d?->getJobType()),
                    '{{LOCATION}}' => $this->fallback($d?->getLocation()),
                    '{{OBJECTIVE}}' => $this->fallback($d?->getObjective()),
                    '{{START_DATE}}' => $this->formatDayYear($d?->getStartDay(), $d?->getStartYear()),
                    '{{DEADLINE}}' => $this->formatDayYear($d?->getDeadlineDay(), $d?->getDeadlineYear()),
                    '{{SUCCESS_CONDITION}}' => $this->fallback($d?->getSuccessCondition()),
                    '{{PAY_AMOUNT}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{BONUS}}' => $this->fallback($d?->getBonus()),
                    '{{EXPENSES_POLICY}}' => $this->fallback($d?->getExpensesPolicy()),
                    '{{DEPOSIT}}' => $this->formatMoney($d?->getDeposit(), $currency),
                    '{{RESTRICTIONS}}' => $this->fallback($d?->getRestrictions()),
                    '{{CONFIDENTIALITY_LEVEL}}' => $this->fallback($d?->getConfidentialityLevel()),
                    '{{FAILURE_TERMS}}' => $this->fallback($d?->getFailureTerms()),
                    '{{CANCELLATION_TERMS}}' => $this->fallback($d?->getCancellationTerms()),
                ]);
                break;
            case 'INTEREST':
                $d = $income->getInterestDetails();
                $map = array_merge($map, [
                    '{{RECEIPT_ID}}' => $income->getCode(),
                    '{{PAYER_NAME}}' => $companyName,
                    '{{PAYEE_NAME}}' => $ship?->getName() ?? '—',
                    '{{PAYEE_CONTACT}}' => $companyContact,
                    '{{ACCOUNT_REF}}' => $this->fallback($d?->getAccountRef()),
                    '{{INSTRUMENT}}' => $this->fallback($d?->getInstrument()),
                    '{{PRINCIPAL}}' => $this->formatMoney($d?->getPrincipal(), $currency),
                    '{{INTEREST_RATE}}' => $this->fallback($d?->getInterestRate()),
                    '{{START_DATE}}' => $this->formatDayYear($d?->getStartDay(), $d?->getStartYear()),
                    '{{END_DATE}}' => $this->formatDayYear($d?->getEndDay(), $d?->getEndYear()),
                    '{{CALC_METHOD}}' => $this->fallback($d?->getCalcMethod()),
                    '{{INTEREST_EARNED}}' => $this->formatMoney($d?->getInterestEarned(), $currency),
                    '{{NET_PAID}}' => $this->formatMoney($d?->getNetPaid(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{DISPUTE_WINDOW}}' => $this->fallback($d?->getDisputeWindow()),
                ]);
                break;
            case 'MAIL':
                $d = $income->getMailDetails();
                $map = array_merge($map, [
                    '{{CARRIER_NAME}}' => $ship?->getName() ?? '—',
                    '{{VESSEL_NAME}}' => $ship?->getName() ?? '—',
                    '{{ORIGIN}}' => $this->fallback($d?->getOrigin()),
                    '{{DESTINATION}}' => $this->fallback($d?->getDestination()),
                    '{{DISPATCH_DATE}}' => $this->formatDayYear($d?->getDispatchDay(), $d?->getDispatchYear()),
                    '{{DELIVERY_DATE}}' => $this->formatDayYear($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{MAIL_TYPE}}' => $this->fallback($d?->getMailType()),
                    '{{PACKAGE_COUNT}}' => (string) ($d?->getPackageCount() ?? '—'),
                    '{{TOTAL_MASS}}' => $this->formatMoney($d?->getTotalMass(), "dT"),
                    '{{SECURITY_LEVEL}}' => $this->fallback($d?->getSecurityLevel()),
                    '{{SEAL_CODES}}' => $this->fallback($d?->getSealCodes()),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{MAIL_FEE}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{LIABILITY_LIMIT}}' => $this->formatMoney($d?->getLiabilityLimit(), $currency),
                    '{{PROOF_OF_DELIVERY}}' => $this->fallback($d?->getProofOfDelivery()),
                    '{{CARRIER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{AUTHORITY_NAME}}' => $companyName,
                    '{{AUTHORITY_SIGN}}' => $companySign,
                ]);
                break;
            case 'INSURANCE':
                $d = $income->getInsuranceDetails();
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{INSURER_NAME}}' => $companyName,
                    '{{INSURED_NAME}}' => $ship?->getName() ?? '—',
                    '{{INSURED_CONTACT}}' => $companyContact,
                    '{{POLICY_NUMBER}}' => $income->getCode(),
                    '{{INCIDENT_REF}}' => $this->fallback($d?->getIncidentRef()),
                    '{{INCIDENT_DATE}}' => $this->formatDayYear($d?->getIncidentDay(), $d?->getIncidentYear()),
                    '{{INCIDENT_LOCATION}}' => $this->fallback($d?->getIncidentLocation()),
                    '{{INCIDENT_CAUSE}}' => $this->fallback($d?->getIncidentCause()),
                    '{{LOSS_TYPE}}' => $this->fallback($d?->getLossType()),
                    '{{VERIFIED_LOSS}}' => $this->formatMoney($d?->getVerifiedLoss(), $currency),
                    '{{PAYOUT_AMOUNT}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{DEDUCTIBLE}}' => $this->formatMoney($d?->getDeductible(), $currency),
                    '{{COVERAGE_NOTES}}' => $this->fallback($d?->getCoverageNotes()),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{ACCEPTANCE_EFFECT}}' => $this->fallback($d?->getAcceptanceEffect()),
                    '{{SUBROGATION_TERMS}}' => $this->fallback($d?->getSubrogationTerms()),
                    '{{NOTES}}' => $this->fallback($income->getNote()),
                    '{{INSURER_SIGN}}' => $companySign,
                    '{{INSURED_SIGN}}' => $ship?->getName() ?? '—',
                ]);
                break;
            case 'SALVAGE':
                $d = $income->getSalvageDetails();
                $map = array_merge($map, [
                    '{{CLAIM_ID}}' => $income->getCode(),
                    '{{SALVAGE_TEAM_NAME}}' => $companyName,
                    '{{SALVAGE_CONTACT}}' => $companyContact,
                    '{{SALVAGE_SIGN}}' => $companySign,
                    '{{AUTHORITY_OR_OWNER_NAME}}' => $this->fallback(null),
                    '{{AUTHORITY_SIGN}}' => $this->fallback(null),
                    '{{CASE_REF}}' => $this->fallback($d?->getCaseRef()),
                    '{{SITE_LOCATION}}' => $this->fallback($d?->getSiteLocation()),
                    '{{SOURCE}}' => $this->fallback($d?->getSource()),
                    '{{START_DATE}}' => $this->formatDayYear(null, null),
                    '{{END_DATE}}' => $this->formatDayYear(null, null),
                    '{{RECOVERED_ITEMS_SUMMARY}}' => $this->fallback($d?->getRecoveredItemsSummary()),
                    '{{QTY_VALUE}}' => $this->formatMoney($d?->getQtyValue(), $currency),
                    '{{HAZARDS}}' => $this->fallback($d?->getHazards()),
                    '{{SALVAGE_AWARD}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{SPLIT_TERMS}}' => $this->fallback($d?->getSplitTerms()),
                    '{{RIGHTS_BASIS}}' => $this->fallback($d?->getRightsBasis()),
                    '{{AWARD_TRIGGER}}' => $this->fallback($d?->getAwardTrigger()),
                    '{{DISPUTE_PROCESS}}' => $this->fallback($d?->getDisputeProcess()),
                    '{{NOTES}}' => $this->fallback($income->getNote()),
                ]);
                break;
            case 'TRADE':
                $d = $income->getTradeDetails();
                $map = array_merge($map, [
                    '{{DEAL_ID}}' => $income->getCode(),
                    '{{BUYER_NAME}}' => $companyName,
                    '{{BUYER_CONTACT}}' => $companyContact,
                    '{{BUYER_SIGN}}' => $companySign,
                    '{{SELLER_NAME}}' => $ship?->getName() ?? '—',
                    '{{SELLER_CONTACT}}' => $companyContact,
                    '{{SELLER_SIGN}}' => $ship?->getName() ?? '—',
                    '{{LOCATION}}' => $this->fallback($d?->getLocation()),
                    '{{GOODS_DESCRIPTION}}' => $this->fallback($d?->getGoodsDescription()),
                    '{{QTY}}' => (string) ($d?->getQty() ?? '—'),
                    '{{GRADE}}' => $this->fallback($d?->getGrade()),
                    '{{BATCH_IDS}}' => $this->fallback($d?->getBatchIds()),
                    '{{UNIT_PRICE}}' => $this->formatMoney($d?->getUnitPrice(), $currency),
                    '{{TOTAL_PRICE}}' => $this->formatMoney($income->getAmount(), $currency),
                    '{{PAYMENT_TERMS}}' => $this->fallback($d?->getPaymentTerms()),
                    '{{DELIVERY_METHOD}}' => $this->fallback($d?->getDeliveryMethod()),
                    '{{DELIVERY_DATE}}' => $this->formatDayYear($d?->getDeliveryDay(), $d?->getDeliveryYear()),
                    '{{TRANSFER_POINT}}' => $this->fallback($d?->getTransferPoint()),
                    '{{TRANSFER_CONDITION}}' => $this->fallback($d?->getTransferCondition()),
                    '{{AS_IS_OR_WARRANTY}}' => $this->fallback($d?->getAsIsOrWarranty()),
                    '{{WARRANTY}}' => $this->fallback($d?->getWarrantyText()),
                    '{{CLAIM_WINDOW}}' => $this->fallback($d?->getClaimWindow()),
                    '{{CANCEL_RETURN_POLICY}}' => $this->fallback($d?->getReturnPolicy()),
                    '{{NOTES}}' => $this->fallback($income->getNote()),
                ]);
                break;
        }

        return $map;
    }

    private function formatDayYear(?int $day, ?int $year): string
    {
        if ($day === null && $year === null) {
            return '—';
        }
        return sprintf('Day %s / Year %s', $day ?? '—', $year ?? '—');
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
