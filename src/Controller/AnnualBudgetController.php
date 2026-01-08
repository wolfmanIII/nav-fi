<?php

namespace App\Controller;

use App\Entity\AnnualBudget;
use App\Entity\Campaign;
use App\Entity\Cost;
use App\Entity\Income;
use App\Entity\MortgageInstallment;
use App\Entity\Ship;
use App\Form\AnnualBudgetType;
use App\Security\Voter\AnnualBudgetVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AnnualBudgetController extends BaseController
{
    public const CONTROLLER_NAME = 'AnnualBudgetController';

    #[Route('/annual-budget/index', name: 'app_annual_budget_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'ship' => ['type' => 'int'],
            'start',
            'end',
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $budgets = [];
        $total = 0;
        $totalPages = 1;
        $ships = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(AnnualBudget::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $budgets = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(AnnualBudget::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $budgets = $result['items'];
            }

            $ships = $em->getRepository(Ship::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('annual_budget/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'budgets' => $budgets,
            'filters' => $filters,
            'ships' => $ships,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/annual-budget/new', name: 'app_annual_budget_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $budget = new AnnualBudget();
        $form = $this->createForm(AnnualBudgetType::class, $budget, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($budget);
            $em->flush();

            return $this->redirectToRoute('app_annual_budget_index');
        }

        return $this->renderTurbo('annual_budget/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'budget' => $budget,
            'form' => $form,
        ]);
    }

    #[Route('/annual-budget/edit/{id}', name: 'app_annual_budget_edit', methods: ['GET', 'POST'])]
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

        $budget = $em->getRepository(AnnualBudget::class)->findOneForUser($id, $user);
        if (!$budget) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(AnnualBudgetVoter::EDIT, $budget);

        $form = $this->createForm(AnnualBudgetType::class, $budget, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_annual_budget_index');
        }

        return $this->renderTurbo('annual_budget/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'budget' => $budget,
            'form' => $form,
        ]);
    }

    #[Route('/annual-budget/delete/{id}', name: 'app_annual_budget_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $budget = $em->getRepository(AnnualBudget::class)->findOneForUser($id, $user);
        if (!$budget) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(AnnualBudgetVoter::DELETE, $budget);

        $em->remove($budget);
        $em->flush();

        return $this->redirectToRoute('app_annual_budget_index');
    }

    #[Route('/annual-budget/chart/{id}', name: 'app_annual_budget_chart', methods: ['GET'])]
    public function chart(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $budget = $em->getRepository(AnnualBudget::class)->findOneForUser($id, $user);
        if (!$budget) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(AnnualBudgetVoter::VIEW, $budget);

        $incomes = $em->getRepository(Income::class)->findAllNotCanceledForUser($user);
        $costs = $em->getRepository(Cost::class)->findAllForUser($user);
        $installments = $em->getRepository(MortgageInstallment::class)->findAllForUser($user);

        [$labels, $incomeSeries, $costSeries] = $this->buildSeries($budget, $incomes, $costs, $installments);

        return $this->render('annual_budget/chart.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'budget' => $budget,
            'labels' => $labels,
            'incomeSeries' => $incomeSeries,
            'costSeries' => $costSeries,
        ]);
    }

    private function buildSeries(AnnualBudget $budget, array $incomes, array $costs, array $installments): array
    {
        $startKey = $this->keyFromDayYear($budget->getStartDay(), $budget->getStartYear());
        $endKey = $this->keyFromDayYear($budget->getEndDay(), $budget->getEndYear());
        $labels = [];
        $incomeMap = [];
        $costMap = [];

        foreach ($incomes as $income) {
            if ($income->getShip()?->getId() !== $budget->getShip()?->getId()) {
                continue;
            }
            $day = $income->getPaymentDay() ?? $income->getSigningDay();
            $year = $income->getPaymentYear() ?? $income->getSigningYear();
            $key = $this->keyFromDayYear($day, $year);
            if ($key === null || $key < $startKey || $key > $endKey) {
                continue;
            }
            $incomeMap[$key] = ($incomeMap[$key] ?? 0) + (float) $income->getAmount();
            $labels[$key] = sprintf('%s/%s', $day, $year);
        }

        foreach ($costs as $cost) {
            if ($cost->getShip()?->getId() !== $budget->getShip()?->getId()) {
                continue;
            }
            $day = $cost->getPaymentDay() ?? null;
            $year = $cost->getPaymentYear() ?? null;
            if ($day === null || $year === null) {
                continue;
            }
            $key = $this->keyFromDayYear($day, $year);
            if ($key === null || $key < $startKey || $key > $endKey) {
                continue;
            }
            $costMap[$key] = ($costMap[$key] ?? 0) + (float) $cost->getAmount();
            $labels[$key] = sprintf('%s/%s', $day, $year);
        }

        foreach ($installments as $installment) {
            if ($installment->getMortgage()?->getShip()?->getId() !== $budget->getShip()?->getId()) {
                continue;
            }
            $day = $installment->getPaymentDay();
            $year = $installment->getPaymentYear();
            $key = $this->keyFromDayYear($day, $year);
            if ($key === null || $key < $startKey || $key > $endKey) {
                continue;
            }
            $costMap[$key] = ($costMap[$key] ?? 0) + (float) $installment->getPayment();
            $labels[$key] = sprintf('%s/%s', $day, $year);
        }

        ksort($labels);
        $orderedLabels = array_values($labels);
        $incomeSeries = [];
        $costSeries = [];
        foreach (array_keys($labels) as $key) {
            $incomeSeries[] = $incomeMap[$key] ?? 0;
            $costSeries[] = $costMap[$key] ?? 0;
        }

        return [$orderedLabels, $incomeSeries, $costSeries];
    }

    private function keyFromDayYear(?int $day, ?int $year): ?int
    {
        if ($day === null || $year === null) {
            return null;
        }

        return $year * 1000 + $day;
    }
}
