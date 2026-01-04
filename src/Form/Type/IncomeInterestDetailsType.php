<?php

namespace App\Form\Type;

use App\Entity\IncomeInterestDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeInterestDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $builder
            ->add('accountRef', TextType::class, [
                'required' => false,
                'label' => 'Account ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('instrument', ChoiceType::class, [
                'required' => false,
                'label' => 'Instrument',
                'placeholder' => '-- Select instrument --',
                'choices' => [
                    'Savings Account' => 'Savings Account',
                    'Term Deposit' => 'Term Deposit',
                    'Bond' => 'Bond',
                    'Note' => 'Note',
                    'Loan Interest' => 'Loan Interest',
                    'Investment Fund Share' => 'Investment Fund Share',
                    'Corporate Debenture' => 'Corporate Debenture',
                    'Trade Finance Facility' => 'Trade Finance Facility',
                    'Letter of Credit Interest' => 'Letter of Credit Interest',
                    'Treasury Bill' => 'Treasury Bill',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('principal', NumberType::class, [
                'required' => false,
                'label' => 'Principal (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('interestRate', NumberType::class, [
                'required' => false,
                'label' => 'Interest rate (%)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
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
            ->add('calcMethod', ChoiceType::class, [
                'required' => false,
                'label' => 'Calc. method',
                'placeholder' => '-- Select method --',
                'choices' => [
                    'Simple Interest' => 'Simple Interest',
                    'Compound Interest' => 'Compound Interest',
                    'Daily Compounding' => 'Daily Compounding',
                    'Monthly Compounding' => 'Monthly Compounding',
                    'Floating Rate (Index-Linked)' => 'Floating Rate (Index-Linked)',
                    'Fixed Rate' => 'Fixed Rate',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('interestEarned', NumberType::class, [
                'required' => false,
                'label' => 'Interest earned (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('netPaid', NumberType::class, [
                'required' => false,
                'label' => 'Net paid (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('disputeWindow', TextareaType::class, [
                'required' => false,
                'label' => 'Dispute window',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeInterestDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
