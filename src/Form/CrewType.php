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
            ->add('background', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 13],
            ])
        ;

        if ($options['is_admin']) {
            $builder->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'choice_label' => function (ShipRole $role) {
                    return $role->getCode() . ' - ' . $role->getName();
                },
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'select m-1 h-72 w-full'],
            ]);
        }

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
