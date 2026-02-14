<?php

namespace App\Form;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Entity\FinancialAccount;
use App\Entity\LocalLaw;
use App\Repository\CompanyRepository;
use App\Repository\FinancialAccountRepository;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MortgageType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Mortgage $mortgage */
        $mortgage = $options['data'];
        $user = $options['user'];
        $currentAssetId = $mortgage->getAsset()?->getId();
        $builder
            //->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full'],])

            ->add('assetShares', IntegerType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('advancePayment', TravellerMoneyType::class, [
                'label' => 'Deposit(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('discount', IntegerType::class, [
                'label' => 'Discount(%)',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'label' => 'Mission',
                'mapped' => false,
                'required' => true,
                'placeholder' => '// MISSION',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $mortgage->getAsset()?->getCampaign(),
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
                'label' => 'Asset // Name // Price',
                'placeholder' => '// ASSET',
                'class' => Asset::class,
                'choice_label' => fn(Asset $asset) =>
                sprintf(
                    '%s - %s - %s - [CODE: %s]',
                    ucfirst($asset->getCategory()),
                    $asset->getName(),
                    number_format((float) $asset->getPrice(), 2, ',', '.') . " Cr",
                    substr($asset->getFinancialAccount()?->getCode() ?? 'N/A', 0, 8)
                ),
                'choice_attr' => function (Asset $a): array {
                    $start = $a->getCampaign()?->getStartingYear();
                    $campaignId = $a->getCampaign()?->getId();
                    $financialAccount = $a->getFinancialAccount();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                        'data-financial-account-id' => $financialAccount ? (string) $financialAccount->getId() : '',
                    ];
                },
                'query_builder' => function (AssetRepository $repo) use ($user, $currentAssetId) {
                    $qb = $repo->createQueryBuilder('s')
                        ->leftJoin('s.mortgage', 'm')
                        ->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    $qb->andWhere('s.category IN (:mortgageable)')
                        ->setParameter('mortgageable', [Asset::CATEGORY_SHIP, Asset::CATEGORY_BASE]);
                    if ($currentAssetId) {
                        $qb->andWhere('(m.id IS NULL OR s.id = :currentAsset)')
                            ->setParameter('currentAsset', $currentAssetId);
                    } else {
                        $qb->andWhere('m.id IS NULL');
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'asset',
                    'data-financial-lock-target' => 'asset',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange change->financial-lock#check',
                ],
            ])
            ->add('interestRate', EntityType::class, [
                'class' => InterestRate::class,
                'choice_label' => fn(InterestRate $rate) =>
                sprintf(
                    '%d years – x%s / %s – %s%%',
                    $rate->getDuration(),
                    $rate->getPriceMultiplier(),
                    $rate->getPriceDivider(),
                    $rate->getAnnualInterestRate()
                ),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('insurance', EntityType::class, [
                'class' => Insurance::class,
                'choice_label' => fn(Insurance $insurance) =>
                sprintf(
                    '%s - %d%% Asset Price',
                    $insurance->getName(),
                    $insurance->getAnnualCost(),
                ),
                'multiple' => false,
                'expanded' => false,
            ])
            // DEBIT ACCOUNT (Chi paga)
            ->add('financialAccount', EntityType::class, [
                'class' => FinancialAccount::class,
                'choice_label' => fn(FinancialAccount $fa) => $fa->getDisplayName(),
                'label' => 'Link Existing Ledger (Debit)',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// DEBIT ACCOUNT',
                'data' => $mortgage->getFinancialAccount(),
                'query_builder' => function (FinancialAccountRepository $repo) use ($user, $mortgage) {
                    $qb = $repo->createQueryBuilder('fa')
                        ->where('fa.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('fa.id', 'DESC');
                    if ($mortgage->getFinancialAccount()) {
                        $qb->andWhere('fa.id != :current OR fa.id = :current')
                            ->setParameter('current', $mortgage->getFinancialAccount()->getId());
                    }
                    return $qb;
                },
                'help' => 'Account that pays the mortgage. Select an existing one OR create new below.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full', 'data-financial-lock-target' => 'debitAccount', 'data-action' => 'change->financial-lock#onAccountChange'],
            ])
            ->add('bank', EntityType::class, [
                'class' => Company::class,
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'label' => 'New Ledger // Banking Institution',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// BANK',
                'query_builder' => function (CompanyRepository $cr) use ($user) {
                    return $cr->createQueryBuilder('c')
                        ->innerJoin('c.companyRole', 'r')
                        ->where('c.user = :user')
                        ->andWhere('r.code = :role')
                        ->setParameter('user', $user)
                        ->setParameter('role', CompanyRole::ROLE_BANK)
                        ->orderBy('c.name', 'ASC');
                },
                'help' => 'To create a NEW Debit Account, select a bank here OR enter custom name below.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full', 'data-financial-lock-target' => 'debitCreation'],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'New Ledger // Custom Institution Name',
                'required' => false,
                'mapped' => false,
                'help' => 'Enter a custom bank name for the new Debit Account.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Spinward Marches Credit Union', 'data-financial-lock-target' => 'debitCreation'],
            ])

            // CREDIT INSTITUTION (Chi riceve)
            ->add('company', EntityType::class, [
                'label' => 'Lender (Credit Institution)',
                'class' => Company::class,
                'placeholder' => '// LENDER',
                'required' => false, // Gestito manualmente se newLenderName è presente
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('c')
                        ->innerJoin('c.companyRole', 'r')
                        ->where('c.user = :user')
                        ->andWhere('r.code = :role')
                        ->setParameter('user', $user)
                        ->setParameter('role', CompanyRole::ROLE_BANK)
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('creditInstitutionName', TextType::class, [
                'label' => 'New Lender // Custom Name',
                'required' => false,
                'mapped' => false,
                'help' => 'If Lender not listed, enter name to create new Banking Entity.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Imperial Naval Bank'],
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
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Mortgage $mortgage */
        $mortgage = $event->getData();

        // 1. DEBIT PROTOCOL (Account that Pays)
        // Logic: specific Financial Account XOR (New Account via Bank/Name)
        $financialAccount = $form->get('financialAccount')->getData();
        $bank = $form->get('bank')->getData();
        $bankName = $form->get('bankName')->getData();
        $asset = $form->get('asset')->getData();

        $hasAccount = $financialAccount !== null;
        $hasNewLedger = !empty($bank) || !empty($bankName);

        // Recupero automatico per campi disabilitati via JS:
        // Se JS disabilita l'input perché l'Asset ha un conto, questo non viene inviato.
        // Recuperiamo il conto dell'Asset e lo impostiamo, garantendo che la validazione passi.
        if (!$hasAccount && !$hasNewLedger && $asset && $asset->getFinancialAccount()) {
            $financialAccount = $asset->getFinancialAccount();
            $mortgage->setFinancialAccount($financialAccount);
            $hasAccount = true;
        }

        if ($hasAccount && $hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Protocol Conflict: Cannot link existing account AND open a new one. Choose one method.'));
        }
        if (!$hasAccount && !$hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Missing Debit Source: Select an existing Account OR open a new one.'));
        }

        // 2. CREDIT PROTOCOL (Lender / Institution)
        // Logic: specific Company (Lender) XOR New Custom Name
        $company = $form->get('company')->getData();
        $newLenderName = $form->get('creditInstitutionName')->getData();

        $hasCompany = $company !== null;
        $hasNewLender = !empty($newLenderName);

        if ($hasCompany && $hasNewLender) {
            $form->get('company')->addError(new FormError('Lender Conflict: Cannot select registered Lender AND enter a new Name. Clear one.'));
        }
        if (!$hasCompany && !$hasNewLender) {
            $form->get('company')->addError(new FormError('Missing Lender: Select a Registered Institution OR enter a new Name.'));
        }

        // 3. VALIDAZIONE ESSENZIALI (Interessi & Assicurazione)
        // L'utente ha richiesto validazione esplicita. Usiamo FormError invece di Flash per evidenziare il campo.
        $interestRate = $form->get('interestRate')->getData();
        if (!$interestRate) {
            $form->get('interestRate')->addError(new FormError('Interest Rate Required: Please select a valid mortgage plan.'));
        }

        $insurance = $form->get('insurance')->getData();
        if (!$insurance) {
            $form->get('insurance')->addError(new FormError('Insurance Required: Asset must be insured for mortgage approval.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mortgage::class,
            'user' => null,
        ]);
    }
}
