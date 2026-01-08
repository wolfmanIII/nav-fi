<?php

namespace App\Controller;

use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\Campaign;
use App\Entity\Ship;
use App\Form\MortgageInstallmentType;
use App\Form\MortgageType;
use App\Security\Voter\MortgageVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MortgageController extends BaseController
{
    const CONTROLLER_NAME = "MortgageController";

    #[Route('/mortgage/index', name: 'app_mortgage_index')]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'ship' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $mortgages = [];
        $total = 0;
        $totalPages = 1;
        $ships = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Mortgage::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $mortgages = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Mortgage::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $mortgages = $result['items'];
            }

            $ships = $em->getRepository(Ship::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('mortgage/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgages' => $mortgages,
            'filters' => $filters,
            'ships' => $ships,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/mortgage/new', name: 'app_mortgage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = new Mortgage();
        $form = $this->createForm(MortgageType::class, $mortgage, ['user' => $user]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $mortgage->setName("MOR - " . $mortgage->getShip()->getName());

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_index');
        }

        $payment = new MortgageInstallment();
        $payment->setMortgage($mortgage);

        return $this->renderTurbo('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'form' => $form,
            'last_payment' => $mortgage->getMortgageInstallments()->last(),
        ]);
    }

    #[Route('/mortgage/edit/{id}', name: 'app_mortgage_edit', methods: ['GET', 'POST'])]
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

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(MortgageType::class, $mortgage, ['user' => $user]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if (
                !$this->isGranted(MortgageVoter::EDIT, $mortgage)
            ) {
                $this->addFlash('error', 'Mortgage Signed, Action Denied!');
                return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
            }

            $action = $request->request->get('action');

            if ($action === 'sign') {
                $signingLocation = trim((string)$request->request->get('signing_location'));
                if ($signingLocation === '') {
                    $this->addFlash('error', 'Signing location is required to sign.');
                    return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
                }

                $mortgage->setSigned(true);

                $campaign = $mortgage->getShip()?->getCampaign();
                if ($campaign) {
                    $mortgage->setSigningDay($campaign->getSessionDay());
                    $mortgage->setSigningYear($campaign->getSessionYear());
                }

                $mortgage->setSigningLocation($signingLocation ?: null);
            }

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
        }

        $summary = $mortgage->calculate();

        $payment = new MortgageInstallment();
        $payment->setMortgage($mortgage);
        $paymentForm = $this->createForm(MortgageInstallmentType::class, $payment, ['summary' => $summary]);

        return $this->renderTurbo('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form,
            'payment_form' => $paymentForm->createView(),
            'last_payment' => $mortgage->getMortgageInstallments()->last(),
        ]);
    }

    #[Route('/mortgage/delete/{id}', name: 'app_mortgage_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(MortgageVoter::DELETE, $mortgage);

        $em->remove($mortgage);
        $em->flush();

        return $this->redirectToRoute('app_mortgage_index');
    }

    #[Route('/mortgage/{id}/pay', name: 'app_mortgage_pay', methods: ['GET', 'POST'])]
    public function pay(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $payment = new MortgageInstallment();
        $payment->setMortgage($mortgage);
        $paymentForm = $this->createForm(MortgageInstallmentType::class, $payment);

        $paymentForm->handleRequest($request);
        if ($paymentForm->isSubmitted() && $paymentForm->isValid()) {
            $em->persist($payment);
            $em->flush();
        }

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }

    #[Route('/mortgage/{id}/pdf', name: 'app_mortgage_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        \App\Service\PdfGenerator $pdfGenerator,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $htmlTemplate = 'pdf/contracts/MORTGAGE.html.twig';
        $context = [
            'mortgage' => $mortgage,
            'ship' => $mortgage->getShip(),
            'user' => $user,
            'locale' => $request->getLocale(),
        ];

        $options = [
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
        ];

        $pdfContent = $pdfGenerator->render($htmlTemplate, $context, $options);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"mortgage-%s.pdf\"', $mortgage->getCode()),
        ]);
    }

    #[Route('/mortgage/{id}/pdf/preview', name: 'app_mortgage_pdf_preview', methods: ['GET'])]
    public function pdfPreview(
        int $id,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        return $this->render('pdf/contracts/MORTGAGE.html.twig', [
            'mortgage' => $mortgage,
            'ship' => $mortgage->getShip(),
            'user' => $user,
            'locale' => $request->getLocale(),
        ]);
    }

    #[Route('/mortgage/{id}/unsigned', name: 'app_mortgage_unsigned', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unsigned(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->find($id);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $mortgage->setSigned(false);
        $mortgage->setSigningDay(null);
        $mortgage->setSigningYear(null);
        $mortgage->setSigningLocation(null);
        $em->flush();

        $this->addFlash('info', 'Mortgage signature cleared.');

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }
}
