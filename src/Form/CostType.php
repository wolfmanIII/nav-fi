<?php

namespace App\Form;

use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\Config\DayYearLimits;
use App\Form\CostDetailItemType;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use App\Entity\Campaign;
use App\Entity\FinancialAccount;
use App\Repository\FinancialAccountRepository;
use App\Service\TravellerMapDataService;

class CostType extends AbstractType
{
    public function __construct(
        private readonly DayYearLimits $limits,
        private readonly TravellerMapDataService $dataService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Cost $cost */
        $cost = $builder->getData();
        $asset = $cost?->getFinancialAccount()?->getAsset();
        $campaignStartYear = $asset?->getCampaign()?->getStartingYear();
        $paymentDate = new ImperialDate($cost?->getPaymentYear(), $cost?->getPaymentDay());
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();

        $builder
            ->add('asset', EntityType::class, [
                'label' => 'Asset // Name',
                'placeholder' => '// ASSET',
                'class' => Asset::class,
                'mapped' => false,
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
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'asset',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange change->financial-lock#check change->form-visibility#toggle',
                    'data-financial-lock-target' => 'asset',
                ],
            ])
            ->add('campaign', EntityType::class, [
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
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full', 'readonly' => true],
            ])
            ->add('detailItems', CollectionType::class, [
                'entry_type' => CostDetailItemType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Add at least one cost detail'),
                ],
                'error_bubbling' => false,
            ])
            ->add('paymentDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Payment date',
                'data' => $paymentDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('financialAccount', EntityType::class, [
                'class' => FinancialAccount::class,
                'placeholder' => '// DEBIT ACCOUNT',
                'label' => 'Source Account (Debit)',
                'choice_label' => fn(FinancialAccount $fa) => $fa->getDisplayName(),
                'query_builder' => function (FinancialAccountRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('fa')
                        ->leftJoin('fa.asset', 'a')
                        ->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('fa.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-financial-lock-target' => 'debitAccount',
                    'data-action' => 'change->financial-lock#onAccountChange',
                ],
                'help' => 'Account that pays this cost.',
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
                'help' => 'Select a company to create a NEW Debit Account for this Asset.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-financial-lock-target' => 'debitCreation',
                ],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'New Ledger // Custom Company Name',
                'required' => false,
                'mapped' => false,
                'help' => 'Enter a custom company name for the new Debit Account.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => [
                    'class' => 'input m-1 w-full',
                    'placeholder' => 'e.g. Starport Maintenance Fund',
                    'data-financial-lock-target' => 'debitCreation',
                ],
            ])
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'label' => 'Vendor (Company)',
                'placeholder' => '// VENDOR (COMPANY)',
                'required' => false, // Changed to false for XOR logic
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => ['class' => 'select m-1 w-full'],
                'help' => 'Who are we paying? (Vendor/Entity)',
            ])
            ->add('vendorRole', EntityType::class, [
                'class' => \App\Entity\CompanyRole::class, // Need to ensure use statement or fully qualified
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
            ->add('vendorName', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Vendor // New Company Name',
                'help' => 'Enter a name if the vendor is not in the list.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. "Joe\'s Repair Shop"'],
            ])
            ->add('costCategory', EntityType::class, [
                'class' => CostCategory::class,
                'placeholder' => '// CATEGORY',
                'choice_label' => fn(CostCategory $cat) =>
                sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cc')
                        ->orderBy('cc.code', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
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
            ->add('targetSector', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Target Sector',
                'placeholder' => '// SELECT SECTOR',
                'choices' => $this->dataService->getOtuSectors(),
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                ],
            ])
            ->add('targetWorld', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Target World',
                'placeholder' => '// SELECT WORLD',
                'choices' => [],
                'disabled' => true,
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                ],
            ])
            ->add('targetDestination', TextType::class, [
                'required' => false,
                'label' => 'Target Trade Destination',
                'attr' => [
                    'class' => 'input m-1 w-full',
                    'readonly' => true,
                    'placeholder' => 'Pick Sector and World above...'
                ],
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Cost $cost */
            $cost = $event->getData();
            $form = $event->getForm();

            $date = $form->get('paymentDate')->getData();
            if ($date instanceof \App\Model\ImperialDate) {
                $cost->setPaymentDay($date->getDay());
                $cost->setPaymentYear($date->getYear());
            }

            // Calcolo server-side dell'importo totale...
            $details = $cost->getDetailItems() ?? [];
            $validDetails = [];
            $total = 0.0;
            foreach ($details as $idx => $item) {
                $description = isset($item['description']) ? trim((string) $item['description']) : '';
                $qtyRaw = $item['quantity'] ?? null;
                $costRaw = $item['cost'] ?? null;
                $hasAny = $description !== '' || $qtyRaw !== null && $qtyRaw !== '' || $costRaw !== null && $costRaw !== '';
                if (!$hasAny) {
                    continue;
                }
                $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;
                $lineCost = is_numeric($costRaw) ? (float) $costRaw : null;
                if ($description === '' || $qty === null || $lineCost === null) {
                    $form->get('detailItems')->addError(new FormError('Each detail needs description, quantity and cost.'));
                    continue;
                }
                $validDetails[] = [
                    'description' => $description,
                    'quantity' => $qty,
                    'cost' => $lineCost,
                    'isSold' => $item['isSold'] ?? false,
                ];
                $total += $qty * $lineCost;
            }
            if (count($validDetails) < 1) {
                $form->get('detailItems')->addError(new FormError('Add at least one cost detail'));
            }
            $cost->setDetailItems($validDetails);
            $cost->setAmount(sprintf('%.2f', $total));

            // Map field reconstrucion
            $sector = $form->get('targetSector')->getData();
            $world = $form->get('targetWorld')->getData();
            if ($sector && $world) {
                $cost->setTargetDestination(sprintf('%s // %s', $sector, $world));
            }
        });

        // PRE_SET_DATA to initialize map fields
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Cost|null $cost */
            $cost = $event->getData();
            $form = $event->getForm();
            if (!$cost) return;

            $location = $cost->getTargetDestination();
            if ($location && str_contains($location, ' // ')) {
                [$sector, $world] = explode(' // ', $location, 2);
                $sector = trim($sector);
                $world = trim($world);

                // Ridefiniamo il settore con il dato pre-impostato
                $form->add('targetSector', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Target Sector',
                    'placeholder' => '// SELECT SECTOR',
                    'choices' => $this->dataService->getOtuSectors(),
                    'data' => $sector, // Forza il valore selezionato
                    'attr' => [
                        'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    ],
                ]);

                $form->add('targetWorld', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Target World',
                    'choices' => [$world => $world],
                    'data' => $world,
                    'attr' => [
                        'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    ],
                ]);
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var Cost $cost */
        $cost = $event->getData();
        $form = $event->getForm();

        // 1. VALIDAZIONE DEBIT (Source / FinancialAccount)
        $financialAccount = $form->get('financialAccount')->getData();
        $bank = $form->get('bank')->getData();
        $bankName = $form->get('bankName')->getData();
        $asset = $form->get('asset')->getData();

        $hasAccount = $financialAccount !== null;
        $hasNewLedger = !empty($bank) || !empty($bankName);

        // Auto-Recovery for JS-Disabled fields
        if ($asset && !$hasNewLedger) {
            $assetAccount = $asset->getFinancialAccount();
            if ($assetAccount) {
                // If not manually selected or different from current asset's account, force it
                if (!$financialAccount || $financialAccount !== $assetAccount) {
                    $financialAccount = $assetAccount;
                    $cost->setFinancialAccount($financialAccount);
                    $hasAccount = true;
                }
            }
        }

        if ($hasAccount && $hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Protocol Conflict: Cannot link existing account AND create a new one simultaneously. Choose one.'));
        }

        if (!$hasAccount && !$hasNewLedger) {
            $form->get('financialAccount')->addError(new FormError('Missing Source: Select an existing Debit Account OR create a new one.'));
        }

        // 2. VALIDAZIONE CREDIT (Vendor / Company)
        $company = $form->get('company')->getData();
        $vendorName = $form->get('vendorName')->getData();
        $vendorRole = $form->get('vendorRole')->getData();

        $hasCompany = $company !== null;
        $hasNewVendor = !empty($vendorName);

        if ($hasCompany && $hasNewVendor) {
            $form->get('company')->addError(new FormError('Destination Conflict: Cannot select a registered Vendor AND a new Name. Clear one field.'));
        }

        if (!$hasCompany && !$hasNewVendor) {
            $form->get('company')->addError(new FormError('Missing Vendor: Select a Vendor Company OR enter a new Name.'));
        }

        if ($hasNewVendor && !$vendorRole) {
            $form->get('vendorRole')->addError(new FormError('Role Required: When creating a new Vendor, you must specify its Role.'));
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cost::class,
            'user' => null,
        ]);
    }
}
