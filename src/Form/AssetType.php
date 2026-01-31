<?php

namespace App\Form;

use App\Dto\AssetDetailsData;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Entity\FinancialAccount;
use App\Form\Type\TravellerMoneyType;
use App\Repository\CampaignRepository;
use App\Repository\CompanyRepository;
use App\Repository\FinancialAccountRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
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
                'label' => 'Mission',
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
            ->add('financialAccount', EntityType::class, [
                'class' => FinancialAccount::class,
                'choice_label' => fn(FinancialAccount $fa) => $fa->getDisplayName(),
                'label' => 'Link Existing Ledger',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// ACCOUNT',
                'data' => $asset->getFinancialAccount(),
                'query_builder' => function (FinancialAccountRepository $repo) use ($asset) {
                    $qb = $repo->createQueryBuilder('fa')
                        ->where('fa.user = :user')
                        ->setParameter('user', $this->security->getUser())
                        ->orderBy('fa.id', 'DESC');
                    if ($asset->getFinancialAccount()) {
                        $qb->andWhere('fa.id != :current OR fa.id = :current')
                            ->setParameter('current', $asset->getFinancialAccount()->getId());
                    }
                    return $qb;
                },
                'help' => 'Optional. Select an existing Financial Account to link. If none selected, use the fields below to open a new one.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('bank', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'label' => 'New Account // Banking Institution',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// BANK',
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
                'help' => 'To create a NEW Financial Account, select a registered banking institution here OR enter a custom name below.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'New Account // Custom Institution Name',
                'required' => false,
                'mapped' => false,
                'data' => $asset->getFinancialAccount()?->getBankName(),
                'help' => 'Enter a custom institution name if not listed above. A new banking entity will be registered automatically.',
                'help_attr' => ['class' => 'text-xs text-slate-500 ml-1'],
                'attr' => ['class' => 'input m-1 w-full', 'placeholder' => 'e.g. Spinward Marches Credit Union'],
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
