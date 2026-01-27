<?php

namespace App\Controller;

use App\Entity\FinancialAccount;
use App\Form\FinancialAccountType;
use App\Repository\FinancialAccountRepository;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/financial-account')]
class FinancialAccountController extends BaseController
{
    const CONTROLLER_NAME = 'FinancialAccountController';

    #[Route('/', name: 'app_financial_account_index', methods: ['GET'])]
    public function index(FinancialAccountRepository $financialAccountRepository, Request $request, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        //$page = $listViewHelper->getPage($request);
        //$perPage = 20;

        // For now, simple findAll for user. Repository logic for user filtering needs to be checked or implemented via standard findBy.
        $accounts = $financialAccountRepository->findBy(['user' => $user], ['id' => 'DESC']);

        return $this->render('financial_account/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'accounts' => $accounts,
        ]);
    }

    #[Route('/new', name: 'app_financial_account_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $financialAccount = new FinancialAccount();
        $financialAccount->setUser($this->getUser());
        $form = $this->createForm(FinancialAccountType::class, $financialAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($financialAccount);
            $entityManager->flush();

            return $this->redirectToRoute('app_financial_account_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('financial_account/edit.html.twig', [
            'financial_account' => $financialAccount,
            'form' => $form,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_financial_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, FinancialAccount $financialAccount, EntityManagerInterface $entityManager): Response
    {
        if ($financialAccount->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(FinancialAccountType::class, $financialAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_financial_account_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('financial_account/edit.html.twig', [
            'financial_account' => $financialAccount,
            'form' => $form,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/{id}', name: 'app_financial_account_delete', methods: ['POST'])]
    public function delete(Request $request, FinancialAccount $financialAccount, EntityManagerInterface $entityManager): Response
    {
        if ($financialAccount->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $financialAccount->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($financialAccount);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_financial_account_index', [], Response::HTTP_SEE_OTHER);
    }
}
