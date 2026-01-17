<?php

namespace App\Form;

use App\Dto\AssetDetailsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\MDriveDetailItemType;
use App\Form\JDriveDetailItemType;
use App\Form\PowerPlantDetailItemType;

class AssetDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('techLevel', TextType::class, [
                'required' => false,
                'label' => 'Tech Level',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('totalCost', NumberType::class, [
                'required' => false,
                'label' => 'Total Cost (MCr)',
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any', 'readonly' => true],
            ])
            ->add('hull', AssetDetailItemType::class, ['label' => 'Hull'])
            ->add('mDrive', MDriveDetailItemType::class, ['label' => 'M-Drive'])
            ->add('jDrive', JDriveDetailItemType::class, ['label' => 'J-Drive'])
            ->add('powerPlant', PowerPlantDetailItemType::class, ['label' => 'Power Plant'])
            ->add('fuel', AssetDetailItemType::class, ['label' => 'Fuel Tanks'])
            ->add('bridge', AssetDetailItemType::class, ['label' => 'Bridge'])
            ->add('computer', AssetDetailItemType::class, ['label' => 'Computer'])
            ->add('sensors', AssetDetailItemType::class, ['label' => 'Sensors'])
            ->add('commonAreas', AssetDetailItemType::class, ['label' => 'Common Areas'])
            ->add('cargo', AssetDetailItemType::class, ['label' => 'Cargo'])
            ->add('weapons', CollectionType::class, [
                'entry_type' => AssetDetailItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
            ])
            ->add('craft', CollectionType::class, [
                'entry_type' => AssetDetailItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
            ])
            ->add('systems', CollectionType::class, [
                'entry_type' => AssetDetailItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
            ])
            ->add('staterooms', CollectionType::class, [
                'entry_type' => AssetDetailItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
            ])
            ->add('software', CollectionType::class, [
                'entry_type' => AssetDetailItemType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetDetailsData::class,
        ]);
    }
}
