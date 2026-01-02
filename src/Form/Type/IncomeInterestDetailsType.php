<?php

namespace App\Form\Type;

use App\Entity\IncomeInterestDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
        $builder
            ->add('receiptId', TextType::class, [
                'required' => false,
                'label' => 'Receipt ID',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('accountRef', TextType::class, [
                'required' => false,
                'label' => 'Account ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('instrument', TextType::class, [
                'required' => false,
                'label' => 'Instrument',
                'attr' => ['class' => 'input m-1 w-full'],
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
            ->add('calcMethod', TextType::class, [
                'required' => false,
                'label' => 'Calc. method',
                'attr' => ['class' => 'input m-1 w-full'],
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
        ]);
    }
}
