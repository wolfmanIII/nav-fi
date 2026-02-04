<?php

namespace App\Form;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\EventSubscriber\IncomeDetailsSubscriber;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Campaign;
use App\Entity\FinancialAccount;
use App\Repository\FinancialAccountRepository;

class IncomeType extends AbstractType
{
    public function __construct(
        private readonly IncomeDetailsSubscriber $incomeDetailsSubscriber,
        private readonly DayYearLimits $dayYearLimits,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Income $income */
        $income = $builder->getData();
        $asset = $income?->getFinancialAccount()?->getAsset();
        $campaignStartYear = $asset?->getCampaign()?->getStartingYear();
        $minYear = $campaignStartYear ?? $this->dayYearLimits->getYearMin();

        $signingDate = new ImperialDate($income?->getSigningYear(), $income?->getSigningDay());
        $paymentDate = new ImperialDate($income?->getPaymentYear(), $income?->getPaymentDay());
        $expirationDate = new ImperialDate($income?->getExpirationYear(), $income?->getExpirationDay());
        $cancelDate = new ImperialDate($income?->getCancelYear(), $income?->getCancelDay());

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signingDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Signing date',
                'data' => $signingDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('signingLocation', TextType::class, [
                'required' => true,
                'label' => 'Signing Location',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Payment date',
                'data' => $paymentDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('expirationDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Expiration date',
                'data' => $expirationDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('cancelDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Cancel date',
                'data' => $cancelDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incomeCategory', EntityType::class, [
                'class' => IncomeCategory::class,
                'placeholder' => '// Category',
                'choice_label' => fn(IncomeCategory $cat) => sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'income-details',
                    'data-action' => 'change->income-details#change',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    Income::STATUS_DRAFT => Income::STATUS_DRAFT,
                    Income::STATUS_SIGNED => Income::STATUS_SIGNED,
                ],
                'required' => false,
                'disabled' => true,
                'data' => $income?->getStatus() ?? Income::STATUS_DRAFT,
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('campaign', EntityType::class, [
                'label' => 'Mission',
                'class' => Campaign::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => '// MISSION',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $asset?->getCampaign(),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'campaign',
                    'data-action' => 'change->campaign-asset#onCampaignChange',
                ],
            ])
            ->add('asset', EntityType::class, [
                'label' => 'Asset // Name', // Label style from Mortgage
                'placeholder' => '// ASSET',
                'class' => Asset::class,
                'mapped' => false, // Income è collegato a FinancialAccount, non direttamente all'Asset nella proprietà dell'entità (solitamente)
                'data' => $asset,
                'required' => false,
                'choice_label' => fn(Asset $asset) =>
                sprintf(
                    '%s - %s [CODE: %s]',
                    ucfirst($asset->getCategory()),
                    $asset->getName(),
                    substr($asset->getFinancialAccount()?->getCode() ?? 'N/A', 0, 8)
                ),
                'choice_attr' => function (Asset $a): array {
                    // Se l'account non è legato a un asset, non ha campagna (es. conto personale generico),
                    // quindi lo lasciamo visibile o lo gestiamo come "senza campagna".
                    // Per ora assumiamo logica simile a Mortgage: mostriamo se match campagna.
                    $campaignId = $a->getCampaign()?->getId();

                    $start = $a->getCampaign()?->getStartingYear();
                    $financialAccount = $a->getFinancialAccount();

                    return [
                        'data-start-year' => $start ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                        'data-financial-account-id' => $financialAccount ? (string) $financialAccount->getId() : '',
                    ];
                },
                'query_builder' => function (AssetRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    // Logica di filtro simile a Mortgage se necessario, oppure ampia
                    $qb->andWhere('s.campaign IS NOT NULL');
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'asset', // Questo è il target per il cambio Campagna
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->dayYearLimits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange',
                ],
            ])
            // CONTO DI ACCREDITO (Chi riceve / Dove vanno i soldi)
            ->add('financialAccount', EntityType::class, [
                'class' => FinancialAccount::class,
                'placeholder' => '// CREDIT ACCOUNT', // Placeholder coerente
                'label' => 'Link Existing Ledger (Credit)', // Dove entrano i soldi
                'required' => false,
                'choice_label' => fn(FinancialAccount $fa) => $fa->getDisplayName(),
                // Logica per mostrare se l'account appartiene all'asset selezionato è spesso fatta via query_builder o attributi dati se JS lo filtra.
                // Mortgage usa filtro JS (data-financial-account-id su opzione Asset solitamente?) O filtro standard.
                // Qui ci atteniamo al pattern:
                'query_builder' => function (FinancialAccountRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('fa')
                        ->leftJoin('fa.asset', 'a')
                        ->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('fa.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'help' => 'Account that receives the payment. Select existing OR create new below.',
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    // Nota: Manteniamo potenziali target per sicurezza
                ],
            ])
            ->add('bank', EntityType::class, [
                'class' => Company::class,
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'label' => 'New Ledger // Company',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// COMPANY',
                'query_builder' => function (EntityRepository $cr) use ($user) {
                    return $cr->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('c.name', 'ASC');
                },
                'help' => 'To create a NEW Credit Account for the Asset, select a company here OR enter custom name below.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'New Ledger // Custom Company Name',
                'required' => false,
                'mapped' => false,
                'help' => 'Enter a custom company name for the new Credit Account.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Spinward Marches Trading Post'],
            ])
            // PAGANTE (Chi paga / Fonte dei fondi)
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'label' => 'Payer (Company / Source)',
                'placeholder' => '// PAYER (COMPANY)',
                'required' => false,
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'help' => 'Who is paying this amount? (Commercial Client or Entity)',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('payerCompanyRole', EntityType::class, [
                'class' => \App\Entity\CompanyRole::class,
                'mapped' => false,
                'required' => false,
                'label' => 'Role (if new)',
                'placeholder' => '// ROLE',
                'choice_label' => 'shortDescription',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cr')->orderBy('cr.code', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('patronAlias', TextType::class, [
                'required' => false,
                'label' => 'Payer // Alias (if no Company)',
                'help' => 'Enter a name if the payer is not a registered Company (e.g. Private Individual).',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. "Colonial Governor"'],
            ])
            ->add('localLaw', EntityType::class, [
                'class' => LocalLaw::class,
                'placeholder' => '// LOCAL LAW',
                'required' => true,
                'choice_label' => function (LocalLaw $l): string {
                    $label = $l->getShortDescription() ?: $l->getDescription();
                    return sprintf('%s - %s', $l->getCode(), $label);
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Income $income */
            $income = $event->getData();
            $form = $event->getForm();



            /** @var ImperialDate|null $signing */
            $signing = $form->get('signingDate')->getData();
            if ($signing instanceof ImperialDate) {
                $income->setSigningDay($signing->getDay());
                $income->setSigningYear($signing->getYear());
            }

            /** @var ImperialDate|null $payment */
            $payment = $form->get('paymentDate')->getData();
            if ($payment instanceof ImperialDate) {
                $income->setPaymentDay($payment->getDay());
                $income->setPaymentYear($payment->getYear());
            }

            /** @var ImperialDate|null $expiration */
            $expiration = $form->get('expirationDate')->getData();
            if ($expiration instanceof ImperialDate) {
                $income->setExpirationDay($expiration->getDay());
                $income->setExpirationYear($expiration->getYear());
            }

            /** @var ImperialDate|null $cancel */
            $cancel = $form->get('cancelDate')->getData();
            if ($cancel instanceof ImperialDate) {
                $income->setCancelDay($cancel->getDay());
                $income->setCancelYear($cancel->getYear());
            }

            if ($income->isCancelled()) {
                $income->setStatus(Income::STATUS_CANCELLED);
            } elseif ($signing instanceof ImperialDate && $signing->getDay() !== null && $signing->getYear() !== null) {
                $income->setStatus(Income::STATUS_SIGNED);
            } else {
                $income->setStatus(Income::STATUS_DRAFT);
            }
        });

        $builder->addEventSubscriber($this->incomeDetailsSubscriber);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var Income $income */
        $income = $event->getData();
        $form = $event->getForm();

        // 1. VALIDAZIONE CREDIT (Receiver / Dove vanno i soldi)
        $financialAccount = $form->get('financialAccount')->getData();
        $bank = $form->get('bank')->getData();
        $bankName = $form->get('bankName')->getData();
        $asset = $form->get('asset')->getData();

        $hasAccount = $financialAccount !== null;
        $hasNewLedger = !empty($bank) || !empty($bankName);

        // Recupero automatico per campi disabilitati via JS:
        // Se JS disabilita l'input perché l'Asset ha un conto, questo non viene inviato.
        // Recuperiamo il conto dell'Asset e lo impostiamo, garantendo che la validazione passi.
        if ($asset && !$hasNewLedger) {
            $assetAccount = $asset->getFinancialAccount();
            if ($assetAccount) {
                // Se non è stato selezionato un conto diverso, forziamo quello dell'asset desiderato
                if (!$financialAccount || $financialAccount !== $assetAccount) {
                    $financialAccount = $assetAccount;
                    $income->setFinancialAccount($financialAccount);
                    $hasAccount = true;
                }
            }
        }

        if ($hasAccount && $hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Protocol Conflict: Cannot link existing account AND create a new one simultaneously. Choose one.'));
        }

        if (!$hasAccount && !$hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Missing Target: Select an existing Credit Account OR create a new one (Institution/Name).'));
        }

        // 2. VALIDAZIONE DEBIT (Payer / Chi paga)
        $company = $form->get('company')->getData();
        $patronAlias = $form->get('patronAlias')->getData();
        $role = $form->get('payerCompanyRole')->getData();

        $hasCompany = $company !== null;
        $hasAlias = !empty($patronAlias);

        if ($hasCompany && $hasAlias) {
            $form->get('company')->addError(new FormError('Source Conflict: Cannot select a registered Company AND a new Alias. Clear one field.'));
        }

        if (!$hasCompany && !$hasAlias) {
            $form->get('company')->addError(new FormError('Missing Source: Select a Payer Company OR enter a new Alias.'));
        }

        // Strict Role Requirement for New Payers
        if ($hasAlias && !$role) {
            $form->get('payerCompanyRole')->addError(new FormError('Role Required: When creating a new Payer Company, you must specify its Role.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Income::class,
            'user' => null,
        ]);
    }
}
