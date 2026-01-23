<?php

namespace App\Form\Type;

use App\Form\Data\DriveData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('rating', IntegerType::class, [
                'label' => $options['rating_label'] ?? 'Rating',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('tons', NumberType::class, [
                'label' => 'Tonnage',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('costMcr', NumberType::class, [
                'label' => 'Cost (MCr)',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DriveData::class,
            'rating_label' => 'Rating',
        ]);
        
        $resolver->setAllowedTypes('rating_label', 'string');
    }
}
