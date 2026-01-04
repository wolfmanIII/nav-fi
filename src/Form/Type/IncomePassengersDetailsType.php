<?php

namespace App\Form\Type;

use App\Entity\IncomePassengersDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomePassengersDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $builder
            ->add('origin', TextType::class, [
                'required' => false,
                'label' => 'Origin',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('destination', TextType::class, [
                'required' => false,
                'label' => 'Destination',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('departureDay', NumberType::class, [
                'required' => false,
                'label' => 'Departure Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('departureYear', NumberType::class, [
                'required' => false,
                'label' => 'Departure Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('arrivalDay', NumberType::class, [
                'required' => false,
                'label' => 'Arrival Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('arrivalYear', NumberType::class, [
                'required' => false,
                'label' => 'Arrival Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('classOrBerth', TextType::class, [
                'required' => false,
                'label' => 'Class / Berth',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('qty', NumberType::class, [
                'required' => false,
                'label' => 'Qty',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('passengerNames', TextareaType::class, [
                'required' => false,
                'label' => 'Passenger names',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('passengerContact', TextType::class, [
                'required' => false,
                'label' => 'Passenger contact',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('baggageAllowance', TextType::class, [
                'required' => false,
                'label' => 'Baggage allowance',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('extraBaggage', TextType::class, [
                'required' => false,
                'label' => 'Extra baggage',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('refundChangePolicy', TextareaType::class, [
                'required' => false,
                'label' => 'Refund/Change policy',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomePassengersDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
