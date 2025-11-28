<?php

namespace App\Form;

use App\Entity\Crew;
use App\Entity\Ship;
use App\Entity\ShipRole;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CrewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Crew $crew */
        $crew = $options['data'];
        $disabled = $crew->hasMortgageSigned();
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
            ->add('birthYear', NumberType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'disabled' => $disabled,
            ])
            ->add('birthDay', NumberType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'disabled' => $disabled,
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
                'attr' => ['class' => 'select m-1 w-full'],
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Crew::class,
        ]);
    }
}
