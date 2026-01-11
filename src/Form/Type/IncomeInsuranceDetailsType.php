<?php

namespace App\Form\Type;

use App\Entity\IncomeInsuranceDetails;
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

class IncomeInsuranceDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $campaignStartYear = $options['campaign_start_year'] ?? null;
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        /** @var IncomeInsuranceDetails|null $data */
        $data = $builder->getData();
        $incidentDate = new ImperialDate($data?->getIncidentYear(), $data?->getIncidentDay());
        $this->addIfEnabled($builder, $options, 'incidentRef', TextType::class, [
            'required' => false,
            'label' => 'Incident ref',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'incidentDate', ImperialDateType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Incident date',
            'data' => $incidentDate,
            'min_year' => $minYear,
            'max_year' => $this->limits->getYearMax(),
        ]);
        $this->addIfEnabled($builder, $options, 'incidentLocation', TextType::class, [
            'required' => false,
            'label' => 'Incident location',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'incidentCause', TextType::class, [
            'required' => false,
            'label' => 'Incident cause',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'lossType', ChoiceType::class, [
            'required' => false,
            'label' => 'Loss type',
            'placeholder' => '-- Select loss type --',
            'choices' => [
                'Hull Damage' => 'Hull Damage',
                'Cargo Loss/Damage' => 'Cargo Loss/Damage',
                'Personal Injury' => 'Personal Injury',
                'Medical Expenses' => 'Medical Expenses',
                'Liability Claim' => 'Liability Claim',
                'Theft / Piracy' => 'Theft / Piracy',
                'Fire / Explosion' => 'Fire / Explosion',
                'Collision' => 'Collision',
                'Systems Failure' => 'Systems Failure',
                'Total Loss' => 'Total Loss',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'verifiedLoss', NumberType::class, [
            'required' => false,
            'label' => 'Verified loss (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'deductible', NumberType::class, [
            'required' => false,
            'label' => 'Deductible (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'acceptanceEffect', ChoiceType::class, [
            'required' => false,
            'label' => 'Acceptance effect',
            'placeholder' => '-- Select acceptance --',
            'choices' => [
                'Settles the claim in full' => 'Settles the claim in full',
                'Partial settlement (balance pending)' => 'Partial settlement (balance pending)',
                'Advance payment only' => 'Advance payment only',
                'Settlement without admission of liability' => 'Settlement without admission of liability',
                'Final payment subject to audit' => 'Final payment subject to audit',
                'Payment covers listed items only' => 'Payment covers listed items only',
                'Payment excludes deductible and fees' => 'Payment excludes deductible and fees',
                'Settlement subject to subrogation rights' => 'Settlement subject to subrogation rights',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'subrogationTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Subrogation terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'coverageNotes', TextareaType::class, [
            'required' => false,
            'label' => 'Coverage notes',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var IncomeInsuranceDetails $details */
            $details = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $incident */
            if ($form->has('incidentDate')) {
                $incident = $form->get('incidentDate')->getData();
                if ($incident instanceof ImperialDate) {
                    $details->setIncidentDay($incident->getDay());
                    $details->setIncidentYear($incident->getYear());
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeInsuranceDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
