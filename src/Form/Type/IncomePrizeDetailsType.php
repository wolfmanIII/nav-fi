<?php

namespace App\Form\Type;

use App\Entity\IncomePrizeDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomePrizeDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('caseRef', TextType::class, [
                'required' => false,
                'label' => 'Case ref',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('legalBasis', ChoiceType::class, [
                'required' => false,
                'label' => 'Legal basis',
                'placeholder' => 'Select legal basis',
                'choices' => [
                    'Imperial Warrant' => 'Imperial Warrant',
                    'Port Authority Writ' => 'Port Authority Writ',
                    'Prize Court Order' => 'Prize Court Order',
                    'Letters of Marque' => 'Letters of Marque',
                    'Customs Seizure (Contraband)' => 'Customs Seizure (Contraband)',
                    'Anti-Piracy Mandate' => 'Anti-Piracy Mandate',
                    'Quarantine / Emergency Order' => 'Quarantine / Emergency Order',
                    'Search & Seizure Authorization' => 'Search & Seizure Authorization',
                    'Court-Ordered Repossession' => 'Court-Ordered Repossession',
                    'Maritime Lien (Unpaid Fees)' => 'Maritime Lien (Unpaid Fees)',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('prizeDescription', TextareaType::class, [
                'required' => false,
                'label' => 'Prize description',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('estimatedValue', NumberType::class, [
                'required' => false,
                'label' => 'Estimated value (Cr)',
                'scale' => 2,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('disposition', ChoiceType::class, [
                'required' => false,
                'label' => 'Disposition',
                'placeholder' => 'Select disposition',
                'choices' => [
                    'Returned to Owner' => 'Returned to Owner',
                    'Released (No Action)' => 'Released (No Action)',
                    'Held in Custody' => 'Held in Custody',
                    'Impounded' => 'Impounded',
                    'Forfeited to Authority' => 'Forfeited to Authority',
                    'Auctioned' => 'Auctioned',
                    'Assigned to Captor' => 'Assigned to Captor',
                    'Destroyed' => 'Destroyed',
                    'Transferred to Third Party' => 'Transferred to Third Party',
                    'Pending Adjudication' => 'Pending Adjudication',
                ],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'required' => false,
                'label' => 'Payment terms',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('shareSplit', TextareaType::class, [
                'required' => false,
                'label' => 'Share split',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
            ->add('awardTrigger', TextareaType::class, [
                'required' => false,
                'label' => 'Award trigger',
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomePrizeDetails::class,
        ]);
    }
}
