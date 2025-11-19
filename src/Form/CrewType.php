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
        $builder
            ->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full']])
            ->add('surname', TextType::class, ['attr' => ['class' => 'input m-1 w-full']])
            ->add('nickname', TextType::class, ['attr' => ['class' => 'input m-1 w-full'], 'required' => false])
            ->add('birthYear', NumberType::class, ['attr' => ['class' => 'input m-1 w-full'], 'required' => false])
            ->add('birthDay', NumberType::class, ['attr' => ['class' => 'input m-1 w-full'], 'required' => false])
            ->add('birthWorld', TextType::class, ['attr' => ['class' => 'input m-1 w-full'], 'required' => false])
            ->add('code', TextType::class, ['attr' => ['class' => 'input m-1 w-full', 'readonly' => true], 'required' => false])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'choice_label' => 'name',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'input m-1 h-40 w-full'],
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
