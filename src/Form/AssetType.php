<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Asset;
use App\Dto\AssetDetailsData;
use App\Repository\CampaignRepository;
use App\Form\AssetDetailsType;
use App\Form\Type\TravellerMoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AssetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Asset $asset */
        $asset = $options['data'];
        
        // Usa form strutturato per Navi e Basi (Stazioni)
        $isStructured = in_array($asset->getCategory(), [Asset::CATEGORY_SHIP, Asset::CATEGORY_BASE]);

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ]);

        if ($asset->getCategory() !== Asset::CATEGORY_TEAM) {
            $builder
                ->add('type', TextType::class, [
                    'attr' => ['class' => 'input m-1 w-full'],
                    'constraints' => [
                        new NotBlank(['message' => 'Please provide an asset type.']),
                    ],
                ])
                ->add('class', TextType::class, [
                    'attr' => ['class' => 'input m-1 w-full'],
                    'constraints' => [
                        new NotBlank(['message' => 'Please provide an asset class.']),
                    ],
                ])
            ;
        }

        $builder
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'placeholder' => '-- Select a Mission --',
                'required' => false,
                'choice_label' => fn(Campaign $c) => sprintf('%s (%03d/%04d)', $c->getTitle(), $c->getSessionDay(), $c->getSessionYear()),
                'query_builder' => function (CampaignRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('price', TravellerMoneyType::class, [
                'label' => 'Price(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('credits', TravellerMoneyType::class, [
                'label' => 'Initial Credits',
                'attr' => ['class' => 'input m-1 w-full'],
            ]);
            
        if ($asset->getCategory() === Asset::CATEGORY_SHIP) {
            // Usa il nuovo DTO ShipDetailsData
            $shipData = \App\Form\Data\ShipDetailsData::fromArray($asset->getAssetDetails() ?? []);
            
            $builder->add('shipDetails', \App\Form\Type\ShipDetailsType::class, [
                'mapped' => false,
                'data' => $shipData,
                'label' => 'Specifications',
            ]);
        } elseif ($asset->getCategory() === Asset::CATEGORY_BASE) {
            // Usa BaseDetailsType per stazioni
            $baseData = \App\Form\Data\ShipDetailsData::fromArray($asset->getAssetDetails() ?? []);
            
            $builder->add('baseDetails', \App\Form\Type\BaseDetailsType::class, [
                'mapped' => false,
                'data' => $baseData,
                'label' => 'Station Specifications',
            ]);
        } else {
            // Fallback al vecchio sistema generico per team
            $detailsData = AssetDetailsData::fromArray($asset->getAssetDetails() ?? []);
            $builder->add('assetDetails', AssetDetailsType::class, [
                'mapped' => false,
                'data' => $detailsData,
                'label' => 'Asset Details',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
