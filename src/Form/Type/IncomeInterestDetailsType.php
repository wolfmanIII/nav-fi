<?php

namespace App\Form\Type;

use App\Entity\IncomeInterestDetails;
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

class IncomeInterestDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeInterestDetails|null $data */
        $data = $builder->getData();
        $startDate = new ImperialDate($data?->getStartYear(), $data?->getStartDay());
        $endDate = new ImperialDate($data?->getEndYear(), $data?->getEndDay());
        $this->addIfEnabled($builder, $options, 'accountRef', TextType::class, [
            'required' => false,
            'label' => 'Account ref',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'instrument', ChoiceType::class, [
            'required' => false,
            'label' => 'Instrument',
            'placeholder' => '-- Select instrument --',
            'choices' => [
                'Savings Account' => 'Savings Account',
                'Term Deposit' => 'Term Deposit',
                'Bond' => 'Bond',
                'Note' => 'Note',
                'Loan Interest' => 'Loan Interest',
                'Investment Fund Share' => 'Investment Fund Share',
                'Corporate Debenture' => 'Corporate Debenture',
                'Trade Finance Facility' => 'Trade Finance Facility',
                'Letter of Credit Interest' => 'Letter of Credit Interest',
                'Treasury Bill' => 'Treasury Bill',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'principal', NumberType::class, [
            'required' => false,
            'label' => 'Principal (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'interestRate', NumberType::class, [
            'required' => false,
            'label' => 'Interest rate (%)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'startDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Start date',
            'data' => $startDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'endDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'End date',
            'data' => $endDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'calcMethod', ChoiceType::class, [
            'required' => false,
            'label' => 'Calc. method',
            'placeholder' => '-- Select method --',
            'choices' => [
                'Simple Interest' => 'Simple Interest',
                'Compound Interest' => 'Compound Interest',
                'Daily Compounding' => 'Daily Compounding',
                'Monthly Compounding' => 'Monthly Compounding',
                'Floating Rate (Index-Linked)' => 'Floating Rate (Index-Linked)',
                'Fixed Rate' => 'Fixed Rate',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'interestEarned', NumberType::class, [
            'required' => false,
            'label' => 'Interest earned (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'netPaid', NumberType::class, [
            'required' => false,
            'label' => 'Net paid (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'disputeWindow', TextareaType::class, [
            'required' => false,
            'label' => 'Dispute window',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeInterestDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $start */
            if ($form->has('startDate')) {
                $start = $form->get('startDate')->getData();
                if ($start instanceof ImperialDate) {
                    $details->setStartDay($start->getDay());
                    $details->setStartYear($start->getYear());
                }
            }

            /** @var ImperialDate|null $end */
            if ($form->has('endDate')) {
                $end = $form->get('endDate')->getData();
                if ($end instanceof ImperialDate) {
                    $details->setEndDay($end->getDay());
                    $details->setEndYear($end->getYear());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeInterestDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
