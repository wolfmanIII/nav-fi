<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\ShipRole;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Ship;
use App\Entity\Campaign;
use Doctrine\ORM\EntityRepository;
use App\Repository\ShipRepository;

class CrewType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Crew $crew */
        $crew = $options['data'];
        $user = $options['user'];
        $campaignStartYear = $crew?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = 0;
        $birthDate = new ImperialDate($crew?->getBirthYear(), $crew?->getBirthDay());
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
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => true,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn (Campaign $campaign) => $campaign->getTitle(),
                'data' => $crew->getShip()?->getCampaign(),
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
                'required' => false,
                'placeholder' => '-- Select a Ship --',
                'choice_label' => fn (Ship $ship) => sprintf('%s - %s(%s)', $ship->getName(), $ship->getType(), $ship->getClass()),
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
                    'class' => 'select m-1 w-full',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                    'data-campaign-ship-target' => 'ship',
                ],
            ])
            ->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'label' => 'Roles',
                'choice_label' => fn (ShipRole $role) => sprintf('%s – %s', $role->getCode(), $role->getName()),
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

            /** @var ImperialDate|null $birth */
            $birth = $form->get('birthDate')->getData();
            if ($birth instanceof ImperialDate) {
                $crew->setBirthDay($birth->getDay());
                $crew->setBirthYear($birth->getYear());
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
