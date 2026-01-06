<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\Ship;
use App\Entity\ShipRole;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Repository\ShipRepository;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CrewType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Crew $crew */
        $crew = $options['data'];
        $disabled = $crew->hasMortgageSigned();
        $user = $options['user'];
        $campaignStartYear = $crew?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = max($this->limits->getYearMin(), $campaignStartYear ?? $this->limits->getYearMin());
        $birthDate = new ImperialDate($crew?->getBirthYear(), $crew?->getBirthDay());
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
            ->add('surname', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
            ->add('nickname', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'disabled' => $disabled,
            ])
            ->add('birthDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Birth date',
                'data' => $birthDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
                'attr' => ['class' => $disabled ? 'pointer-events-none opacity-60' : ''],
            ])
            ->add('birthWorld', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'disabled' => $disabled,
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'choice_label' => 'name',
                'required' => false,
                'choice_attr' => function (Ship $ship): array {
                    $start = $ship->getCampaign()?->getStartingYear();
                    return ['data-start-year' => $start ?? ''];
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
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                ],
                'disabled' => $disabled,
            ])
            ->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'choice_label' => function (ShipRole $role) {
                    return $role->getCode() . ' - ' . $role->getName();
                },
                'multiple' => true,
                'attr' => ['class' => 'select m-1 h-72 w-full'],
                'disabled' => $disabled,
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
        ]);
    }
}
