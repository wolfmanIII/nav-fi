<?php

namespace App\Form\Type;

use App\Form\Data\BaseDetailsData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BaseDetailsType extends AbstractType
{
// ... (skip lines 12-73 are buildForm which are fine)

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BaseDetailsData::class,
        ]);
    }
}
