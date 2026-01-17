<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('assetSelections', CollectionType::class, [ // Renamed key to match future usages
            'entry_type'    => AssetRowType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add'     => false,
            'allow_delete'  => false,
            'label'         => false,
            'by_reference'  => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
