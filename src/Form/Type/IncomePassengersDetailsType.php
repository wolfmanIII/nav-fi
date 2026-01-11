<?php

namespace App\Form\Type;

use App\Entity\IncomePassengersDetails;
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

class IncomePassengersDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomePassengersDetails|null $data */
        $data = $builder->getData();
        $departureDate = new ImperialDate($data?->getDepartureYear(), $data?->getDepartureDay());
        $arrivalDate = new ImperialDate($data?->getArrivalYear(), $data?->getArrivalDay());
        $deliveryProofDate = new ImperialDate($data?->getDeliveryProofYear(), $data?->getDeliveryProofDay());
        $this->addIfEnabled($builder, $options, 'origin', TextType::class, [
            'required' => false,
            'label' => 'Origin',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'destination', TextType::class, [
            'required' => false,
            'label' => 'Destination',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'departureDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Departure date',
            'data' => $departureDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'arrivalDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Arrival date',
            'data' => $arrivalDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'deliveryProofRef', TextType::class, [
            'required' => false,
            'label' => 'Delivery proof ref',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'deliveryProofDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Delivery proof date',
            'data' => $deliveryProofDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'deliveryProofReceivedBy', TextType::class, [
            'required' => false,
            'label' => 'Received by',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'classOrBerth', TextType::class, [
            'required' => false,
            'label' => 'Class / Berth',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'qty', NumberType::class, [
            'required' => false,
            'label' => 'Qty',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'passengerNames', TextareaType::class, [
            'required' => false,
            'label' => 'Passenger names',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'passengerContact', TextType::class, [
            'required' => false,
            'label' => 'Passenger contact',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'baggageAllowance', TextType::class, [
            'required' => false,
            'label' => 'Baggage allowance',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'extraBaggage', TextType::class, [
            'required' => false,
            'label' => 'Extra baggage',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'refundChangePolicy', TextareaType::class, [
            'required' => false,
            'label' => 'Refund/Change policy',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomePassengersDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $dep */
            if ($form->has('departureDate')) {
                $dep = $form->get('departureDate')->getData();
                if ($dep instanceof ImperialDate) {
                    $details->setDepartureDay($dep->getDay());
                    $details->setDepartureYear($dep->getYear());
                }
            }

            /** @var ImperialDate|null $arr */
            if ($form->has('arrivalDate')) {
                $arr = $form->get('arrivalDate')->getData();
                if ($arr instanceof ImperialDate) {
                    $details->setArrivalDay($arr->getDay());
                    $details->setArrivalYear($arr->getYear());
                }
            }

            /** @var ImperialDate|null $deliveryProof */
            if ($form->has('deliveryProofDate')) {
                $deliveryProof = $form->get('deliveryProofDate')->getData();
                if ($deliveryProof instanceof ImperialDate) {
                    $details->setDeliveryProofDay($deliveryProof->getDay());
                    $details->setDeliveryProofYear($deliveryProof->getYear());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomePassengersDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
