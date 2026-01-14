<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\ShipRole;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Ship;
use App\Entity\Campaign;
use Doctrine\ORM\EntityRepository;
use App\Repository\ShipRepository;

class CrewType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Crew $crew */
        $crew = $options['data'];
        $user = $options['user'];
        $campaignStartYear = $crew?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = 0;
        $eventMinYear = $campaignStartYear ?? $this->limits->getYearMin();
        $birthDate = new ImperialDate($crew?->getBirthYear(), $crew?->getBirthDay());
        $activeDate = new ImperialDate($crew?->getActiveYear(), $crew?->getActiveDay());
        $onLeaveDate = new ImperialDate($crew?->getOnLeaveYear(), $crew?->getOnLeaveDay());
        $retiredDate = new ImperialDate($crew?->getRetiredYear(), $crew?->getRetiredDay());
        $miaDate = new ImperialDate($crew?->getMiaYear(), $crew?->getMiaDay());
        $deceasedDate = new ImperialDate($crew?->getDeceasedYear(), $crew?->getDeceasedDay());
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('surname', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('nickname', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'label' => 'Callsign',
            ])
            ->add('birthDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Birth date',
                'data' => $birthDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('birthWorld', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'placeholder' => '-- Select a Status --',
                'choices' => [
                    'Active' => 'Active',
                    'On Leave' => 'On Leave',
                    'Retired' => 'Retired',
                    'Missing (MIA)' => 'Missing (MIA)',
                    'Deceased' => 'Deceased',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('activeDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Active date',
                'data' => $activeDate,
                'min_year' => $eventMinYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('onLeaveDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'On leave date',
                'data' => $onLeaveDate,
                'min_year' => $eventMinYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('retiredDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Retired date',
                'data' => $retiredDate,
                'min_year' => $eventMinYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('miaDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'MIA date',
                'data' => $miaDate,
                'min_year' => $eventMinYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('deceasedDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Deceased date',
                'data' => $deceasedDate,
                'min_year' => $eventMinYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => $campaign->getTitle(),
                'data' => $crew->getShip()?->getCampaign(),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-ship-target' => 'campaign',
                    'data-action' => 'change->campaign-ship#onCampaignChange',
                ],
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'required' => false,
                'placeholder' => '-- Select a Ship --',
                'choice_label' => fn(Ship $ship) => sprintf('%s - %s(%s)', $ship->getName(), $ship->getType(), $ship->getClass()),
                'choice_attr' => function (Ship $ship): array {
                    $start = $ship->getCampaign()?->getStartingYear();
                    $campaignId = $ship->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (ShipRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                    'data-campaign-ship-target' => 'ship',
                ],
            ])
            ->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'label' => 'Roles',
                'choice_label' => fn(ShipRole $role) => sprintf('%s – %s', $role->getCode(), $role->getName()),
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')->orderBy('r.code', 'ASC');
                },
                'attr' => [
                    'class' => 'select m-1 w-full h-48',
                ],
            ])
            ->add('background', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 13],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Crew $crew */
            $crew = $event->getData();
            $form = $event->getForm();

            $ship = $form->get('ship')->getData();

            /** @var ImperialDate|null $birth */
            $birth = $form->get('birthDate')->getData();
            if ($birth instanceof ImperialDate) {
                $crew->setBirthDay($birth->getDay());
                $crew->setBirthYear($birth->getYear());
            }

            /** @var ImperialDate|null $active */
            $active = $form->get('activeDate')->getData();
            if ($active instanceof ImperialDate) {
                $crew->setActiveDay($active->getDay());
                $crew->setActiveYear($active->getYear());
            }

            /** @var ImperialDate|null $onLeave */
            $onLeave = $form->get('onLeaveDate')->getData();
            if ($onLeave instanceof ImperialDate) {
                $crew->setOnLeaveDay($onLeave->getDay());
                $crew->setOnLeaveYear($onLeave->getYear());
            }

            /** @var ImperialDate|null $retired */
            $retired = $form->get('retiredDate')->getData();
            if ($retired instanceof ImperialDate) {
                $crew->setRetiredDay($retired->getDay());
                $crew->setRetiredYear($retired->getYear());
            }

            /** @var ImperialDate|null $mia */
            $mia = $form->get('miaDate')->getData();
            if ($mia instanceof ImperialDate) {
                $crew->setMiaDay($mia->getDay());
                $crew->setMiaYear($mia->getYear());
            }

            /** @var ImperialDate|null $deceased */
            $deceased = $form->get('deceasedDate')->getData();
            if ($deceased instanceof ImperialDate) {
                $crew->setDeceasedDay($deceased->getDay());
                $crew->setDeceasedYear($deceased->getYear());
            }

            if ($ship !== null) {
                if (!$crew->getStatus()) {
                    $form->get('status')->addError(new FormError('Status is required when a ship is assigned.'));
                }

                if ($crew->getShipRoles()->count() === 0) {
                    $form->get('shipRoles')->addError(new FormError('At least one role is required when a ship is assigned.'));
                }

                $status = $crew->getStatus() ?? '';
                $dateField = match ($status) {
                    'Active' => 'activeDate',
                    'On Leave' => 'onLeaveDate',
                    'Retired' => 'retiredDate',
                    'Missing (MIA)' => 'miaDate',
                    'Deceased' => 'deceasedDate',
                    default => '',
                };

                if ($dateField !== '') {
                    /** @var ImperialDate|null $date */
                    $date = $form->get($dateField)->getData();
                    if (!$date instanceof ImperialDate || $date->getDay() === null || $date->getYear() === null) {
                        $form->get($dateField)->addError(new FormError('Status date is required for the selected status.'));
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Crew::class,
            'user' => null,
            'is_admin' => false,
        ]);
    }
}
