<?php

namespace App\Form\Type;

use App\Entity\IncomeContractDetails;
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

class IncomeContractDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeContractDetails|null $data */
        $data = $builder->getData();
        $startDate = new ImperialDate($data?->getStartYear(), $data?->getStartDay());
        $deadlineDate = new ImperialDate($data?->getDeadlineYear(), $data?->getDeadlineDay());
        $this->addIfEnabled($builder, $options, 'jobType', TextType::class, [
            'required' => false,
            'label' => 'Job type',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'location', TextareaType::class, [
            'required' => false,
            'label' => 'Location',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'objective', TextareaType::class, [
            'required' => false,
            'label' => 'Objective',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'successCondition', TextareaType::class, [
            'required' => false,
            'label' => 'Success condition',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'startDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Start date',
            'data' => $startDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'deadlineDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Deadline date',
            'data' => $deadlineDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'bonus', NumberType::class, [
            'required' => false,
            'label' => 'Bonus (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'expensesPolicy', TextareaType::class, [
            'required' => false,
            'label' => 'Expenses policy',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'deposit', NumberType::class, [
            'required' => false,
            'label' => 'Deposit (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'restrictions', TextareaType::class, [
            'required' => false,
            'label' => 'Restrictions',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'confidentialityLevel', TextareaType::class, [
            'required' => false,
            'label' => 'Confidentiality level',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'failureTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Failure terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'cancellationTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Cancellation terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeContractDetails $details */
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

            /** @var ImperialDate|null $deadline */
            if ($form->has('deadlineDate')) {
                $deadline = $form->get('deadlineDate')->getData();
                if ($deadline instanceof ImperialDate) {
                    $details->setDeadlineDay($deadline->getDay());
                    $details->setDeadlineYear($deadline->getYear());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeContractDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
