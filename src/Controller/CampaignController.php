<?php

namespace App\Controller;

use App\Dto\ShipSelection;
use App\Entity\Campaign;
use App\Entity\Ship;
use App\Entity\CampaignSessionLog;
use App\Entity\AnnualBudget;
use App\Entity\Cost;
use App\Entity\Crew;
use App\Entity\Income;
use App\Entity\Mortgage;
use App\Entity\ShipAmendment;
use Symfony\Component\Uid\Uuid;
use App\Form\Config\DayYearLimits;
use App\Form\CampaignType;
use App\Form\ShipSelectType;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Security\Voter\CampaignVoter;
use App\Security\Voter\ShipVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CampaignController extends BaseController
{
    public const CONTROLLER_NAME = 'CampaignController';

    #[Route('/campaign/index', name: 'app_campaign_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $filters = $listViewHelper->collectFilters($request, [
            'title',
            'starting_year' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $result = $em->getRepository(Campaign::class)->findWithFilters($filters, $page, $perPage, $user);
        $campaigns = $result['items'];
        $total = $result['total'];

        $totalPages = max(1, (int) ceil($total / $perPage));
        $clampedPage = $listViewHelper->clampPage($page, $totalPages);
        if ($clampedPage !== $page) {
            $page = $clampedPage;
            $result = $em->getRepository(Campaign::class)->findWithFilters($filters, $page, $perPage, $user);
            $campaigns = $result['items'];
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('campaign/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaigns' => $campaigns,
            'filters' => $filters,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/campaign/new', name: 'app_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = new Campaign();
        $form = $this->createForm(CampaignType::class, $campaign);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($campaign);
            $em->flush();

            return $this->redirectToRoute('app_campaign_index');
        }

        return $this->renderTurbo('campaign/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaign' => $campaign,
            'form' => $form,
        ]);
    }

    #[Route('/campaign/edit/{id}', name: 'app_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(CampaignType::class, $campaign);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_campaign_index');
        }

        return $this->renderTurbo('campaign/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'campaign' => $campaign,
            'form' => $form,
        ]);
    }

    #[Route('/campaign/delete/{id}', name: 'app_campaign_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CampaignVoter::DELETE, $campaign);

        $em->remove($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_index');
    }

    #[Route('/campaign/{id}/details', name: 'app_campaign_details')]
    public function ships(
        int $id,
        Request $request,
        DayYearLimits $limits,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $shipsToSelect = $em->getRepository(Ship::class)->findWithoutCampaignForUser($user);

        $rows = [];
        foreach ($shipsToSelect as $ship) {
            $dto = (new ShipSelection())
                ->setShip($ship)
                ->setSelected(false);

            $rows[] = $dto;
        }

        $shipForm = $this->createForm(ShipSelectType::class, [
            'shipSelections' => $rows,
        ]);

        $sessionDate = new ImperialDate($campaign->getSessionYear(), $campaign->getSessionDay());
        $calendarForm = $this->createForm(ImperialDateType::class, $sessionDate, [
            'min_year' => max($limits->getYearMin(), $campaign->getStartingYear() ?? $limits->getYearMin()),
            'max_year' => $limits->getYearMax(),
        ]);

        $shipForm->handleRequest($request);
        if ($shipForm->isSubmitted() && $shipForm->isValid()) {
            /** @var ShipSelection[] $selections */
            $selections = $shipForm->get('shipSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->isSelected()) {
                    $campaign->addShip($selection->getShip());
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        $calendarForm->handleRequest($request);
        if ($calendarForm->isSubmitted() && $calendarForm->isValid()) {
            /** @var ImperialDate $session */
            $session = $calendarForm->getData();

            $previousDay = $campaign->getSessionDay();
            $previousYear = $campaign->getSessionYear();
            $campaign->setSessionDay($session->getDay());
            $campaign->setSessionYear($session->getYear());

            if ($previousDay !== $session->getDay() || $previousYear !== $session->getYear()) {
                $snapshot = $this->buildCampaignSnapshot($campaign, $em);
                $log = (new CampaignSessionLog())
                    ->setCampaign($campaign)
                    ->setUser($user)
                    ->setSessionDay($session->getDay())
                    ->setSessionYear($session->getYear())
                    ->setPayload([
                        'from' => ['day' => $previousDay, 'year' => $previousYear],
                        'to' => ['day' => $session->getDay(), 'year' => $session->getYear()],
                        'source' => 'manual',
                        'snapshot' => $snapshot,
                    ]);

                $em->persist($log);
            }

            $em->flush();
            $this->addFlash('success', 'Session updated');

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        $sessionLogs = $em->getRepository(CampaignSessionLog::class)->findBy(
            ['campaign' => $campaign],
            ['createdAt' => 'DESC']
        );

        return $this->renderTurbo('campaign/details.html.twig', [
            'campaign' => $campaign,
            'form' => $shipForm,
            'calendar_form' => $calendarForm,
            'session_logs' => $sessionLogs,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCampaignSnapshot(Campaign $campaign, EntityManagerInterface $em): array
    {
        $ships = [];

        foreach ($campaign->getShips() as $ship) {
            $ships[] = [
                'id' => $ship->getId(),
                'code' => $ship->getCode(),
                'name' => $ship->getName(),
                'type' => $ship->getType(),
                'class' => $ship->getClass(),
                'price' => $ship->getPrice(),

                'shipDetails' => $ship->getShipDetails(),
                'mortgage' => $this->mapMortgage($ship->getMortgage()),
                'crews' => $ship->getCrews()->map(fn(Crew $crew) => $this->mapCrew($crew))->toArray(),
                'costs' => $ship->getCosts()->map(fn(Cost $cost) => $this->mapCost($cost))->toArray(),
                'incomes' => $ship->getIncomes()->map(fn(Income $income) => $this->mapIncome($income))->toArray(),
                'amendments' => $ship->getAmendments()->map(fn(ShipAmendment $amendment) => $this->mapAmendment($amendment))->toArray(),
                'annualBudgets' => array_map(
                    fn(AnnualBudget $budget) => $this->mapAnnualBudget($budget),
                    $em->getRepository(AnnualBudget::class)->findBy(['ship' => $ship])
                ),
            ];
        }

        return [
            'campaign' => [
                'id' => $campaign->getId(),
                'code' => $campaign->getCode() ? (string) $campaign->getCode() : null,
                'title' => $campaign->getTitle(),
                'description' => $campaign->getDescription(),
                'startingYear' => $campaign->getStartingYear(),
                'sessionDay' => $campaign->getSessionDay(),
                'sessionYear' => $campaign->getSessionYear(),
            ],
            'ships' => $ships,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapMortgage(?Mortgage $mortgage): ?array
    {
        if (!$mortgage) {
            return null;
        }

        $interestRate = $mortgage->getInterestRate();
        $insurance = $mortgage->getInsurance();

        return [
            'id' => $mortgage->getId(),
            'code' => $mortgage->getCode(),
            'name' => $mortgage->getName(),
            'signed' => $mortgage->isSigned(),
            'startDay' => $mortgage->getStartDay(),
            'startYear' => $mortgage->getStartYear(),
            'signingDay' => $mortgage->getSigningDay(),
            'signingYear' => $mortgage->getSigningYear(),
            'signingLocation' => $mortgage->getSigningLocation(),
            'shipShares' => $mortgage->getShipShares(),
            'advancePayment' => $mortgage->getAdvancePayment(),
            'discount' => $mortgage->getDiscount(),
            'interestRate' => $interestRate ? [
                'id' => $interestRate->getId(),
                'duration' => $interestRate->getDuration(),
                'priceMultiplier' => $interestRate->getPriceMultiplier(),
                'priceDivider' => $interestRate->getPriceDivider(),
                'annualInterestRate' => $interestRate->getAnnualInterestRate(),
            ] : null,
            'insurance' => $insurance ? [
                'id' => $insurance->getId(),
                'name' => $insurance->getName(),
                'annualCost' => $insurance->getAnnualCost(),
                'lossRefund' => $insurance->getLossRefund(),
                'coverage' => $insurance->getCoverage(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCrew(Crew $crew): array
    {
        return [
            'id' => $crew->getId(),
            'code' => $crew->getCode(),
            'name' => $crew->getName(),
            'surname' => $crew->getSurname(),
            'nickname' => $crew->getNickname(),
            'status' => $crew->getStatus(),
            'birthDay' => $crew->getBirthDay(),
            'birthYear' => $crew->getBirthYear(),
            'birthWorld' => $crew->getBirthWorld(),
            'background' => $crew->getBackground(),
            'activeDay' => $crew->getActiveDay(),
            'activeYear' => $crew->getActiveYear(),
            'onLeaveDay' => $crew->getOnLeaveDay(),
            'onLeaveYear' => $crew->getOnLeaveYear(),
            'retiredDay' => $crew->getRetiredDay(),
            'retiredYear' => $crew->getRetiredYear(),
            'miaDay' => $crew->getMiaDay(),
            'miaYear' => $crew->getMiaYear(),
            'deceasedDay' => $crew->getDeceasedDay(),
            'deceasedYear' => $crew->getDeceasedYear(),
            'roles' => $crew->getShipRoles()->map(fn($role) => $role->getCode())->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCost(Cost $cost): array
    {
        $company = $cost->getCompany();
        $localLaw = $cost->getLocalLaw();
        $category = $cost->getCostCategory();

        return [
            'id' => $cost->getId(),
            'code' => $cost->getCode(),
            'title' => $cost->getTitle(),
            'amount' => $cost->getAmount(),
            'paymentDay' => $cost->getPaymentDay(),
            'paymentYear' => $cost->getPaymentYear(),
            'note' => $cost->getNote(),
            'detailItems' => $cost->getDetailItems(),
            'category' => $category ? [
                'id' => $category->getId(),
                'code' => $category->getCode(),
                'description' => $category->getDescription(),
            ] : null,
            'company' => $company ? [
                'id' => $company->getId(),
                'code' => $company->getCode(),
                'name' => $company->getName(),
                'contact' => $company->getContact(),
                'signLabel' => $company->getSignLabel(),
            ] : null,
            'localLaw' => $localLaw ? [
                'id' => $localLaw->getId(),
                'shortDescription' => $localLaw->getShortDescription(),
                'description' => $localLaw->getDescription(),
                'disclaimer' => $localLaw->getDisclaimer(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapIncome(Income $income): array
    {
        $company = $income->getCompany();
        $localLaw = $income->getLocalLaw();
        $category = $income->getIncomeCategory();

        $details = [];
        foreach (
            [
                'charter' => $income->getCharterDetails(),
                'subsidy' => $income->getSubsidyDetails(),
                'freight' => $income->getFreightDetails(),
                'passengers' => $income->getPassengersDetails(),
                'services' => $income->getServicesDetails(),
                'insurance' => $income->getInsuranceDetails(),
                'mail' => $income->getMailDetails(),
                'interest' => $income->getInterestDetails(),
                'trade' => $income->getTradeDetails(),
                'salvage' => $income->getSalvageDetails(),
                'prize' => $income->getPrizeDetails(),
                'contract' => $income->getContractDetails(),
            ] as $key => $detail
        ) {
            if ($detail) {
                $details[$key] = $this->extractDetails($detail);
            }
        }

        return [
            'id' => $income->getId(),
            'code' => $income->getCode(),
            'title' => $income->getTitle(),
            'status' => $income->getStatus(),
            'amount' => $income->getAmount(),
            'signingDay' => $income->getSigningDay(),
            'signingYear' => $income->getSigningYear(),
            'signingLocation' => $income->getSigningLocation(),
            'paymentDay' => $income->getPaymentDay(),
            'paymentYear' => $income->getPaymentYear(),
            'cancelDay' => $income->getCancelDay(),
            'cancelYear' => $income->getCancelYear(),
            'expirationDay' => $income->getExpirationDay(),
            'expirationYear' => $income->getExpirationYear(),
            'note' => $income->getNote(),
            'category' => $category ? [
                'id' => $category->getId(),
                'code' => $category->getCode(),
                'description' => $category->getDescription(),
            ] : null,
            'company' => $company ? [
                'id' => $company->getId(),
                'code' => $company->getCode(),
                'name' => $company->getName(),
                'contact' => $company->getContact(),
                'signLabel' => $company->getSignLabel(),
            ] : null,
            'localLaw' => $localLaw ? [
                'id' => $localLaw->getId(),
                'shortDescription' => $localLaw->getShortDescription(),
                'description' => $localLaw->getDescription(),
                'disclaimer' => $localLaw->getDisclaimer(),
            ] : null,
            'details' => $details,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAmendment(ShipAmendment $amendment): array
    {
        $cost = $amendment->getCost();

        return [
            'id' => $amendment->getId(),
            'code' => $amendment->getCode(),
            'title' => $amendment->getTitle(),
            'description' => $amendment->getDescription(),
            'effectiveDay' => $amendment->getEffectiveDay(),
            'effectiveYear' => $amendment->getEffectiveYear(),
            'patchDetails' => $amendment->getPatchDetails(),
            'cost' => $cost ? [
                'id' => $cost->getId(),
                'code' => $cost->getCode(),
                'title' => $cost->getTitle(),
                'amount' => $cost->getAmount(),
                'paymentDay' => $cost->getPaymentDay(),
                'paymentYear' => $cost->getPaymentYear(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAnnualBudget(AnnualBudget $budget): array
    {
        return [
            'id' => $budget->getId(),
            'code' => $budget->getCode(),
            'startDay' => $budget->getStartDay(),
            'startYear' => $budget->getStartYear(),
            'endDay' => $budget->getEndDay(),
            'endYear' => $budget->getEndYear(),
            'note' => $budget->getNote(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractDetails(object $details): array
    {
        $data = [];
        $ref = new \ReflectionClass($details);

        foreach ($ref->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($details);
            $normalized = $this->normalizeSnapshotValue($value);
            if ($normalized !== null) {
                $data[$property->getName()] = $normalized;
            }
        }

        return $data;
    }

    private function normalizeSnapshotValue(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Uuid) {
            return (string) $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    #[Route('/campaign/ship/{id}/remove', name: 'app_campaign_ship_remove', methods: ['GET', 'POST'])]
    public function removeShip(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $ship = $em->getRepository(Ship::class)->findOneForUser($id, $user);
        if (!$ship) {
            throw new NotFoundHttpException();
        }

        $campaign = $ship->getCampaign();
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(ShipVoter::CAMPAIGN_REMOVE, $ship);

        $campaign->removeShip($ship);
        $em->persist($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
    }
}
