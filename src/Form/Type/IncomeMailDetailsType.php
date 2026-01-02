<?php

namespace App\Form\Type;

use App\Entity\IncomeMailDetails;
use App\Form\Config\DayYearLimits;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeMailDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
            ->add('dispatchDay', NumberType::class, [
                'required' => false,
                'label' => 'Dispatch Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('dispatchYear', NumberType::class, [
                'required' => false,
                'label' => 'Dispatch Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('deliveryDay', NumberType::class, [
                'required' => false,
                'label' => 'Delivery Day',
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('deliveryYear', NumberType::class, [
                'required' => false,
                'label' => 'Delivery Year',
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('mailType', ChoiceType::class, [
                'required' => false,
                'label' => 'Mail type',
                'placeholder' => '-- Select mail type --',
                'choices' => [
                    'Official Mail' => 'Official Mail',
                    'Priority Mail' => 'Priority Mail',
                    'Registered Mail' => 'Registered Mail',
                    'Secure Pouch' => 'Secure Pouch',
                    'Diplomatic Bag' => 'Diplomatic Bag',
                    'Courier Packet' => 'Courier Packet',
                    'Bulk Post' => 'Bulk Post',
                    'Parcel Mail' => 'Parcel Mail',
                    'Medical Dispatch' => 'Medical Dispatch',
                    'Emergency Dispatch' => 'Emergency Dispatch',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('packageCount', IntegerType::class, [
                'required' => false,
                'label' => 'Package count',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('totalMass', NumberType::class, [
                'required' => false,
                'label' => 'Total mass',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('securityLevel', TextType::class, [
                'required' => false,
                'label' => 'Security level',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('sealCodes', TextType::class, [
                'required' => false,
                'label' => 'Seal codes',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('proofOfDelivery', TextareaType::class, [
                'required' => false,
                'label' => 'Proof of delivery',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('liabilityLimit', NumberType::class, [
                'required' => false,
                'label' => 'Liability limit (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeMailDetails::class,
        ]);
    }
}
