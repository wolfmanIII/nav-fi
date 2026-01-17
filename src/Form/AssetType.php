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

class AssetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Asset $asset */
        $asset = $options['data'];
        $detailsData = AssetDetailsData::fromArray($asset->getAssetDetails() ?? []);
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Starship' => Asset::CATEGORY_SHIP,
                    'Base / Station' => Asset::CATEGORY_BASE,
                    'Team / Mercenary Unit' => Asset::CATEGORY_TEAM,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Asset Type',
                'attr' => ['class' => 'flex gap-4 mb-4'],
                'label_attr' => ['class' => 'label font-bold text-slate-400 uppercase tracking-wider text-xs'],
            ])
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('type', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('class', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
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
            ->add('assetDetails', AssetDetailsType::class, [
                'mapped' => false,
                'data' => $detailsData,
                'label' => 'Asset Details',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
