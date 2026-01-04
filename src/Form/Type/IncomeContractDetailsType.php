<?php

namespace App\Form\Type;

use App\Entity\IncomeContractDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeContractDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $builder
            ->add('jobType', TextType::class, [
                'required' => false,
                'label' => 'Job type',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('location', TextareaType::class, [
                'required' => false,
                'label' => 'Location',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('objective', TextareaType::class, [
                'required' => false,
                'label' => 'Objective',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('successCondition', TextareaType::class, [
                'required' => false,
                'label' => 'Success condition',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
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
            ->add('deadlineDay', NumberType::class, [
                'required' => false,
                'label' => 'Deadline Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('deadlineYear', NumberType::class, [
                'required' => false,
                'label' => 'Deadline Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('bonus', NumberType::class, [
                'required' => false,
                'label' => 'Bonus (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('expensesPolicy', TextareaType::class, [
                'required' => false,
                'label' => 'Expenses policy',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('deposit', NumberType::class, [
                'required' => false,
                'label' => 'Deposit (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('restrictions', TextareaType::class, [
                'required' => false,
                'label' => 'Restrictions',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('confidentialityLevel', TextareaType::class, [
                'required' => false,
                'label' => 'Confidentiality level',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('failureTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Failure terms',
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
            'data_class' => IncomeContractDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
