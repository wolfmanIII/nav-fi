<?php

namespace App\Form;

use App\Entity\LocalLaw;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CargoLiquidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultLabelClass = 'mb-0 text-slate-300 text-[10px] uppercase tracking-widest font-bold font-orbitron opacity-50';
        $defaultInputClass = 'input input-bordered input-sm w-full bg-slate-950/50 border-slate-700 focus:border-cyan-500/50 text-white placeholder-slate-600';

        $builder
            ->add('location', TextType::class, [
                'label' => 'Location',
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null, // Array o semplice DTO
        ]);
    }
}
