<?php

namespace App\Form\Type;

use App\Entity\IncomeTradeDetails;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeTradeDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeTradeDetails|null $data */
        $data = $builder->getData();
        $deliveryDate = new ImperialDate($data?->getDeliveryYear(), $data?->getDeliveryDay());
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
            ->add('grade', ChoiceType::class, [
                'required' => false,
                'label' => 'Grade',
                'placeholder' => '-- Select grade --',
                'choices' => [
                    'Prime (top quality)' => 'Prime (top quality)',
                    'Premium' => 'Premium',
                    'Standard' => 'Standard',
                    'Economy' => 'Economy',
                    'Low Grade' => 'Low Grade',
                    'Substandard' => 'Substandard',
                    'Mixed Lot' => 'Mixed Lot',
                    'Uninspected' => 'Uninspected',
                    'Damaged' => 'Damaged',
                    'Salvage Quality' => 'Salvage Quality',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
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
            ->add('deliveryDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Delivery date',
                'data' => $deliveryDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
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

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeTradeDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $delivery */
            $delivery = $form->get('deliveryDate')->getData();
            if ($delivery instanceof ImperialDate) {
                $details->setDeliveryDay($delivery->getDay());
                $details->setDeliveryYear($delivery->getYear());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeTradeDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
