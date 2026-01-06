<?php

namespace App\Form\Type;

use App\Entity\IncomeCharterDetails;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeCharterDetailsType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeCharterDetails|null $data */
        $data = $builder->getData();
        $startDate = new ImperialDate($data?->getStartYear(), $data?->getStartDay());
        $endDate = new ImperialDate($data?->getEndYear(), $data?->getEndDay());
        $builder
            ->add('areaOrRoute', TextType::class, [
                'required' => false,
                'label' => 'Area / Route',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('purpose', ChoiceType::class, [
                'required' => false,
                'label' => 'Purpose',
                'placeholder' => '-- Select a purpose --',
                'choices' => [
                    'Tourism / Sightseeing' => 'Tourism / Sightseeing',
                    'Event / Media Charter' => 'Event / Media Charter',
                    'Private Charter (Non-Service)' => 'Private Charter (Non-Service)',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('manifestSummary', TextareaType::class, [
                'required' => false,
                'label' => 'Manifest summary',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
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
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('deposit', NumberType::class, [
                'required' => false,
                'label' => 'Deposit (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('extras', TextareaType::class, [
                'required' => false,
                'label' => 'Extras',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('damageTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Damage terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('cancellationTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Cancellation terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeCharterDetails $details */
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
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeCharterDetails::class,
            'campaign_start_year' => null,
        ]);
    }
}
