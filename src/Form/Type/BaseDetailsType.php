<?php

namespace App\Form\Type;

use App\Form\Data\BaseDetailsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hull', HullType::class, [
                'label' => 'Hull Specification',
            ])
            ->add('powerPlant', DriveType::class, [
                'label' => 'Power Plant',
                'rating_label' => 'Output',
            ])
            ->add('bridge', GenericComponentType::class, ['label' => 'Bridge'])
            ->add('computer', GenericComponentType::class, ['label' => 'Computer'])
            ->add('sensors', GenericComponentType::class, ['label' => 'Sensors'])
            ->add('cargo', GenericComponentType::class, ['label' => 'Cargo'])
            ->add('fuel', GenericComponentType::class, ['label' => 'Fuel'])

            ->add('staterooms', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('commonAreas', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('weapons', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('systems', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('software', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('craft', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \App\Form\AssetDetailItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])

            ->add('techLevel', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Tech Level',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('totalCost', \Symfony\Component\Form\Extension\Core\Type\NumberType::class, [
                'label' => 'Total Cost (MCr)',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BaseDetailsData::class,
        ]);
    }
}
