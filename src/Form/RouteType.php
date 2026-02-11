<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\Asset;
use App\Form\Config\DayYearLimits;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use App\Service\TravellerMapDataService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteType extends AbstractType
{
    public function __construct(
        private readonly DayYearLimits $limits,
        private readonly AssetRepository $assetRepository,
        private readonly TravellerMapDataService $dataService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Route $route */
        $route = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('campaign', EntityType::class, [
                // ... (lines 47-65)
                'label' => 'Mission',
                'class' => Campaign::class,
                'required' => true,
                'placeholder' => '// MISSION',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $route->getCampaign() ?? $route->getAsset()?->getCampaign(),
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
                // ... (lines 67-95)
                'class' => Asset::class,
                'placeholder' => '// ASSET',
                'choice_label' => fn(Asset $asset) => sprintf('%s - %s(%s) [CODE: %s]', $asset->getName(), $asset->getType(), $asset->getClass(), substr($asset->getFinancialAccount()?->getCode() ?? 'N/A', 0, 8)),
                'choice_attr' => function (Asset $asset): array {
                    $start = $asset->getCampaign()?->getStartingYear();
                    $session = $asset->getCampaign()?->getSessionYear();
                    $campaignId = $asset->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-session-year' => $session ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (AssetRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('a')->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('a.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'asset',
                    'data-controller' => 'year-limit route-ship-data',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-form-visibility-target' => 'trigger',
                    'data-action' => 'change->year-limit#onAssetChange change->form-visibility#toggle change->route-ship-data#change',
                ],
            ])
            ->add('startSector', ChoiceType::class, [
                'required' => false,
                'label' => 'Start Sector (OTU)',
                'placeholder' => '// SELECT SECTOR',
                'choices' => $this->dataService->getOtuSectors(),
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'searchable-select',
                    'data-searchable-select-placeholder-value' => 'Search Sector...',
                    'data-action' => 'change->dependent-select#change',
                    'data-dependent-select-target' => 'source',
                ],
            ])
            ->add('startWorld', ChoiceType::class, [
                'required' => false,
                'label' => 'Start World',
                'placeholder' => '// SELECT WORLD',
                'property_path' => 'startHex',
                'choices' => $route->getStartSector() ? $this->dataService->getWorldsForSector($route->getStartSector()) : [],
                'choice_label' => fn($choice, $key, $value) => $key,
                'choice_value' => fn($choice) => $choice,
                'disabled' => $route->getStartSector() === null,
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'searchable-select',
                    'data-dependent-select-target' => 'destination',
                    'data-searchable-select-placeholder-value' => 'Search World...',
                ],
            ])
            ->add('startDate', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Start Date',
                'data' => $route->getStartDateImperial(),
                'disabled' => true,
                'attr' => [
                    'class' => 'input input-bordered w-full bg-slate-950/50 border-slate-700 font-mono text-cyan-400 cursor-not-allowed',
                ],
            ])
            ->add('destDate', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Destination Date',
                'data' => $route->getDestDateImperial(),
                'disabled' => true,
                'attr' => [
                    'class' => 'input input-bordered w-full bg-slate-950/50 border-slate-700 font-mono text-cyan-400 cursor-not-allowed',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea textarea-bordered w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 font-rajdhani', 'rows' => 3],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Route $route */
            $route = $event->getData();
            $form = $event->getForm();
            $asset = $route?->getAsset();

            $this->modifyForm($form, $asset);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!empty($data['startSector'])) {
                $form->add('startWorld', ChoiceType::class, [
                    'required' => false,
                    'label' => 'Start World',
                    'placeholder' => '// SELECT WORLD',
                    'property_path' => 'startHex',
                    'choices' => $this->dataService->getWorldsForSector($data['startSector']),
                    'choice_label' => fn($choice, $key, $value) => $key,
                    'choice_value' => fn($choice) => $choice,
                    'attr' => [
                        'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                        'data-controller' => 'searchable-select',
                        'data-dependent-select-target' => 'destination',
                        'data-searchable-select-placeholder-value' => 'Search World...',
                    ],
                ]);
            }

            // If asset is not selected in the form data, we can't do anything
            if (empty($data['asset'])) {
                return;
            }

            $assetId = $data['asset'];
            // If asset is same as initial data, modifyForm might have already run in PRE_SET_DATA?
            // But PRE_SUBMIT runs on submit. PRE_SET_DATA ran on view creation.
            // If user changes asset, we need to re-run specific logic?
            // modifyForm validates missing fields.

            $asset = $this->assetRepository->find($assetId);
            $this->modifyForm($form, $asset);
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Route $route */
            $route = $event->getData();
            $form = $event->getForm();
            $asset = $route->getAsset();

            if (!$asset) {
                return;
            }

            $details = $asset->getAssetDetails() ?? [];
            $updated = false;

            if ($form->has('shipHull') && $form->get('shipHull')->getData()) {
                $details['hull']['tons'] = $form->get('shipHull')->getData();
                $updated = true;
            }

            if ($form->has('shipJump') && $form->get('shipJump')->getData()) {
                $details['jDrive']['jump'] = $form->get('shipJump')->getData();
                $updated = true;
            }

            if ($form->has('shipFuel') && $form->get('shipFuel')->getData()) {
                $details['fuel']['tons'] = $form->get('shipFuel')->getData();
                $updated = true;
            }

            if ($updated) {
                $asset->setAssetDetails($details);
            }
        });
    }

    private function modifyForm(FormInterface $form, ?Asset $asset): void
    {
        if (!$asset) {
            return;
        }

        $spec = $asset->getSpec();

        // Check Hull
        if (!$spec->getHull()->getTons()) {
            if (!$form->has('shipHull')) {
                $form->add('shipHull', NumberType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Ship Hull Tonnage (Required)',
                    'help' => 'Required for fuel calculations. Will update ship data.',
                    'attr' => ['class' => 'input input-bordered input-warning w-full'],
                ]);
            }
        }

        // Check Jump Rating
        if (!$spec->getJDrive()->getRating()) {
            if (!$form->has('shipJump')) {
                $form->add('shipJump', IntegerType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Ship Jump Rating (Required)',
                    'help' => 'Required for route validation. Will update ship data.',
                    'attr' => ['class' => 'input input-bordered input-warning w-full'],
                ]);
            }
        }

        // Check Fuel Capacity
        if (!$spec->getFuel()->getCapacity()) {
            if (!$form->has('shipFuel')) {
                $form->add('shipFuel', NumberType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Ship Fuel Tank (Required)',
                    'help' => 'Required for fuel calc. Will update ship data.',
                    'attr' => ['class' => 'input input-bordered input-warning w-full'],
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Route::class,
            'user' => null,
        ]);
    }
}
