<?php

namespace App\Controller;

use App\Entity\Income;
use App\Entity\Campaign;
use App\Entity\Asset;
use App\Form\IncomeType;
use App\Security\Voter\IncomeVoter;
use App\Entity\IncomeCategory;
use App\Service\ImperialDateHelper;
use App\Service\Pdf\PdfGeneratorInterface;
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
        private readonly \App\Service\IncomePlaceholderService $incomePlaceholderService,
        private readonly \App\Service\FinancialAccountManager $accountManager
    ) {}

    // Rimosso DETAIL_FIELDS: i dettagli sono ora unificati nella colonna JSON 'details'.

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

        $faId = $request->query->get('financialAccount');
        if ($faId) {
            $fa = $em->getRepository(\App\Entity\FinancialAccount::class)->find($faId);
            if ($fa && $fa->getUser() === $user) {
                $income->setFinancialAccount($fa);
            }
        }

        $form = $this->createForm(IncomeType::class, $income, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Gestione CREDIT ACCOUNT (Chi riceve / Asset Account)
            $financialAccount = $form->get('financialAccount')->getData();
            $asset = $form->get('asset')->getData(); // Field mapped=false ma accessibile se aggiunto alla form

            if (!$financialAccount) {
                $bank = $form->get('bank')->getData();
                $bankName = $form->get('bankName')->getData();

                // Se abbiamo dati per la banca, creiamo/aggiorniamo il conto per l'asset
                // Nota: In Income, l'asset potrebbe essere null se non selezionato, ma assumiamo che l'utente voglia legare il conto a un asset se presente.
                // Se l'asset è presente nel form data (mapped=false o true), usiamolo.
                // IncomeType ha 'asset' field. 

                if (($bank || $bankName) && $asset) {
                    $financialAccount = $this->accountManager->updateForAsset(
                        $asset,
                        $user,
                        null,
                        $bank,
                        $bankName
                    );
                }
            }

            if ($financialAccount) {
                $income->setFinancialAccount($financialAccount);
            }

            // Gestione PAYER (Chi paga)
            $payerCompany = $form->get('company')->getData();
            if (!$payerCompany) {
                // Se non è selezionata una company, controlliamo se l'utente vuole crearne una al volo (Alias + Role)
                $alias = $form->get('patronAlias')->getData();
                $role = $form->get('payerCompanyRole')->getData();

                if ($alias && $role) {
                    $companyRepo = $em->getRepository(\App\Entity\Company::class);
                    // Controllo duplicati: cerchiamo se esiste già una company con questo nome e ruolo per l'utente
                    $existingCompany = $companyRepo->findOneByNormalizedName($alias, $user, $role->getCode());

                    if ($existingCompany) {
                        $income->setCompany($existingCompany);
                    } else {
                        $newCompany = new \App\Entity\Company();
                        $newCompany->setName($alias);
                        $newCompany->setCompanyRole($role);
                        $newCompany->setUser($user);
                        $newCompany->setIsAutoGenerated(true);
                        $newCompany->setContact($alias . ' contact'); // Default required fields
                        $newCompany->setSignLabel($alias); // Default

                        $em->persist($newCompany);
                        $income->setCompany($newCompany);
                    }

                    // Puliamo l'alias testuale dall'Income perché ora è una Company reale
                    $income->setPatronAlias(null);
                }
            }

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

            // Gestione CREDIT ACCOUNT (Chi riceve)
            $financialAccount = $form->get('financialAccount')->getData();
            // In Edit, l'asset è probabilmente già legato al conto, ma controlliamo se l'utente vuole cambiarlo/crearne uno nuovo.
            $asset = $form->get('asset')->getData();

            if (!$financialAccount) {
                $bank = $form->get('bank')->getData();
                $bankName = $form->get('bankName')->getData();

                if (($bank || $bankName) && $asset) {
                    $financialAccount = $this->accountManager->updateForAsset(
                        $asset,
                        $user,
                        null,
                        $bank,
                        $bankName
                    );
                }
            }

            if ($financialAccount) {
                $income->setFinancialAccount($financialAccount);
            }

            $this->clearUnusedDetails($income, $em);
            $em->flush();

            return $this->redirectToRoute('app_income_index');
        }

        return $this->renderTurbo('income/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,

            'income' => $income,
            'form' => $form,
            'asset' => $income->getFinancialAccount()?->getAsset(),
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
        PdfGeneratorInterface $pdfGenerator
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

    /**
     * Pulisce i dettagli se la categoria è cambiata, per evitare residui JSON incoerenti.
     */
    private function clearUnusedDetails(Income $income, EntityManagerInterface $em): void
    {
        // Se stiamo usando JSONb, il controllo è più semplice: 
        // Se la categoria cambia radicalmente, azzeriamo l'array details.
        // In questo scenario, la form ripopolerà l'array details con i nuovi dati.

        // Nota: Il subscriber e la form gestiscono gran parte della logica.
        // Qui ci assicuriamo solo che relazioni come purchaseCost vengano resettate se non più pertinenti.
        if ($income->getIncomeCategory()?->getCode() !== 'TRADE') {
            $income->setPurchaseCost(null);
        }
    }
}
