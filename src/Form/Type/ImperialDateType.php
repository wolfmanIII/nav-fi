<?php

namespace App\Form\Type;

use App\Model\ImperialDate;
use App\Validator\ImperialDateComplete;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImperialDateType extends AbstractType
{
    public function __construct(
        private readonly int $minYear,
        private readonly int $maxYear,
        private readonly int $minDay,
        private readonly int $maxDay,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ImperialDate|null $data */
        $data = ($options['data'] ?? null) instanceof ImperialDate ? $options['data'] : null;
        $initialDay = $data?->getDay();
        $initialYear = $data?->getYear();

        $displayValue = ($initialDay !== null && $initialYear !== null)
            ? sprintf('%03d/%s', $initialDay, $initialYear)
            : '';

        $builder
            ->add('display', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'data' => $displayValue,
                'attr' => [
                    'class' => 'input m-1 w-full datepicker',
                    'readonly' => true,
                    'data-imperial-date-target' => 'display',
                    'data-action' => 'click->imperial-date#toggle',
                    'data-imperial-date-initial-day' => $initialDay ?? '',
                    'data-imperial-date-initial-year' => $initialYear ?? '',
                    'placeholder' => 'Select day/year',
                ],
            ])
            ->add('year', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'data-imperial-date-target' => 'year',
                    'data-min-year' => $options['min_year'],
                    'data-max-year' => $options['max_year'],
                    'data-min-day' => $this->minDay,
                    'data-max-day' => $this->maxDay,
                ],
                'data' => $initialYear,
            ])
            ->add('day', HiddenType::class, [
                'required' => false,
                'attr' => [
                    'data-imperial-date-target' => 'day',
                ],
                'constraints' => [
                    new Range(
                        min: $this->minDay,
                        max: $this->maxDay,
                        notInRangeMessage: 'Day must be between {{ min }} and {{ max }}.',
                    ),
                ],
                'data' => $initialDay,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($initialDay, $initialYear, $displayValue): void {
            $form = $event->getForm();
            if ($initialDay !== null) {
                $form->get('day')->setData($initialDay);
            }
            if ($initialYear !== null) {
                $form->get('year')->setData($initialYear);
            }
            if ($displayValue !== '') {
                $form->get('display')->setData($displayValue);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $day = $data['day'] ?? null;
            $year = $data['year'] ?? null;

            if ($day === null || $day === '' || $day === '0') {
                $data['day'] = null;
                $data['year'] = null;
            } else {
                $data['day'] = (int) $day;
                $data['year'] = (int) $year;
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImperialDate::class,
            'min_year' => $this->minYear,
            'max_year' => $this->maxYear,
            'error_bubbling' => false,
            'error_mapping' => ['.' => 'display'],
            'constraints' => static function (Options $options): array {
                return [
                    new ImperialDateComplete(required: (bool) $options['required']),
                ];
            },
        ]);
    }
}
