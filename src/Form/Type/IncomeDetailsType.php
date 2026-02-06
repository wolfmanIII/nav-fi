<?php

namespace App\Form\Type;

use App\Form\Config\DayYearLimits;
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

/**
 * Tipo di form generico per i dettagli dell'Income (memorizzati in JSON).
 * Sostituisce i 12 tipi specifici (Freight, Trade, ecc.) utilizzando una configurazione dinamica.
 *
 * @author Antigravity
 */
class IncomeDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    private const TYPE_MAPPING = [
        // Numeri / Valuta
        'amount' => NumberType::class,
        'value' => NumberType::class,
        'limit' => NumberType::class,
        'price' => NumberType::class,
        'rate' => NumberType::class,
        'bonus' => NumberType::class,
        'deposit' => NumberType::class,
        'principal' => NumberType::class,
        'subsidyAmount' => NumberType::class,
        'interestEarned' => NumberType::class,
        'netPaid' => NumberType::class,
        'verifiedLoss' => NumberType::class,
        'deductible' => NumberType::class,
        'qtyValue' => NumberType::class,
        'unitPrice' => NumberType::class,

        // Interi / Quantità
        'qty' => IntegerType::class,
        'pax' => IntegerType::class,
        'packageCount' => IntegerType::class,

        // Aree di testo
        'summary' => TextareaType::class,
        'notes' => TextareaType::class,
        'policy' => TextareaType::class,
        'terms' => TextareaType::class,
        'requirements' => TextareaType::class,
        'summary' => TextareaType::class,
        'notes' => TextareaType::class,
        'policy' => TextareaType::class,
        'terms' => TextareaType::class,
        'requirements' => TextareaType::class,
        // 'summary' duplicato rimosso
        'description' => TextareaType::class,
        'objective' => TextareaType::class,
        'workSummary' => TextareaType::class,
        'partsMaterials' => TextareaType::class,
        'risks' => TextareaType::class,
        'hazards' => TextareaType::class,
        'recoveredItemsSummary' => TextareaType::class,
        // legalBasis moved to TextType for datalist support
        'warrantyText' => TextareaType::class,
        'claimWindow' => TextareaType::class,
        'returnPolicy' => TextareaType::class,
        'manifestSummary' => TextareaType::class,
        'milestones' => TextareaType::class,
        'reportingRequirements' => TextareaType::class,
        'nonComplianceTerms' => TextareaType::class,
        'proofRequirements' => TextareaType::class,
        'cancellationTerms' => TextareaType::class,
        'failureTerms' => TextareaType::class,
        // paymentTerms moved to TextType for datalist support
        'expensesPolicy' => TextareaType::class,
        'restrictions' => TextareaType::class,
        'confidentialityLevel' => TextareaType::class,
        'batchIds' => TextareaType::class,
    ];

    private const DATE_FIELDS = [
        'startDate' => ['day' => 'startDay', 'year' => 'startYear'],
        'endDate' => ['day' => 'endDay', 'year' => 'endYear'],
        'pickupDate' => ['day' => 'pickupDay', 'year' => 'pickupYear'],
        'deliveryDate' => ['day' => 'deliveryDay', 'year' => 'deliveryYear'],
        'deliveryProofDate' => ['day' => 'deliveryProofDay', 'year' => 'deliveryProofYear'],
        'departureDate' => ['day' => 'departureDay', 'year' => 'departureYear'],
        'arrivalDate' => ['day' => 'arrivalDay', 'year' => 'arrivalYear'],
        'dispatchDate' => ['day' => 'dispatchDay', 'year' => 'dispatchYear'],
        'incidentDate' => ['day' => 'incidentDay', 'year' => 'incidentYear'],
        'deadlineDate' => ['day' => 'deadlineDay', 'year' => 'deadlineYear'],
    ];

    private const ENUM_FIELDS = [
        'calcMethod' => [
            'choices' => [
                'Simple' => 'Simple',
                'Compound' => 'Compound',
            ],
        ],
        'mailType' => [
            'choices' => [
                'X-Boat' => 'X-Boat',
                'Private Courier' => 'Private Courier',
                'Standard' => 'Standard',
                'Freight Packet' => 'Freight Packet',
            ],
        ],
        'securityLevel' => [
            'choices' => [
                'Unclassified' => 'Unclassified',
                'Restricted' => 'Restricted',
                'Confidential' => 'Confidential',
                'Secret' => 'Secret',
                'Top Secret' => 'Top Secret',
            ],
        ],
        'classOrBerth' => [
            'choices' => [
                'High Passage' => 'High Passage',
                'Middle Passage' => 'Middle Passage',
                'Basic Passage' => 'Basic Passage',
                'Low Passage' => 'Low Passage',
                'Working Passage' => 'Working Passage',
            ],
        ],
        'lossType' => [
            'choices' => [
                'Total Hull Loss' => 'Total Hull Loss',
                'Partial Damage' => 'Partial Damage',
                'Third Party Liability' => 'Third Party Liability',
                'Cargo Loss' => 'Cargo Loss',
            ],
        ],
        'serviceLevel' => [
            'choices' => [
                'Routine' => 'Routine',
                'Priority' => 'Priority',
                'Critical' => 'Critical',
            ],
        ],
    ];

    private const SUGGESTION_FIELDS = [
        'paymentTerms' => 'list-payment-terms',
        'legalBasis' => 'list-legal-basis',
        'jobType' => 'list-job-type',
        'serviceType' => 'list-service-type',
        'deliveryMethod' => 'list-delivery-method',
        'proofOfDelivery' => 'list-proof-of-delivery',
    ];

    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $enabled = $options['enabled_fields'] ?? [];
        $campaignStartYear = $options['campaign_start_year'] ?? $this->limits->getYearMin();
        $details = $options['data'] ?? [];

        foreach ($enabled as $fieldName) {
            // Gestione Date Imperiali
            if (isset(self::DATE_FIELDS[$fieldName])) {
                $config = self::DATE_FIELDS[$fieldName];
                $day = $details[$config['day']] ?? null;
                $year = $details[$config['year']] ?? null;

                $this->addIfEnabled($builder, $options, $fieldName, ImperialDateType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => new ImperialDate($year, $day),
                    'min_year' => $campaignStartYear,
                    'max_year' => $this->limits->getYearMax(),
                ]);
                continue;
            }

            // Gestione Choice specifica per TRADE Grade
            if ($fieldName === 'grade') {
                $this->addIfEnabled($builder, $options, 'grade', ChoiceType::class, [
                    'required' => false,
                    'placeholder' => '// GRADE',
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
                continue;
            }

            // ENUMS (Nuova richiesta)
            if (isset(self::ENUM_FIELDS[$fieldName])) {
                $config = self::ENUM_FIELDS[$fieldName];
                $this->addIfEnabled($builder, $options, $fieldName, ChoiceType::class, [
                    'required' => false,
                    'placeholder' => '// Select ' . ucfirst($fieldName),
                    'choices' => $config['choices'],
                    'attr' => ['class' => 'select m-1 w-full'],
                ]);
                continue;
            }

            // Gestione speciale per cargoQty (può contenere unità come "tons")
            if ($fieldName === 'cargoQty') {
                $this->addIfEnabled($builder, $options, 'cargoQty', TextType::class, [
                    'required' => false,
                    'attr' => ['class' => 'input m-1 w-full'],
                ]);
                continue;
            }

            // Fallback su tipo dedotto dal nome o TextType
            $type = $this->deriveType($fieldName);

            // Override: I campi con suggerimenti (Datalist) devono essere TextType (input), non Textarea
            if (isset(self::SUGGESTION_FIELDS[$fieldName])) {
                $type = TextType::class;
            }

            $attr = ['class' => ($type === TextareaType::class ? 'textarea' : 'input') . ' m-1 w-full'];

            // SMART SUGGESTIONS
            if (isset(self::SUGGESTION_FIELDS[$fieldName])) {
                $attr['list'] = self::SUGGESTION_FIELDS[$fieldName];
            }

            $this->addIfEnabled($builder, $options, $fieldName, $type, [
                'required' => false,
                'attr' => $attr,
            ]);
        }

        // Listener per decomprimere le date Imperiali nell'array finale
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            foreach (self::DATE_FIELDS as $fieldName => $keys) {
                if ($form->has($fieldName)) {
                    /** @var ImperialDate|null $date */
                    $date = $form->get($fieldName)->getData();
                    if ($date instanceof ImperialDate) {
                        $data[$keys['day']] = $date->getDay();
                        $data[$keys['year']] = $date->getYear();
                    }
                }
            }
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // Lavoriamo con array
            'campaign_start_year' => null,
            'enabled_fields' => [],
            'field_placeholders' => [],
        ]);
    }

    /**
     * Deduce il tipo di campo Symfony dal nome della proprietà.
     */
    private function deriveType(string $fieldName): string
    {
        foreach (self::TYPE_MAPPING as $suffix => $type) {
            if (str_ends_with(strtolower($fieldName), strtolower($suffix))) {
                return $type;
            }
        }

        return TextType::class;
    }
}
