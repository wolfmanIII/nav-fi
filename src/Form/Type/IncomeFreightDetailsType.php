<?php

namespace App\Form\Type;

use App\Entity\IncomeFreightDetails;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeFreightDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeFreightDetails|null $data */
        $data = $builder->getData();
        $pickupDate = new ImperialDate($data?->getPickupYear(), $data?->getPickupDay());
        $deliveryDate = new ImperialDate($data?->getDeliveryYear(), $data?->getDeliveryDay());
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
            ->add('pickupDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Pickup date',
                'data' => $pickupDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('deliveryDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Delivery date',
                'data' => $deliveryDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('cargoDescription', TextareaType::class, [
                'required' => false,
                'label' => 'Cargo description',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('cargoQty', TextType::class, [
                'required' => false,
                'label' => 'Cargo qty',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('declaredValue', NumberType::class, [
                'required' => false,
                'label' => 'Declared value (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
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

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeFreightDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $pickup */
            $pickup = $form->get('pickupDate')->getData();
            if ($pickup instanceof ImperialDate) {
                $details->setPickupDay($pickup->getDay());
                $details->setPickupYear($pickup->getYear());
            }

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
            'data_class' => IncomeFreightDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
