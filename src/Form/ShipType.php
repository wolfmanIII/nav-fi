<?php

namespace App\Form;

use App\Entity\Ship;
use App\Form\Type\TravellerMoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Ship $ship */
        $ship = $options['data'];
        $disabled = $ship->hasMortgageSigned();
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
            ->add('type', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
            ->add('class', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
            ->add('price', TravellerMoneyType::class, [
                'label' => 'Price(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ship::class,
        ]);
    }
}
