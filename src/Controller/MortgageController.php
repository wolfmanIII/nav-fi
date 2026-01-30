<?php

namespace App\Controller;

use App\Entity\Mortgage;
use App\Entity\MortgageInstallment;
use App\Entity\Campaign;
use App\Entity\Asset;
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
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Form\Config\DayYearLimits;
use App\Service\Pdf\PdfGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

final class MortgageController extends BaseController
{
    const CONTROLLER_NAME = "MortgageController";

    #[Route('/mortgage/index', name: 'app_mortgage_index')]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'asset' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $mortgages = [];
        $total = 0;
        $totalPages = 1;
        $assets = [];
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

            $assets = $em->getRepository(Asset::class)->findAllForUser($user);
            $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('mortgage/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgages' => $mortgages,
            'filters' => $filters,
            'assets' => $assets,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/mortgage/new', name: 'app_mortgage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, \App\Service\FinancialAccountManager $accountManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = new Mortgage();
        $form = $this->createForm(MortgageType::class, $mortgage, ['user' => $user]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $mortgage->setName("MOR - " . $mortgage->getAsset()->getName());
            $mortgage->setUser($user);

            // 1. Gestione DEBIT ACCOUNT (Chi paga)
            // Se selezionato account esistente, usa quello
            $financialAccount = $form->get('financialAccount')->getData();

            // Se non selezionato, prova a creare/aggiornare basandosi su bank/bankName
            if (!$financialAccount) {
                $bank = $form->get('bank')->getData();
                $bankName = $form->get('bankName')->getData();

                // Se abbiamo dati per la banca, creiamo/aggiorniamo il conto per l'asset
                if ($bank || $bankName) {
                    $financialAccount = $accountManager->updateForAsset(
                        $mortgage->getAsset(),
                        $user,
                        null, // Non cambiamo i crediti
                        $bank,
                        $bankName,
                        $mortgage->getAsset()->getCampaign()
                    );
                } else {
                    // Fallback: se l'asset ha già un conto, usa quello
                    $financialAccount = $mortgage->getAsset()->getFinancialAccount();
                }
            }

            // Se ancora null e non abbiamo dati, creiamo un conto "orfano" (legacy logic) o errore?
            // La logica originale creava un conto vuoto se mancava. Manteniamola per sicurezza se l'asset è orfano.
            if (!$financialAccount && $mortgage->getAsset()) {
                // Oppure usiamo FAM per creare un conto default senza banca?
                // Per ora manteniamo la logica base se l'utente non ha specificato nulla, ma FAM richiede banca o nome.
                // Se l'utente non seleziona nulla, lasciamo che il mutuo non abbia conto o creiamo uno vuoto?
                // Logica precedente: creava new FinancialAccount senza bank.
                // Facciamo fallback a creazione manuale base se FAM non può operare
                $financialAccount = new \App\Entity\FinancialAccount();
                $financialAccount->setUser($user);
                $financialAccount->setAsset($mortgage->getAsset());
                $em->persist($financialAccount);
            }

            if ($financialAccount) {
                $mortgage->setFinancialAccount($financialAccount);
            }


            // 2. Gestione CREDIT INSTITUTION (Chi riceve / Lender)
            $lender = $mortgage->getCompany();
            if (!$lender) {
                // Se non selezionato, controlla campo custom name
                $newLenderName = $form->get('creditInstitutionName')->getData();
                if ($newLenderName) {
                    // Usa FAM per risolvere/creare la banca
                    $lender = $accountManager->resolveBank(null, $newLenderName, $user);
                    $mortgage->setCompany($lender);
                }
            }

            $em->persist($mortgage);
            $em->flush();
            return $this->redirectToRoute('app_mortgage_index');
        }

        $payment = new MortgageInstallment();
        $payment->setMortgage($mortgage);

        $summary = $mortgage->calculate();
        $paymentForm = $this->createForm(MortgageInstallmentType::class, $payment, ['summary' => $summary]);

        return $this->renderTurbo('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form,
            'payment_form' => $paymentForm->createView(),
            'start_date_form' => null,
            'sign_form' => null,
            'last_payment' => $mortgage->getMortgageInstallments()->last(),
            'asset' => $mortgage->getAsset(),
        ]);
    }

    #[Route('/mortgage/edit/{id}', name: 'app_mortgage_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        DayYearLimits $limits,
        \App\Service\FinancialAccountManager $accountManager
    ): Response {
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

            // 1. Gestione DEBIT ACCOUNT (Chi paga)
            $financialAccount = $form->get('financialAccount')->getData();

            if (!$financialAccount) {
                $bank = $form->get('bank')->getData();
                $bankName = $form->get('bankName')->getData();

                if ($bank || $bankName) {
                    $financialAccount = $accountManager->updateForAsset(
                        $mortgage->getAsset(),
                        $user,
                        null,
                        $bank,
                        $bankName,
                        $mortgage->getAsset()->getCampaign()
                    );
                }
            }

            if ($financialAccount) {
                $mortgage->setFinancialAccount($financialAccount);
            }

            // 2. Gestione CREDIT INSTITUTION (Chi riceve / Lender)
            // 'company' è mapped, quindi se selezionato è già in $mortgage->getCompany()
            if (!$mortgage->getCompany()) {
                $newLenderName = $form->get('creditInstitutionName')->getData();
                if ($newLenderName) {
                    $lender = $accountManager->resolveBank(null, $newLenderName, $user);
                    $mortgage->setCompany($lender);
                }
            }

            $em->flush();
            $this->addFlash('warning', 'LEDGER INTEGRITY EVENT. Correction appended. Original error archived for forensic audit.');

            return $this->redirectToRoute('app_mortgage_index');
        }



        $summary = $mortgage->calculate();

        $payment = new MortgageInstallment();
        $payment->setMortgage($mortgage);
        $paymentForm = $this->createForm(MortgageInstallmentType::class, $payment, ['summary' => $summary]);

        // Sign Form (Integrated with Start Date)
        $signForm = $this->container->get('form.factory')->createNamedBuilder('mortgage_sign')
            ->setAction($this->generateUrl('app_mortgage_sign', ['id' => $mortgage->getId()]))
            ->setMethod('POST')
            ->add('signingLocation', TextType::class, [
                'required' => false,
                'label' => 'Signing Location (Orbit/Station)',
            ])
            ->add('startDate', ImperialDateType::class, [
                'label' => 'First Installment Date (Optional)',
                'min_year' => $limits->getYearMin(),
                'max_year' => $limits->getYearMax(),
                'required' => false,
            ])
            ->getForm();

        // Start Date Form for Modal
        $startImperialDate = new ImperialDate($mortgage->getStartYear(), $mortgage->getStartDay());
        $startDateForm = $this->container->get('form.factory')->createNamedBuilder('mortgage_set_start_date', FormType::class, ['startDate' => $startImperialDate])
            ->setAction($this->generateUrl('app_mortgage_set_start_date', ['id' => $mortgage->getId()]))
            ->setMethod('POST')
            ->add('startDate', ImperialDateType::class, [
                'label' => false,
                'min_year' => $limits->getYearMin(),
                'max_year' => $limits->getYearMax(),
                'required' => false,
            ])
            ->getForm();

        return $this->renderTurbo('mortgage/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'mortgage' => $mortgage,
            'summary' => $summary,
            'form' => $form,
            'payment_form' => $paymentForm->createView(),
            'start_date_form' => $startDateForm->createView(),
            'sign_form' => $signForm->createView(),
            'last_payment' => $mortgage->getMortgageInstallments()->last(),
            'asset' => $mortgage->getAsset(),
        ]);
    }

    #[Route('/mortgage/delete/{id}', name: 'app_mortgage_delete', methods: ['GET', 'POST'])]
    public function delete(
        int $id,
        EntityManagerInterface $em
    ): Response {
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
    ): Response {
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
        } elseif ($paymentForm->isSubmitted()) {
            $this->flashFormErrors($paymentForm);
        }

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }

    #[Route('/mortgage/{id}/sign', name: 'app_mortgage_sign', methods: ['POST'])]
    public function sign(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        DayYearLimits $limits
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $form = $this->container->get('form.factory')->createNamedBuilder('mortgage_sign')
            ->add('signingLocation', TextType::class, [
                'required' => false,
            ])
            ->add('startDate', ImperialDateType::class, [
                'min_year' => $limits->getYearMin(),
                'max_year' => $limits->getYearMax(),
                'required' => false,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $campaign = $mortgage->getAsset()?->getCampaign();

            if (!$campaign) {
                $this->addFlash('error', 'Asset is not assigned to a campaign.');
                return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
            }

            // Set Signing Date from Session
            $mortgage->setSigningDay($campaign->getSessionDay());
            $mortgage->setSigningYear($campaign->getSessionYear());

            if (!empty($data['signingLocation'])) {
                $mortgage->setSigningLocation($data['signingLocation']);
            }

            // Handle Optional Start Date
            /** @var ImperialDate|null $startDate */
            $startDate = $data['startDate'];
            if ($startDate instanceof ImperialDate && $startDate->getDay() !== null && $startDate->getYear() !== null) {
                // Validate StartDate >= SigningDate
                $startDay = $startDate->getDay();
                $startYear = $startDate->getYear();
                $signingDay = $mortgage->getSigningDay();
                $signingYear = $mortgage->getSigningYear();

                if ($startYear < $signingYear || ($startYear === $signingYear && $startDay < $signingDay)) {
                    $this->addFlash('error', 'Mortgage Signed, BUT Start Date was invalid (must be on or after Signing Date ' . sprintf('%03d/%s', $signingDay, $signingYear) . ').');
                } else {
                    $mortgage->setStartDay($startDay);
                    $mortgage->setStartYear($startYear);
                    $this->addFlash('success', 'First installment date set correctly: ' . sprintf('%03d/%s', $startDay, $startYear));
                }
            }

            $em->flush();
            $this->addFlash('success', 'Mortgage signed successfully.');
        } else {
            $this->addFlash('error', 'Form invalid.');
            $this->flashFormErrors($form);
        }

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }

    #[Route('/mortgage/{id}/set-start-date', name: 'app_mortgage_set_start_date', methods: ['POST'])]
    public function setStartDate(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        DayYearLimits $limits
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted(MortgageVoter::SET_START_DATE, $mortgage);

        $startDateForm = $this->container->get('form.factory')->createNamedBuilder('mortgage_set_start_date', FormType::class, ['startDate' => new ImperialDate($mortgage->getStartYear(), $mortgage->getStartDay())])
            ->add('startDate', ImperialDateType::class, [
                'min_year' => $limits->getYearMin(),
                'max_year' => $limits->getYearMax(),
                'required' => false,
            ])
            ->getForm();

        $startDateForm->handleRequest($request);

        if ($startDateForm->isSubmitted() && $startDateForm->isValid()) {
            $data = $startDateForm->getData();
            /** @var ImperialDate|null $date */
            $date = $data['startDate'];

            if ($date instanceof ImperialDate && $date->getDay() !== null && $date->getYear() !== null) {
                $startDay = $date->getDay();
                $startYear = $date->getYear();

                $signingDay = $mortgage->getSigningDay();
                $signingYear = $mortgage->getSigningYear();

                if ($signingYear !== null && $signingDay !== null) {
                    if ($startYear < $signingYear || ($startYear === $signingYear && $startDay < $signingDay)) {
                        $this->addFlash('error', 'Start Date must be on or after the Signing Date (' . sprintf('%03d/%s', $signingDay, $signingYear) . ').');
                        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
                    }
                }

                $mortgage->setStartDay($startDay);
                $mortgage->setStartYear($startYear);
                $this->addFlash('success', 'First installment date set correctly: ' . sprintf('%03d/%s', $startDay, $startYear));
            } else {
                $mortgage->setStartDay(null);
                $mortgage->setStartYear(null);
                $this->addFlash('info', 'First installment date has been cleared.');
            }

            $em->persist($mortgage);
            $em->flush();
        } else {
            $this->addFlash('error', 'Invalid date provided.');
            $this->flashFormErrors($startDateForm);
        }

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }

    #[Route('/mortgage/{id}/pdf', name: 'app_mortgage_pdf', methods: ['GET'])]
    public function pdf(
        int $id,
        EntityManagerInterface $em,
        PdfGeneratorInterface $pdfGenerator,
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
            'asset' => $mortgage->getAsset(),
            'user' => $user,
            'locale' => $request->getLocale(),
            'watermark' => '',
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
            'asset' => $mortgage->getAsset(),
            'user' => $user,
            'locale' => $request->getLocale(),
            'watermark' => '',
        ]);
    }

    #[Route('/mortgage/{id}/unsign', name: 'app_mortgage_unsign', methods: ['GET'])]
    public function unsign(
        int $id,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $mortgage = $em->getRepository(Mortgage::class)->findOneForUser($id, $user);
        if (!$mortgage) {
            throw new NotFoundHttpException();
        }


        $mortgage->setSigningDay(null);
        $mortgage->setSigningYear(null);
        $mortgage->setSigningLocation(null);
        $em->flush();

        $this->addFlash('info', 'Mortgage signature cleared.');

        return $this->redirectToRoute('app_mortgage_edit', ['id' => $mortgage->getId()]);
    }
}
