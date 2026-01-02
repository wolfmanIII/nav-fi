<?php

namespace App\Form\Type;

use App\Entity\IncomeInsuranceDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeInsuranceDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('incidentRef', TextType::class, [
                'required' => false,
                'label' => 'Incident ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incidentDay', NumberType::class, [
                'required' => false,
                'label' => 'Incident Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('incidentYear', NumberType::class, [
                'required' => false,
                'label' => 'Incident Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('incidentLocation', TextType::class, [
                'required' => false,
                'label' => 'Incident location',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incidentCause', TextType::class, [
                'required' => false,
                'label' => 'Incident cause',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('lossType', ChoiceType::class, [
                'required' => false,
                'label' => 'Loss type',
                'placeholder' => '-- Select loss type --',
                'choices' => [
                    'Hull Damage' => 'Hull Damage',
                    'Cargo Loss/Damage' => 'Cargo Loss/Damage',
                    'Personal Injury' => 'Personal Injury',
                    'Medical Expenses' => 'Medical Expenses',
                    'Liability Claim' => 'Liability Claim',
                    'Theft / Piracy' => 'Theft / Piracy',
                    'Fire / Explosion' => 'Fire / Explosion',
                    'Collision' => 'Collision',
                    'Systems Failure' => 'Systems Failure',
                    'Total Loss' => 'Total Loss',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('verifiedLoss', NumberType::class, [
                'required' => false,
                'label' => 'Verified loss (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('deductible', NumberType::class, [
                'required' => false,
                'label' => 'Deductible (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('acceptanceEffect', TextareaType::class, [
                'required' => false,
                'label' => 'Acceptance effect',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('subrogationTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Subrogation terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('coverageNotes', TextareaType::class, [
                'required' => false,
                'label' => 'Coverage notes',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeInsuranceDetails::class,
        ]);
    }
}
