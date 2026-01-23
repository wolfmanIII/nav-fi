<?php

namespace App\Form\Type;

use App\Form\Data\HullData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HullType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Standard Hull'],
            ])
            ->add('configuration', TextType::class, [
                'label' => 'Configuration',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Streamlined'],
            ])
            ->add('tons', IntegerType::class, [
                'label' => 'Tonnage',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. 200'],
            ])
            ->add('points', IntegerType::class, [
                'label' => 'Hull Points',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('costMcr', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, [
                'label' => 'Cost (MCr)',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HullData::class,
        ]);
    }
}
