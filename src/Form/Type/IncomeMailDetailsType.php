<?php

namespace App\Form\Type;

use App\Entity\IncomeMailDetails;
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

class IncomeMailDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeMailDetails|null $data */
        $data = $builder->getData();
        $dispatchDate = new ImperialDate($data?->getDispatchYear(), $data?->getDispatchDay());
        $deliveryDate = new ImperialDate($data?->getDeliveryYear(), $data?->getDeliveryDay());
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
        $this->addIfEnabled($builder, $options, 'dispatchDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Dispatch date',
            'data' => $dispatchDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
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
        $this->addIfEnabled($builder, $options, 'mailType', ChoiceType::class, [
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
        ]);
        $this->addIfEnabled($builder, $options, 'packageCount', IntegerType::class, [
            'required' => false,
            'label' => 'Package count',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'totalMass', NumberType::class, [
            'required' => false,
            'label' => 'Total mass',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'securityLevel', ChoiceType::class, [
            'required' => false,
            'label' => 'Security level',
            'placeholder' => '-- Select security --',
            'choices' => [
                'Open' => 'Open',
                'Sealed' => 'Sealed',
                'Registered' => 'Registered',
                'Restricted' => 'Restricted',
                'Secure' => 'Secure',
                'High Security' => 'High Security',
                'Diplomatic' => 'Diplomatic',
                'Black (Need-to-Know)' => 'Black (Need-to-Know)',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'sealCodes', TextType::class, [
            'required' => false,
            'label' => 'Seal codes',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'proofOfDelivery', ChoiceType::class, [
            'required' => false,
            'label' => 'Proof of delivery',
            'placeholder' => '-- Select proof --',
            'choices' => [
                'Recipient Signature' => 'Recipient Signature',
                'Authority Stamp' => 'Authority Stamp',
                'Port Log Entry' => 'Port Log Entry',
                'Delivery Scan / Barcode' => 'Delivery Scan / Barcode',
                'Seal Check (Intact)' => 'Seal Check (Intact)',
                'Chain-of-Custody Form' => 'Chain-of-Custody Form',
                'Secure Drop Confirmation' => 'Secure Drop Confirmation',
                'Photo Evidence' => 'Photo Evidence',
                'Witness Confirmation' => 'Witness Confirmation',
                'Encrypted Receipt Code' => 'Encrypted Receipt Code',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'liabilityLimit', NumberType::class, [
            'required' => false,
            'label' => 'Liability limit (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeMailDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $dispatch */
            if ($form->has('dispatchDate')) {
                $dispatch = $form->get('dispatchDate')->getData();
                if ($dispatch instanceof ImperialDate) {
                    $details->setDispatchDay($dispatch->getDay());
                    $details->setDispatchYear($dispatch->getYear());
                }
            }

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
            'data_class' => IncomeMailDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
