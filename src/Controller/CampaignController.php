<?php

namespace App\Controller;

use App\Dto\AssetSelection;
use App\Entity\Campaign;
use App\Entity\Asset;
use App\Entity\CampaignSessionLog;
use App\Entity\Crew;
use App\Entity\Mortgage;
use App\Entity\AssetAmendment;
use App\Entity\Route as NavRoute;
use App\Entity\RouteWaypoint;
use Symfony\Component\Uid\Uuid;
use App\Form\Config\DayYearLimits;
use App\Form\CampaignType;
use App\Form\AssetSelectType;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Security\Voter\CampaignVoter;
use App\Security\Voter\AssetVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\CrewAssignmentService;

final class CampaignController extends BaseController
{
    public const CONTROLLER_NAME = 'CampaignController';

    #[Route('/campaign/index', name: 'app_campaign_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
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
        if (!$user instanceof User) {
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
        if (!$user instanceof User) {
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
        if (!$user instanceof User) {
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
    public function assets(
        int $id,
        Request $request,
        DayYearLimits $limits,
        EntityManagerInterface $em,
        ListViewHelper $listViewHelper,
        CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $campaign = $em->getRepository(Campaign::class)->findOneForUser($id, $user);
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $assetsToSelect = $em->getRepository(Asset::class)->findWithoutCampaignForUser($user);

        $rows = [];
        foreach ($assetsToSelect as $asset) {
            $dto = (new AssetSelection())
                ->setAsset($asset)
                ->setSelected(false);

            $rows[] = $dto;
        }

        $assetForm = $this->createForm(AssetSelectType::class, [
            'assetSelections' => $rows,
        ]);

        $sessionDate = new ImperialDate($campaign->getSessionYear(), $campaign->getSessionDay());
        $calendarForm = $this->createForm(ImperialDateType::class, $sessionDate, [
            'min_year' => max($limits->getYearMin(), $campaign->getStartingYear() ?? $limits->getYearMin()),
            'max_year' => $limits->getYearMax(),
        ]);

        $assetForm->handleRequest($request);
        if ($assetForm->isSubmitted() && $assetForm->isValid()) {
            /** @var AssetSelection[] $selections */
            $selections = $assetForm->get('assetSelections')->getData();

            foreach ($selections as $selection) {
                if ($selection->isSelected()) {
                    $asset = $selection->getAsset();
                    $campaign->addAsset($asset);
                    $crewAssignmentService->activatePendingCrew($asset);
                }
            }

            $em->flush();

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        if ($assetForm->isSubmitted() && !$assetForm->isValid()) {
            $this->flashFormErrors($assetForm);
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
            $em->flush();
            $this->addFlash('success', 'TEMPORAL RECONCILIATION COMPLETE. Future liabilities converted to current debt. Solvency recalculated.');

            // Check for Hard Deck Breach (Negative Balance)
            foreach ($campaign->getAssets() as $asset) {
                if ($asset->getCredits() < 0) {
                    $this->addFlash('error', 'HARD DECK BREACHED. Asset ' . $asset->getName() . ' is operating below fiscal viability protocols. Seizure risk: IMMINENT.');
                }
            }
            // For now, let's keep it simple. If we had a service method, we'd call it.

            return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
        }

        if ($calendarForm->isSubmitted() && !$calendarForm->isValid()) {
            $this->flashFormErrors($calendarForm);
        }

        $page = $listViewHelper->getPage($request);
        $perPage = 5; // Smaller page size for timelines

        $logResult = $em->getRepository(CampaignSessionLog::class)->findForCampaign($campaign, $page, $perPage);
        $sessionLogs = $logResult['items'];
        $totalLogs = $logResult['total'];

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $totalLogs);

        return $this->renderTurbo('campaign/details.html.twig', [
            'campaign' => $campaign,
            'form' => $assetForm,
            'calendar_form' => $calendarForm,
            'session_logs' => $sessionLogs,
            'pagination' => $pagination,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCampaignSnapshot(Campaign $campaign, EntityManagerInterface $em): array
    {
        $assets = [];

        foreach ($campaign->getAssets() as $asset) {
            $assets[] = [
                'id' => $asset->getId(),
                'code' => $asset->getCode(),
                'name' => $asset->getName(),
                'type' => $asset->getType(),
                'class' => $asset->getClass(),
                'price' => $asset->getPrice(),

                'assetDetails' => $asset->getAssetDetails(),
                'mortgage' => $this->mapMortgage($asset->getMortgage()),
                'crews' => $asset->getCrews()->map(fn(Crew $crew) => $this->mapCrew($crew))->toArray(),
                'routes' => $asset->getRoutes()->map(fn(NavRoute $route) => $this->mapRoute($route))->toArray(),
                'amendments' => $asset->getAmendments()->map(fn(AssetAmendment $amendment) => $this->mapAmendment($amendment))->toArray(),
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
            'assets' => $assets,
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
            'assetShares' => $mortgage->getAssetShares(),
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
            'roles' => $crew->getAssetRoles()->map(fn($role) => $role->getCode())->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */


    /**
     * @return array<string, mixed>
     */
    private function mapAmendment(AssetAmendment $amendment): array
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


    #[Route('/campaign/asset/{id}/remove', name: 'app_campaign_asset_remove', methods: ['GET', 'POST'])]
    public function removeAsset(
        int $id,
        EntityManagerInterface $em,
        CrewAssignmentService $crewAssignmentService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        $campaign = $asset->getCampaign();
        if (!$campaign) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(AssetVoter::CAMPAIGN_REMOVE, $asset);

        $crewAssignmentService->deactivateCrew($asset);
        $campaign->removeAsset($asset);
        $em->persist($campaign);
        $em->flush();

        return $this->redirectToRoute('app_campaign_details', ['id' => $campaign->getId()]);
    }
    private function mapRoute(NavRoute $route): array
    {
        return [
            'id' => $route->getId(),
            'name' => $route->getName(),
            'startHex' => $route->getStartHex(),
            'destHex' => $route->getDestHex(),
            'startDay' => $route->getStartDay(),
            'startYear' => $route->getStartYear(),
            'destDay' => $route->getDestDay(),
            'destYear' => $route->getDestYear(),
            'plannedAt' => $route->getPlannedAt()?->format('Y-m-d H:i:s'),
            'waypoints' => $route->getWaypoints()->map(fn(RouteWaypoint $wp) => [
                'hex' => $wp->getHex(),
                'systemName' => $wp->getWorld(),
                'position' => $wp->getPosition(),
            ])->toArray(),
        ];
    }
}
