<?php

namespace App\Form\Type;

use App\Entity\IncomeCharterDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeCharterDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $builder
            ->add('areaOrRoute', TextType::class, [
                'required' => false,
                'label' => 'Area / Route',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('purpose', ChoiceType::class, [
                'required' => false,
                'label' => 'Purpose',
                'placeholder' => '-- Select a purpose --',
                'choices' => [
                    'Tourism / Sightseeing' => 'Tourism / Sightseeing',
                    'Event / Media Charter' => 'Event / Media Charter',
                    'Private Charter (Non-Service)' => 'Private Charter (Non-Service)',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('manifestSummary', TextareaType::class, [
                'required' => false,
                'label' => 'Manifest summary',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('startDay', NumberType::class, [
                'required' => false,
                'label' => 'Start Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('startYear', NumberType::class, [
                'required' => false,
                'label' => 'Start Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('endDay', NumberType::class, [
                'required' => false,
                'label' => 'End Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('endYear', NumberType::class, [
                'required' => false,
                'label' => 'End Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('deposit', NumberType::class, [
                'required' => false,
                'label' => 'Deposit (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('extras', TextareaType::class, [
                'required' => false,
                'label' => 'Extras',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('damageTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Damage terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Cancellation terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeCharterDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
