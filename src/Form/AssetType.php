<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Asset;
use App\Dto\AssetDetailsData;
use App\Repository\CampaignRepository;
use App\Form\AssetDetailsType;
use App\Form\Type\TravellerMoneyType;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Repository\CompanyRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AssetType extends AbstractType
{
    public function __construct(private Security $security) {}

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
                'placeholder' => '// MISSION',
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
                'mapped' => false,
                'data' => $asset->getFinancialAccount()?->getCredits(),
                'help' => 'Required. The initial balance of the linked Financial Account.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'constraints' => [
                    new NotBlank(['message' => 'Please provide the initial balance.']),
                ],
            ])
            ->add('bank', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'label' => 'Opening Bank // Institution',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select a Bank (or enter custom name below)',
                'data' => $asset->getFinancialAccount()?->getBank(),
                'query_builder' => function (CompanyRepository $cr) {
                    return $cr->createQueryBuilder('c')
                        ->innerJoin('c.companyRole', 'r')
                        ->where('c.user = :user')
                        ->andWhere('r.code = :role')
                        ->setParameter('user', $this->security->getUser())
                        ->setParameter('role', CompanyRole::ROLE_BANK)
                        ->orderBy('c.name', 'ASC');
                },
                'help' => 'Select an existing institution or provide a custom name below.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'Custom Bank Name (fallback)',
                'required' => false,
                'mapped' => false,
                'data' => $asset->getFinancialAccount()?->getBankName(),
                'help' => 'Mandatory if no institution is selected.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Imperial Navy Bank'],
            ]);

        // Cross-field validation: at least bank or bankName must be filled
        $builder->addEventListener(\Symfony\Component\Form\FormEvents::POST_SUBMIT, function (\Symfony\Component\Form\FormEvent $event) {
            $form = $event->getForm();
            $bank = $form->get('bank')->getData();
            $bankName = $form->get('bankName')->getData();

            if (!$bank && !$bankName) {
                $form->get('bank')->addError(new \Symfony\Component\Form\FormError('You must select a bank or provide a custom name.'));
                $form->get('bankName')->addError(new \Symfony\Component\Form\FormError('You must provide a custom name if no bank is selected.'));
            }
        });

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
            $baseData = \App\Form\Data\BaseDetailsData::fromArray($asset->getAssetDetails() ?? []);

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
