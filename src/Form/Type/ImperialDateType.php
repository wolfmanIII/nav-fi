<?php

namespace App\Form\Type;

use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImperialDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ImperialDate|null $data */
        $data = $builder->getData();
        $initialDay = $data?->getDay();
        $initialYear = $data?->getYear();

        $builder
            ->add('display', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
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
                'required' => true,
                'attr' => [
                    'data-imperial-date-target' => 'year',
                    'data-min-year' => $options['min_year'],
                    'data-max-year' => $options['max_year'],
                ],
            ])
            ->add('day', HiddenType::class, [
                'required' => true,
                'attr' => [
                    'data-imperial-date-target' => 'day',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $day = $data['day'] ?? null;
            $year = $data['year'] ?? null;

            if ($day !== null) {
                $event->getForm()->get('day')->setData((int) $day);
            }

            if ($year !== null) {
                $event->getForm()->get('year')->setData((int) $year);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var ImperialDate|null $date */
            $date = $event->getData();
            if (!$date) {
                return;
            }

            $event->getForm()->get('day')->setData($date->getDay());
            $event->getForm()->get('year')->setData($date->getYear());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImperialDate::class,
            'min_year' => 1105,
            'max_year' => 9999,
            'attr' => [
                'data-controller' => 'imperial-date',
            ],
        ]);
    }
}
