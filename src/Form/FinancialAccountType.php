<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\FinancialAccount;
use App\Entity\Asset;
use App\Entity\Company;
use App\Repository\CampaignRepository;
use App\Repository\AssetRepository;
use App\Repository\CompanyRepository;
use App\Entity\CompanyRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;

class FinancialAccountType extends AbstractType
{
    public function __construct(private Security $security) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        $builder
            ->add('bank', EntityType::class, [
                'class' => Company::class,
                'choice_label' => fn(Company $c) => sprintf('%s (CODE: %s)', $c->getName(), $c->getCode()),
                'label' => 'Bank // Institution',
                'required' => false,
                'placeholder' => 'Select a Bank (or enter below)',
                'query_builder' => function (CompanyRepository $cr) use ($user) {
                    return $cr->createQueryBuilder('c')
                        ->innerJoin('c.companyRole', 'r')
                        ->where('c.user = :user')
                        ->andWhere('r.code = :role')
                        ->setParameter('user', $user)
                        ->setParameter('role', CompanyRole::ROLE_BANK)
                        ->orderBy('c.name', 'ASC');
                },
            ])
            ->add('bankName', TextType::class, [
                'label' => 'Custom Bank Name (if not listed)',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. Imperial Navy Bank']
            ])
            ->add('credits', MoneyType::class, [
                'label' => 'Current Balance // CR',
                'currency' => false,
                'scale' => 0,
                'html5' => true,
                'attr' => [
                    'class' => 'text-right font-mono',
                    'step' => '1',
                ]
            ])

            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'choice_label' => 'title',
                'label' => 'Mission',
                'required' => false,
                'mapped' => false,
                'placeholder' => '// All Missions (Show All Assets)',
                'query_builder' => function (CampaignRepository $cr) use ($user) {
                    return $cr->createQueryBuilder('c')
                        ->where('c.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('c.title', 'ASC');
                },
                'help' => 'Select a mission to filter the Asset list.',
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-action' => 'change->campaign-filter#filterAssets',
                    'data-campaign-filter-target' => 'campaignSelect'
                ],
            ])
            ->add('asset', EntityType::class, [
                'class' => Asset::class,
                'choice_label' => 'name',
                'label' => 'Linked Asset // Hull',
                'required' => false,
                'placeholder' => 'None // Independent Account',
                'query_builder' => function (AssetRepository $ar) use ($user) {
                    return $ar->createQueryBuilder('a')
                        ->where('a.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('a.name', 'ASC');
                },
                'help' => 'Linking to an asset will bind this account to the asset\'s ledger.',
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-campaign-filter-target' => 'assetSelect'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FinancialAccount::class,
        ]);
    }
}
