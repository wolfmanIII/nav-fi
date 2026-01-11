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
    use ContractFieldOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addIfEnabled($builder, $options, 'caseRef', TextType::class, [
            'required' => false,
            'label' => 'Case ref',
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'legalBasis', ChoiceType::class, [
            'required' => false,
            'label' => 'Legal basis',
            'placeholder' => '-- Select legal basis --',
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
        ]);
        $this->addIfEnabled($builder, $options, 'prizeDescription', ChoiceType::class, [
            'required' => false,
            'label' => 'Prize description',
            'placeholder' => '-- Select prize type --',
            'choices' => [
                'Captured Cargo' => 'Captured Cargo',
                'Captured Vessel' => 'Captured Vessel',
                'Captured Vessel + Cargo' => 'Captured Vessel + Cargo',
                'Seized Contraband Shipment' => 'Seized Contraband Shipment',
                'Seized Smuggling Cache' => 'Seized Smuggling Cache',
                'Repossessed Vessel (Lien)' => 'Repossessed Vessel (Lien)',
                'Impounded Cargo Lot' => 'Impounded Cargo Lot',
                'Prize Auction Proceeds' => 'Prize Auction Proceeds',
                'Salvage Awarded as Prize' => 'Salvage Awarded as Prize',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'estimatedValue', NumberType::class, [
            'required' => false,
            'label' => 'Estimated value (Cr)',
            'scale' => 2,
            'attr' => ['class' => 'input m-1 w-full'],
        ]);
        $this->addIfEnabled($builder, $options, 'disposition', ChoiceType::class, [
            'required' => false,
            'label' => 'Disposition',
            'placeholder' => '-- Select disposition --',
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
        ]);
        $this->addIfEnabled($builder, $options, 'paymentTerms', TextareaType::class, [
            'required' => false,
            'label' => 'Payment terms',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'shareSplit', TextareaType::class, [
            'required' => false,
            'label' => 'Share split',
            'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 2],
        ]);
        $this->addIfEnabled($builder, $options, 'awardTrigger', ChoiceType::class, [
            'required' => false,
            'label' => 'Award trigger',
            'placeholder' => '-- Select trigger --',
            'choices' => [
                'Court Ruling Issued' => 'Court Ruling Issued',
                'Authority Verification Completed' => 'Authority Verification Completed',
                'Auction Settlement Received' => 'Auction Settlement Received',
                'Funds Cleared / Posted' => 'Funds Cleared / Posted',
                'Delivery Confirmed (Proof of Delivery)' => 'Delivery Confirmed (Proof of Delivery)',
                'Inspection Passed' => 'Inspection Passed',
                'Custody Transfer Signed' => 'Custody Transfer Signed',
                'Case Closed' => 'Case Closed',
                'Forfeiture Finalized' => 'Forfeiture Finalized',
            ],
            'attr' => ['class' => 'select m-1 w-full'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IncomePrizeDetails::class,
            'campaign_start_year' => null,
            'enabled_fields' => null,
            'field_placeholders' => [],
        ]);
    }
}
