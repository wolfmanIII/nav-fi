<?php

namespace App\Form;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MortgageType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Mortgage $mortgage */
        $mortgage = $options['data'];
        $user = $options['user'];
        $currentAssetId = $mortgage->getAsset()?->getId();
        $builder
            //->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full'],])

            ->add('assetShares', IntegerType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('advancePayment', TravellerMoneyType::class, [
                'label' => 'Deposit(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('discount', IntegerType::class, [
                'label' => 'Discount(%)',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => true,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $mortgage->getAsset()?->getCampaign(),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'campaign',
                    'data-action' => 'change->campaign-asset#onCampaignChange',
                ],
            ])
            ->add('asset', EntityType::class, [
                'placeholder' => '-- Select an Asset --',
                'class' => Asset::class,
                'choice_label' => fn(Asset $asset) =>
                sprintf(
                    '%s - %s - %s',
                    $asset->getName(),
                    $asset->getType(),
                    number_format((float) $asset->getPrice(), 2, ',', '.') . " Cr"
                ),
                'choice_attr' => function (Asset $asset): array {
                    $start = $asset->getCampaign()?->getStartingYear();
                    $campaignId = $asset->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (AssetRepository $repo) use ($user, $currentAssetId) {
                    $qb = $repo->createQueryBuilder('s')
                        ->leftJoin('s.mortgage', 'm')
                        ->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    if ($currentAssetId) {
                        $qb->andWhere('(m.id IS NULL OR s.id = :currentAsset)')
                            ->setParameter('currentAsset', $currentAssetId);
                    } else {
                        $qb->andWhere('m.id IS NULL');
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-asset-target' => 'asset',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange',
                ],
            ])
            ->add('interestRate', EntityType::class, [
                'class' => InterestRate::class,
                'choice_label' => fn(InterestRate $rate) =>
                sprintf(
                    '%d years – x%s / %s – %s%%',
                    $rate->getDuration(),
                    $rate->getPriceMultiplier(),
                    $rate->getPriceDivider(),
                    $rate->getAnnualInterestRate()
                ),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('insurance', EntityType::class, [
                'class' => Insurance::class,
                'choice_label' => fn(Insurance $insurance) =>
                sprintf(
                    '%s - %d%% Asset Price',
                    $insurance->getName(),
                    $insurance->getAnnualCost(),
                ),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'placeholder' => '-- Select a Company --',
                'required' => true,
                'choice_label' => fn(Company $c) => sprintf('%s - %s', $c->getName(), $c->getCompanyRole()->getShortDescription()),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('localLaw', EntityType::class, [
                'class' => LocalLaw::class,
                'placeholder' => '-- Select a Local Law --',
                'required' => true,
                'choice_label' => function (LocalLaw $l): string {
                    $label = $l->getShortDescription() ?: $l->getDescription();
                    return sprintf('%s - %s', $l->getCode(), $label);
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Mortgage $mortgage */
            $mortgage = $event->getData();
            $form = $event->getForm();
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mortgage::class,
            'user' => null,
        ]);
    }
}
