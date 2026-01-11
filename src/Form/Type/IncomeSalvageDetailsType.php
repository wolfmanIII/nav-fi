<?php

namespace App\Form\Type;

use App\Entity\IncomeSalvageDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeSalvageDetailsType extends AbstractType
{
    use ContractFieldOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addIfEnabled($builder, $options, 'caseRef', TextType::class, [
            'required' => false,
            'label' => 'Case ref',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'source', TextType::class, [
            'required' => false,
            'label' => 'Source',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'siteLocation', TextType::class, [
            'required' => false,
            'label' => 'Site location',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'recoveredItemsSummary', TextareaType::class, [
            'required' => false,
            'label' => 'Recovered items summary',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'qtyValue', NumberType::class, [
            'required' => false,
            'label' => 'Qty / Value (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'hazards', TextareaType::class, [
            'required' => false,
            'label' => 'Hazards',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'splitTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Split terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'rightsBasis', TextareaType::class, [
            'required' => false,
            'label' => 'Rights basis',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'awardTrigger', TextareaType::class, [
            'required' => false,
            'label' => 'Award trigger',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'disputeProcess', TextareaType::class, [
            'required' => false,
            'label' => 'Dispute process',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomeSalvageDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
