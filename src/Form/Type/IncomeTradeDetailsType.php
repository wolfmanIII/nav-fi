<?php

namespace App\Form\Type;

use App\Entity\IncomeTradeDetails;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeTradeDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeTradeDetails|null $data */
        $data = $builder->getData();
        $deliveryDate = new ImperialDate($data?->getDeliveryYear(), $data?->getDeliveryDay());
        $deliveryProofDate = new ImperialDate($data?->getDeliveryProofYear(), $data?->getDeliveryProofDay());
        $this->addIfEnabled($builder, $options, 'location', TextType::class, [
            'required' => false,
            'label' => 'Location',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'transferPoint', TextType::class, [
            'required' => false,
            'label' => 'Transfer point',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'transferCondition', TextType::class, [
            'required' => false,
            'label' => 'Transfer condition',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'goodsDescription', TextareaType::class, [
            'required' => false,
            'label' => 'Goods description',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'qty', IntegerType::class, [
            'required' => false,
            'label' => 'Quantity',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'grade', ChoiceType::class, [
            'required' => false,
            'label' => 'Grade',
            'placeholder' => '-- Select grade --',
            'choices' => [
                'Prime (top quality)' => 'Prime (top quality)',
                'Premium' => 'Premium',
                'Standard' => 'Standard',
                'Economy' => 'Economy',
                'Low Grade' => 'Low Grade',
                'Substandard' => 'Substandard',
                'Mixed Lot' => 'Mixed Lot',
                'Uninspected' => 'Uninspected',
                'Damaged' => 'Damaged',
                'Salvage Quality' => 'Salvage Quality',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'batchIds', TextareaType::class, [
            'required' => false,
            'label' => 'Batch IDs',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'unitPrice', NumberType::class, [
            'required' => false,
            'label' => 'Unit price (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', NumberType::class, [
            'required' => false,
            'label' => 'Payment terms (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'deliveryMethod', TextareaType::class, [
            'required' => false,
            'label' => 'Delivery method',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'deliveryDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Delivery date',
            'data' => $deliveryDate,
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
        $this->addIfEnabled($builder, $options, 'asIsOrWarranty', TextType::class, [
            'required' => false,
            'label' => 'As-is / Warranty',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'warrantyText', TextareaType::class, [
            'required' => false,
            'label' => 'Warranty',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'claimWindow', TextareaType::class, [
            'required' => false,
            'label' => 'Claim window',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'returnPolicy', TextareaType::class, [
            'required' => false,
            'label' => 'Return policy',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeTradeDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $delivery */
            if ($form->has('deliveryDate')) {
                $delivery = $form->get('deliveryDate')->getData();
                if ($delivery instanceof ImperialDate) {
                    $details->setDeliveryDay($delivery->getDay());
                    $details->setDeliveryYear($delivery->getYear());
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
            'data_class' => IncomeTradeDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
