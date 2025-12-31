<?php

namespace App\Controller;

use App\Entity\Income;
use App\Form\IncomeType;
use App\Security\Voter\IncomeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class IncomeController extends BaseController
{
    public const CONTROLLER_NAME = 'IncomeController';

    #[Route('/income/index', name: 'app_income_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $incomes = $user ? $em->getRepository(Income::class)->findAllForUser($user) : [];

        return $this->render('income/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'incomes' => $incomes,
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
        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
}
