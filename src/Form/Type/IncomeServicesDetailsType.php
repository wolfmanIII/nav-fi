<?php

namespace App\Form\Type;

use App\Entity\IncomeServicesDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeServicesDetailsType extends AbstractType
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
            ->add('vesselId', TextType::class, [
                'required' => false,
                'label' => 'Vessel ID',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('serviceType', TextType::class, [
                'required' => false,
                'label' => 'Service type',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('requestedBy', TextType::class, [
                'required' => false,
                'label' => 'Requested by',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('startDay', IntegerType::class, [
                'required' => false,
                'label' => 'Start Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('startYear', IntegerType::class, [
                'required' => false,
                'label' => 'Start Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('endDay', IntegerType::class, [
                'required' => false,
                'label' => 'End Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('endYear', IntegerType::class, [
                'required' => false,
                'label' => 'End Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('workSummary', TextareaType::class, [
                'required' => false,
                'label' => 'Work summary',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('partsMaterials', TextareaType::class, [
                'required' => false,
                'label' => 'Parts / materials',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('risks', TextareaType::class, [
                'required' => false,
                'label' => 'Risks',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('extras', TextareaType::class, [
                'required' => false,
                'label' => 'Extras',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('liabilityLimit', NumberType::class, [
                'required' => false,
                'label' => 'Liability limit (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Cancellation terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeServicesDetails::class,
        ]);
    }
}
