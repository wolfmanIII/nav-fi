<?php

namespace App\Form\Type;

use App\Entity\IncomeSubsidyDetails;
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

class IncomeSubsidyDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeSubsidyDetails|null $data */
        $data = $builder->getData();
        $startDate = new ImperialDate($data?->getStartYear(), $data?->getStartDay());
        $endDate = new ImperialDate($data?->getEndYear(), $data?->getEndDay());
        $deliveryProofDate = new ImperialDate($data?->getDeliveryProofYear(), $data?->getDeliveryProofDay());
        $builder
            ->add('programRef', TextType::class, [
                'required' => false,
                'label' => 'Program ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
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
            ->add('startDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Start date',
                'data' => $startDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('endDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'End date',
                'data' => $endDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('deliveryProofRef', TextType::class, [
                'required' => false,
                'label' => 'Delivery proof ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('deliveryProofDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Delivery proof date',
                'data' => $deliveryProofDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('deliveryProofReceivedBy', TextType::class, [
                'required' => false,
                'label' => 'Received by',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('serviceLevel', TextType::class, [
                'required' => false,
                'label' => 'Service level',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('subsidyAmount', NumberType::class, [
                'required' => false,
                'label' => 'Subsidy amount (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('milestones', TextareaType::class, [
                'required' => false,
                'label' => 'Milestones',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('reportingRequirements', TextareaType::class, [
                'required' => false,
                'label' => 'Reporting requirements',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('nonComplianceTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Non-compliance terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('proofRequirements', TextareaType::class, [
                'required' => false,
                'label' => 'Proof requirements',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Cancellation terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeSubsidyDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $start */
            $start = $form->get('startDate')->getData();
            if ($start instanceof ImperialDate) {
                $details->setStartDay($start->getDay());
                $details->setStartYear($start->getYear());
            }

            /** @var ImperialDate|null $end */
            $end = $form->get('endDate')->getData();
            if ($end instanceof ImperialDate) {
                $details->setEndDay($end->getDay());
                $details->setEndYear($end->getYear());
            }

            /** @var ImperialDate|null $deliveryProof */
            $deliveryProof = $form->get('deliveryProofDate')->getData();
            if ($deliveryProof instanceof ImperialDate) {
                $details->setDeliveryProofDay($deliveryProof->getDay());
                $details->setDeliveryProofYear($deliveryProof->getYear());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeSubsidyDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
