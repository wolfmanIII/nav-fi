<?php

namespace App\Controller;

use App\Entity\Mortgage;
use App\Form\MortgageType;
use App\Manager\MortgageManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

final class MortgageController extends AbstractController
{
    const CONTROLLER_NAME = "MortgageController";

    #[Route('/mortgage', name: 'app_mortgage_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $mortgages = $em->getRepository(Mortgage::class)->findAll();

        return $this->render('mortgage/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgages' => $mortgages,
        ]);
    }

    #[Route('/mortgage/new', name: 'app_mortgage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $mortgage = new Mortgage();
        $form = $this->createForm(MortgageType::class, $mortgage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $mortgage->setCode(Uuid::v7());
            $mortgage->setName("MOR - " . $mortgage->getShip()->getName());

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_index');
        }

        return $this->render('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mortgage/edit/{id}', name: 'app_mortgage_edit', methods: ['GET', 'POST'])]
    public function edit(Mortgage $mortgage, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MortgageType::class, $mortgage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
        }

        $summary = $mortgage->calculate();

        return $this->render('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mortgage/sign/{id}', name: 'app_mortgage_sign', methods: ['GET', 'POST'])]
    public function sign(Mortgage $mortgage, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MortgageType::class, $mortgage);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $mortgage->setSigned(true);
            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
        }

        $summary = $mortgage->calculate();

        return $this->render('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mortgage/delete/{id}', name: 'app_mortgage_delete', methods: ['GET', 'POST'])]
    public function delete(Mortgage $mortgage, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($mortgage);
        $em->flush();

        return $this->redirectToRoute('app_mortgage_index');
    }
}
