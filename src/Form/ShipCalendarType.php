<?php

namespace App\Form;

use App\Entity\Ship;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipCalendarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sessionDay', IntegerType::class, [
                'label' => 'Session Day',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('sessionYear', IntegerType::class, [
                'label' => 'Session Year',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ship::class,
        ]);
    }
}
