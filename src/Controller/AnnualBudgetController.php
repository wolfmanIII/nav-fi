<?php

namespace App\Controller;

use App\Entity\AnnualBudget;
use App\Form\AnnualBudgetType;
use App\Security\Voter\AnnualBudgetVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AnnualBudgetController extends BaseController
{
    public const CONTROLLER_NAME = 'AnnualBudgetController';

    #[Route('/annual-budget/index', name: 'app_annual_budget_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $budgets = $user ? $em->getRepository(AnnualBudget::class)->findAllForUser($user) : [];

        return $this->render('annual_budget/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'budgets' => $budgets,
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
}
