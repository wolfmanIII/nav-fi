<?php

namespace App\Form;

use App\Entity\Crew;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CrewSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('crewIds', ChoiceType::class, [
                'choices' => $options['crewToSelect'],
                'choice_value' => 'id',
                'choice_label' => fn(Crew $crew) => $crew->getName(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'crewToSelect' => null,
        ]);
    }
}
