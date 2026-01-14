<?php

namespace App\Form;

use App\Dto\PowerPlantDetailItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PowerPlantDetailItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('tons', NumberType::class, [
                'required' => false,
                'label' => 'Tons',
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any'],
            ])
            ->add('costMcr', NumberType::class, [
                'required' => false,
                'label' => 'Cost (MCr)',
                'attr' => ['class' => 'input m-1 w-full', 'step' => 'any', 'data-cost-mcr' => '1'],
            ])
            ->add('power', IntegerType::class, [
                'required' => false,
                'label' => 'Power',
                'attr' => ['class' => 'input m-1 w-full', 'min' => 0],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PowerPlantDetailItem::class,
        ]);
    }
}
