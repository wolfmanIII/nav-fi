<?php

namespace App\Controller;

use App\Entity\Cost;
use App\Form\CostType;
use App\Security\Voter\CostVoter;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\PdfGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CostController extends BaseController
{
    public const CONTROLLER_NAME = 'CostController';

    #[Route('/cost/index', name: 'app_cost_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $costs = $user ? $em->getRepository(Cost::class)->findAllForUser($user) : [];

        return $this->render('cost/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'costs' => $costs,
        ]);
    }

    #[Route('/cost/new', name: 'app_cost_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $cost = new Cost();
        $form = $this->createForm(CostType::class, $cost, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($cost);
            $em->flush();

            return $this->redirectToRoute('app_cost_index');
        }

        return $this->renderTurbo('cost/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'cost' => $cost,
            'form' => $form,
        ]);
    }

    #[Route('/cost/edit/{id}', name: 'app_cost_edit', methods: ['GET', 'POST'])]
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

        $cost = $em->getRepository(Cost::class)->findOneForUser($id, $user);
        if (!$cost) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CostVoter::EDIT, $cost);

        $form = $this->createForm(CostType::class, $cost, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_cost_index');
        }

        return $this->renderTurbo('cost/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'cost' => $cost,
            'form' => $form,
        ]);
    }

    #[Route('/cost/delete/{id}', name: 'app_cost_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $cost = $em->getRepository(Cost::class)->findOneForUser($id, $user);
        if (!$cost) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CostVoter::DELETE, $cost);

        $em->remove($cost);
        $em->flush();

        return $this->redirectToRoute('app_cost_index');
    }

    #[Route('/cost/{id}/pdf', name: 'app_cost_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        PdfGenerator $pdfGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $cost = $em->getRepository(Cost::class)->findOneForUser($id, $user);
        if (!$cost) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(CostVoter::EDIT, $cost);

        $pdf = $pdfGenerator->render('pdf/cost/SHEET.html.twig', [
            'cost' => $cost,
            'locale' => $request->getLocale(),
        ], [
            'margin-top' => '14mm',
            'margin-bottom' => '14mm',
            'margin-left' => '10mm',
            'margin-right' => '10mm',
            'footer-right' => 'Page [page] / [toPage]',
            'footer-font-size' => 8,
            'footer-spacing' => 8,
            'disable-smart-shrinking' => true,
            'enable-local-file-access' => true,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="cost-%s.pdf"', $cost->getCode()),
        ]);
    }
}
