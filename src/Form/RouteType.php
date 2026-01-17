<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\Asset;
use App\Form\Config\DayYearLimits;
use App\Form\RouteWaypointType;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Service\ImperialDateHelper;
use App\Service\RouteMathHelper;
use App\Service\TravellerMapSectorLookup;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class RouteType extends AbstractType
{
    public function __construct(
        private readonly RouteMathHelper $routeMathHelper,
        private readonly DayYearLimits $limits,
        private readonly ImperialDateHelper $imperialDateHelper,
        private readonly TravellerMapSectorLookup $sectorLookup
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Route $route */
        $route = $builder->getData();

        $campaignStartYear = $route->getCampaign()?->getStartingYear()
            ?? $route->getAsset()?->getCampaign()?->getStartingYear();
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        $startDate = new ImperialDate($route->getStartYear(), $route->getStartDay());
        $destDate = new ImperialDate($route->getDestYear(), $route->getDestDay());

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'required' => true,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => $campaign->getTitle(),
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
                'class' => Asset::class,
                'placeholder' => '-- Select an Asset --',
                'choice_label' => fn(Asset $asset) => sprintf('%s - %s(%s)', $asset->getName(), $asset->getType(), $asset->getClass()),
                'choice_attr' => function (Asset $asset): array {
                    $start = $asset->getCampaign()?->getStartingYear();
                    $campaignId = $asset->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
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
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange',
                ],
            ])
            ->add('startHex', HiddenType::class, [
                'required' => false,
            ])
            ->add('destHex', HiddenType::class, [
                'required' => false,
            ])
            ->add('startDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Start date',
                'data' => $startDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('destDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Destination date',
                'data' => $destDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('jumpRating', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('fuelEstimate', TextType::class, [
                'label' => 'Fuel Estimate (per jump)',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('waypoints', CollectionType::class, [
                'entry_type' => RouteWaypointType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
                'constraints' => [
                    new Count(
                        min: 1,
                        minMessage: 'Add at least one waypoint to define a route.',
                    ),
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Route $route */
            $route = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $start */
            $start = $form->get('startDate')->getData();
            /** @var ImperialDate|null $dest */
            $dest = $form->get('destDate')->getData();
            if ($start instanceof ImperialDate) {
                $route->setStartDay($start->getDay());
                $route->setStartYear($start->getYear());
            } else {
                $route->setStartDay(null);
                $route->setStartYear(null);
            }
            if ($dest instanceof ImperialDate) {
                $route->setDestDay($dest->getDay());
                $route->setDestYear($dest->getYear());
            } else {
                $route->setDestDay(null);
                $route->setDestYear(null);
            }

            $startKey = $this->imperialDateHelper->toKey($route->getStartDay(), $route->getStartYear());
            $destKey = $this->imperialDateHelper->toKey($route->getDestDay(), $route->getDestYear());
            if ($startKey !== null && $destKey !== null && $startKey > $destKey) {
                $form->get('startDate')->addError(new FormError('Start date must be before destination date.'));
            }

            $waypoints = $route->getWaypoints();
            $firstWaypoint = $waypoints->first() ?: null;
            $lastWaypoint = $waypoints->last() ?: null;
            $route->setStartHex($firstWaypoint?->getHex() ?: null);
            $route->setDestHex($lastWaypoint?->getHex() ?: null);

            $hexes = [];
            $index = 0;
            foreach ($waypoints as $waypoint) {
                $waypoint->setPosition(++$index);
                $hexes[] = (string) $waypoint->getHex();
            }

            $distances = $this->routeMathHelper->segmentDistances($hexes);

            // Auto-fill jump rating from asset if not specified
            if ($route->getJumpRating() === null) {
                $assetRating = $this->routeMathHelper->getAssetJumpRating($route->getAsset());
                if ($assetRating !== null) {
                    $route->setJumpRating($assetRating);
                }
            }

            $jumpRating = $route->getJumpRating(); // Now strictly use what's on the route (or what we just filled)

            $hullTons = $this->routeMathHelper->getAssetHullTonnage($route->getAsset());
            $fuelCapacity = $this->routeMathHelper->getAssetFuelCapacity($route->getAsset());

            foreach ($waypoints as $idx => $waypoint) {
                $distance = $distances[$idx] ?? null;
                $waypoint->setJumpDistance($distance);

                if ($distance === null && $idx > 0) {
                    $form->get('waypoints')->addError(new FormError('All waypoint hexes must be in 4-digit format.'));
                    continue;
                }

                // 1. Jump Rating check
                if ($jumpRating !== null && $distance !== null && $distance > $jumpRating) {
                    $form->get('waypoints')->addError(new FormError(
                        sprintf('Jump %d exceeds rating %d on segment #%d.', $distance, $jumpRating, $idx + 1)
                    ));
                }

                // 2. Fuel Capacity check (Any single jump must fit in tanks)
                if ($hullTons !== null && $fuelCapacity !== null && $distance !== null) {
                    $requiredForJump = 0.1 * $hullTons * $distance;
                    if ($requiredForJump > $fuelCapacity) {
                        $form->get('waypoints')->addError(new FormError(
                            sprintf('Segment #%d requires %.2f tons of fuel, exceeding ship tank capacity (%.2f tons).', $idx + 1, $requiredForJump, $fuelCapacity)
                        ));
                    }
                }

                $sector = trim((string) $waypoint->getSector());
                $hex = trim((string) $waypoint->getHex());
                if ($sector !== '' && $hex !== '') {
                    $lookup = $this->sectorLookup->lookupWorld($sector, $hex);
                    if ($lookup) {
                        $waypoint->setWorld($lookup['world'] ?? $waypoint->getWorld());
                        $waypoint->setUwp($lookup['uwp'] ?? $waypoint->getUwp());
                    }
                }
            }

            // Always calculate the required fuel based on current waypoints
            $calculatedRequiredFuel = $this->routeMathHelper->estimateJumpFuel($route, $distances);

            if ($route->getFuelEstimate() !== null && $calculatedRequiredFuel !== null) {
                // Check if the estimated fuel is sufficient for the calculated requirement
                if ((float) $route->getFuelEstimate() < (float) $calculatedRequiredFuel) {
                    $form->get('fuelEstimate')->addError(new FormError(
                        sprintf(
                            'Fuel estimate (%s tons) is insufficient. Minimum required: %s tons.',
                            $route->getFuelEstimate(),
                            $calculatedRequiredFuel
                        )
                    ));
                }
            } elseif ($route->getFuelEstimate() === null && $calculatedRequiredFuel !== null) {
                // Auto-fill if empty
                $route->setFuelEstimate($calculatedRequiredFuel);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Route::class,
            'user' => null,
        ]);
    }
}
