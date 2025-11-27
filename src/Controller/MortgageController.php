<?php

namespace App\Controller;

use App\Entity\Mortgage;
use App\Form\MortgageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MortgageController extends AbstractController
{
    const CONTROLLER_NAME = "MortgageController";

    #[Route('/mortgage/index', name: 'app_mortgage_index')]
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

            $mortgage->setName("MOR - " . $mortgage->getShip()->getName());
            $mortgage->setSigned(0);

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_index');
        }

        return $this->renderTurboForm('mortgage/edit.html.twig', $form, [
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

            if (!$this->isGranted('MORTGAGE_EDIT', $mortgage)) {
                $this->addFlash('error', 'Mortgage Signed, Action Denied!');
                return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
            }

            $action = $request->request->get('action');

            if ($action === 'sign') {
                $mortgage->setSigned(true);
            }

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
        }

        $summary = $mortgage->calculate();

        return $this->renderTurboForm('mortgage/edit.html.twig', $form, [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form->createView(),
        ]);

    }

    #[Route('/mortgage/delete/{id}', name: 'app_mortgage_delete', methods: ['GET', 'POST'])]
    #[IsGranted('MORTGAGE_DELETE', 'mortgage')]
    public function delete(Mortgage $mortgage, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($mortgage);
        $em->flush();

        return $this->redirectToRoute('app_mortgage_index');
    }

    /**
     * Render del form con supporto a Turbo:
     * - 200 se form non sottomesso
     * - 422 se form sottomesso ma NON valido (altrimenti Turbo non mostra gli errori)
     */
    private function renderTurboForm(string $template, FormInterface $form, array $options): Response
    {
        $response = $this->render($template, $options);

        if ($form->isSubmitted() && !$form->isValid()) {
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        return $response;
    }
}
