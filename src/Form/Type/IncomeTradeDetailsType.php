<?php

namespace App\Form\Type;

use App\Entity\IncomeTradeDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeTradeDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'Location',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('transferPoint', TextType::class, [
                'required' => false,
                'label' => 'Transfer point',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('transferCondition', TextType::class, [
                'required' => false,
                'label' => 'Transfer condition',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('goodsDescription', TextareaType::class, [
                'required' => false,
                'label' => 'Goods description',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('qty', IntegerType::class, [
                'required' => false,
                'label' => 'Quantity',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('grade', TextType::class, [
                'required' => false,
                'label' => 'Grade',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('batchIds', TextareaType::class, [
                'required' => false,
                'label' => 'Batch IDs',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('unitPrice', NumberType::class, [
                'required' => false,
                'label' => 'Unit price (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', NumberType::class, [
                'required' => false,
                'label' => 'Payment terms (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('deliveryMethod', TextareaType::class, [
                'required' => false,
                'label' => 'Delivery method',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('deliveryDay', IntegerType::class, [
                'required' => false,
                'label' => 'Delivery Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('deliveryYear', IntegerType::class, [
                'required' => false,
                'label' => 'Delivery Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('asIsOrWarranty', TextType::class, [
                'required' => false,
                'label' => 'As-is / Warranty',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('warrantyText', TextareaType::class, [
                'required' => false,
                'label' => 'Warranty',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('claimWindow', TextareaType::class, [
                'required' => false,
                'label' => 'Claim window',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('returnPolicy', TextareaType::class, [
                'required' => false,
                'label' => 'Return policy',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeTradeDetails::class,
        ]);
    }
}
