<?php

namespace App\Form;

use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\LocalLaw;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

class CargoLootType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultLabelClass = 'mb-0 text-slate-300 text-[10px] uppercase tracking-widest font-bold font-orbitron opacity-50';
        $defaultInputClass = 'input input-bordered input-sm w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 text-white placeholder-slate-600';

        $builder
            ->add('title', TextType::class, [
                'label' => 'Item Name',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'class' => $defaultInputClass,
                    'placeholder' => 'e.g. Refined Crystals'
                ],
            ])
            ->add('quantity', NumberType::class, [
                'mapped' => false,
                'label' => 'Quantity (Tons/Units)',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'class' => $defaultInputClass,
                    'placeholder' => '0',
                    'min' => '1'
                ],
            ])
            ->add('unitPrice', NumberType::class, [
                'mapped' => false,
                'label' => 'Unit Est. Value (Cr)',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'class' => $defaultInputClass,
                    'placeholder' => '0.00',
                    'step' => '0.01'
                ],
            ])
            ->add('origin', TextType::class, [
                'mapped' => false, // Mappato manualmente su targetDestination o contesto
                'label' => 'Origin',
                'required' => false,
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'class' => $defaultInputClass,
                    'placeholder' => 'System/Planet'
                ],
            ])
            ->add('localLaw', EntityType::class, [
                'class' => LocalLaw::class,
                'choice_label' => function (LocalLaw $law) {
                    return $law->getShortDescription() ?? $law->getCode();
                },
                'label' => 'Local Law',
                'label_attr' => ['class' => $defaultLabelClass],
                'placeholder' => '// LOCAL LAW',
                'attr' => [
                    'class' => 'select select-bordered select-sm w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 text-white',
                ],
                'required' => true,
            ])

            ->add('note', TextareaType::class, [
                'required' => false,
                'label' => 'Notes',
                'label_attr' => ['class' => $defaultLabelClass],
                'attr' => [
                    'class' => 'textarea textarea-bordered w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 text-white placeholder-slate-600',
                    'placeholder' => 'Optional details...',
                    'rows' => 3
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Cost $cost */
            $cost = $event->getData();
            $form = $event->getForm();

            // 1. Gestione Valore Base -> Dettagli Articolo
            $quantity = (int) $form->get('quantity')->getData();
            $unitPrice = (float) $form->get('unitPrice')->getData();
            $totalEstValue = $quantity * $unitPrice;

            $cost->setDetailItems([
                [
                    'description' => 'Loot acquisition of ' . $cost->getTitle(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'base_value' => $totalEstValue,
                    'markup_estimate' => 1.0
                ]
            ]);

            // 2. Gestione Origine -> Destinazione Target
            $origin = $form->get('origin')->getData();
            if ($origin) {
                $cost->setTargetDestination($origin);
            }

            // 3. Gestione Data -> PaymentDay/Year gestiti nel Controller usando la data di sessione della campagna
            
            // 4. Forza campi specifici per il Loot
            $cost->setAmount('0.00');
            // CostCategory e FinancialAccount sono impostati nel controller o via opzioni se necessario.
            // Li impostiamo nel controller per consapevolezza del contesto rispetto al passaggio di opzioni.
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cost::class,
        ]);
    }
}
