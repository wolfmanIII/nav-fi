<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\Ship;
use App\Form\Config\DayYearLimits;
use App\Form\RouteWaypointType;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Service\ImperialDateHelper;
use App\Service\RouteMathHelper;
use App\Repository\ShipRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteType extends AbstractType
{
    public function __construct(
        private readonly RouteMathHelper $routeMathHelper,
        private readonly DayYearLimits $limits,
        private readonly ImperialDateHelper $imperialDateHelper
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Route $route */
        $route = $builder->getData();

        $campaignStartYear = $route->getCampaign()?->getStartingYear()
            ?? $route->getShip()?->getCampaign()?->getStartingYear();
        $minYear = max($this->limits->getYearMin(), $campaignStartYear ?? $this->limits->getYearMin());
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
                'choice_label' => fn (Campaign $campaign) => $campaign->getTitle(),
                'data' => $route->getCampaign() ?? $route->getShip()?->getCampaign(),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-campaign-ship-target' => 'campaign',
                    'data-action' => 'change->campaign-ship#onCampaignChange',
                ],
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'placeholder' => '-- Select a Ship --',
                'choice_label' => fn (Ship $ship) => sprintf('%s - %s(%s)', $ship->getName(), $ship->getType(), $ship->getClass()),
                'choice_attr' => function (Ship $ship): array {
                    $campaignId = $ship->getCampaign()?->getId();
                    return [
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (ShipRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-campaign-ship-target' => 'ship',
                ],
            ])
            ->add('startHex', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full uppercase', 'maxlength' => 4],
            ])
            ->add('destHex', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full uppercase', 'maxlength' => 4],
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
            $hexes = [];
            $index = 0;
            foreach ($waypoints as $waypoint) {
                $waypoint->setPosition(++$index);
                $hexes[] = (string) $waypoint->getHex();
            }

            $distances = $this->routeMathHelper->segmentDistances($hexes);
            $jumpRating = $this->routeMathHelper->resolveJumpRating($route);

            foreach ($waypoints as $idx => $waypoint) {
                $distance = $distances[$idx] ?? null;
                $waypoint->setJumpDistance($distance);

                if ($distance === null && $idx > 0) {
                    $form->get('waypoints')->addError(new FormError('All waypoint hexes must be in 4-digit format.'));
                    continue;
                }

                if ($jumpRating !== null && $distance !== null && $distance > $jumpRating) {
                    $form->get('waypoints')->addError(new FormError(
                        sprintf('Jump %d exceeds rating %d on segment #%d.', $distance, $jumpRating, $idx + 1)
                    ));
                }
            }

            if ($route->getFuelEstimate() === null) {
                $estimate = $this->routeMathHelper->estimateJumpFuel($route, $distances);
                if ($estimate !== null) {
                    $route->setFuelEstimate($estimate);
                }
            }

            $capacity = $this->routeMathHelper->getShipFuelCapacity($route->getShip());
            $estimateValue = $route->getFuelEstimate();
            if ($capacity !== null && $estimateValue !== null && is_numeric($estimateValue)) {
                if ((float) $estimateValue > $capacity) {
                    $form->get('fuelEstimate')->addError(new FormError(
                        'Estimated jump fuel exceeds ship fuel tankage.'
                    ));
                }
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
