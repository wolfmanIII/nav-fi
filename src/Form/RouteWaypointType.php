<?php

namespace App\Form;

use App\Entity\RouteWaypoint;
use App\Service\TravellerMapDataService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteWaypointType extends AbstractType
{
    public function __construct(
        private readonly TravellerMapDataService $dataService
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', HiddenType::class, [
                'required' => false,
                'empty_data' => '0',
            ])
            ->add('sector', ChoiceType::class, [
                'required' => false,
                'label' => 'Sector (OTU)',
                'placeholder' => '// SELECT SECTOR',
                'choices' => $this->dataService->getOtuSectors(),
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'searchable-select',
                    'data-searchable-select-placeholder-value' => 'Search Sector...',
                    'data-action' => 'change->dependent-select#change',
                    'data-dependent-select-target' => 'source',
                ],
            ])
            ->add('worldChoice', ChoiceType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'World Selection',
                'placeholder' => '// SELECT WORLD',
                'choices' => [],
                'disabled' => true,
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'searchable-select',
                    'data-dependent-select-target' => 'destination',
                    'data-searchable-select-placeholder-value' => 'Search World...',
                ],
            ])
            ->add('world', HiddenType::class)
            ->add('hex', HiddenType::class)
            ->add('uwp', HiddenType::class)
            ->add('jumpDistance', HiddenType::class, [
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea textarea-bordered w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 font-rajdhani', 'rows' => 2],
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var RouteWaypoint|null $waypoint */
            $waypoint = $event->getData();
            $form = $event->getForm();

            if ($waypoint && $waypoint->getSector()) {
                $form->add('worldChoice', ChoiceType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'World',
                    'placeholder' => '// SELECT WORLD',
                    'choices' => $this->dataService->getWorldsForSector($waypoint->getSector()),
                    'choice_label' => fn($choice, $key, $value) => $key,
                    'choice_value' => fn($choice) => $choice,
                    'attr' => [
                        'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                        'data-controller' => 'searchable-select',
                        'data-dependent-select-target' => 'destination',
                        'data-searchable-select-placeholder-value' => 'Search World...',
                    ],
                ]);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!empty($data['sector'])) {
                $form->add('worldChoice', ChoiceType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'World',
                    'placeholder' => '// SELECT WORLD',
                    'choices' => $this->dataService->getWorldsForSector($data['sector']),
                    'choice_label' => fn($choice, $key, $value) => $key,
                    'choice_value' => fn($choice) => $choice,
                    'attr' => [
                        'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                        'data-controller' => 'searchable-select',
                        'data-dependent-select-target' => 'destination',
                        'data-searchable-select-placeholder-value' => 'Search World...',
                    ],
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RouteWaypoint::class,
        ]);
    }
}
