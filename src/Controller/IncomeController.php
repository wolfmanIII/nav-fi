<?php

namespace App\Controller;

use App\Entity\Income;
use App\Entity\Campaign;
use App\Entity\Asset;
use App\Form\IncomeType;
use App\Security\Voter\IncomeVoter;
use App\Entity\IncomeCategory;
use App\Service\ImperialDateHelper;
use App\Service\PdfGenerator;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class IncomeController extends BaseController
{
    public const CONTROLLER_NAME = 'IncomeController';

    public function __construct(
        private readonly ImperialDateHelper $imperialDateHelper,
        private readonly \App\Service\IncomePlaceholderService $incomePlaceholderService
    ) {}

    /**
     * @var array<string, string>
     */
    private const DETAIL_FIELDS = [
        'CHARTER' => 'charterDetails',
        'SUBSIDY' => 'subsidyDetails',
        'FREIGHT' => 'freightDetails',
        'PASSENGERS' => 'passengersDetails',
        'SERVICES' => 'servicesDetails',
        'INSURANCE' => 'insuranceDetails',
        'MAIL' => 'mailDetails',
        'INTEREST' => 'interestDetails',
        'TRADE' => 'tradeDetails',
        'SALVAGE' => 'salvageDetails',
        'PRIZE' => 'prizeDetails',
        'CONTRACT' => 'contractDetails',
    ];

    #[Route('/income/index', name: 'app_income_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'title',
            'category' => ['type' => 'int'],
            'asset' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $incomes = [];
        $total = 0;
        $categories = [];
        $assets = [];
        $campaigns = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Income::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $incomes = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Income::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $incomes = $result['items'];
            }

            $categories = $em->getRepository(IncomeCategory::class)->findBy([], ['code' => 'ASC']);
            $assets = $em->getRepository(Asset::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('income/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'incomes' => $incomes,
            'filters' => $filters,
            'categories' => $categories,
            'assets' => $assets,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
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
        $categoryParam = $request->query->get('category');
        if ($categoryParam) {
            $category = $em->getRepository(IncomeCategory::class)->find($categoryParam);
            if ($category) {
                $income->setIncomeCategory($category);
                $this->clearUnusedDetails($income, $em);
            }
        }
        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearUnusedDetails($income, $em);
            $em->persist($income);
            $em->flush();
            $this->addFlash('warning', 'LEDGER INTEGRITY EVENT. Correction appended. Original error archived for forensic audit.');

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
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = $em->getRepository(Income::class)->findOneForUser($id, $user);
        if (!$income) {
            throw new NotFoundHttpException();
        }

        $categoryParam = $request->query->get('category');
        if ($categoryParam) {
            $category = $em->getRepository(IncomeCategory::class)->find($categoryParam);
            if ($category) {
                $income->setIncomeCategory($category);
            }
        }

        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->clearUnusedDetails($income, $em);
            $em->flush();

            return $this->redirectToRoute('app_income_index');
        }

        return $this->renderTurbo('income/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,

            'income' => $income,
            'form' => $form,
            'asset' => $income->getAsset(),
        ]);
    }

    #[Route('/income/delete/{id}', name: 'app_income_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response {
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

    #[Route('/income/{id}/pdf', name: 'app_income_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        PdfGenerator $pdfGenerator
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $income = $em->getRepository(Income::class)->findOneForUser($id, $user);
        if (!$income) {
            throw new NotFoundHttpException();
        }

        // Refactored to use service
        $template = $this->incomePlaceholderService->resolveTemplatePath($income);
        $placeholders = $this->incomePlaceholderService->buildPlaceholderMap($income);

        $html = $this->renderView($template, ['render_watermark_placeholder' => true]);
        $html = strtr($html, $placeholders);

        $pdf = $pdfGenerator->renderFromHtml($html, [
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
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename=\"income-%s.pdf\"', $income->getCode()),
        ]);
    }

    private function clearUnusedDetails(Income $income, EntityManagerInterface $em): void
    {
        $currentCode = $income->getIncomeCategory()?->getCode();
        if (!$currentCode) {
            return;
        }

        foreach (self::DETAIL_FIELDS as $code => $property) {
            if ($code === $currentCode) {
                continue;
            }

            $getter = 'get' . ucfirst($property);
            $setter = 'set' . ucfirst($property);

            if (!method_exists($income, $getter) || !method_exists($income, $setter)) {
                continue;
            }

            $detail = $income->$getter();
            if ($detail) {
                $em->remove($detail);
                $income->$setter(null);
            }
        }
    }
}
