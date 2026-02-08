<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Route;
use App\Entity\Asset;
use App\Form\Config\DayYearLimits;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteType extends AbstractType
{
    public function __construct(
        private readonly DayYearLimits $limits
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Route $route */
        $route = $builder->getData();

        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('campaign', EntityType::class, [
                'label' => 'Mission',
                'class' => Campaign::class,
                'required' => true,
                'placeholder' => '// MISSION',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $route->getCampaign() ?? $route->getAsset()?->getCampaign(),
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
                'class' => Asset::class,
                'placeholder' => '// ASSET',
                'choice_label' => fn(Asset $asset) => sprintf('%s - %s(%s) [CODE: %s]', $asset->getName(), $asset->getType(), $asset->getClass(), substr($asset->getFinancialAccount()?->getCode() ?? 'N/A', 0, 8)),
                'choice_attr' => function (Asset $asset): array {
                    $start = $asset->getCampaign()?->getStartingYear();
                    $session = $asset->getCampaign()?->getSessionYear();
                    $campaignId = $asset->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-session-year' => $session ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (AssetRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('a')->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('a.user = :user')->setParameter('user', $user);
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
            ->add('startHex', HiddenType::class, [
                'required' => false,
            ])
            ->add('destHex', HiddenType::class, [
                'required' => false,
            ])
            ->add('startDate', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Start Date',
                'data' => $route->getStartDateImperial(),
                'disabled' => true,
                'attr' => [
                    'class' => 'input input-bordered w-full bg-slate-950/50 border-slate-700 font-mono text-cyan-400 cursor-not-allowed',
                ],
            ])
            ->add('destDate', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Destination Date',
                'data' => $route->getDestDateImperial(),
                'disabled' => true,
                'attr' => [
                    'class' => 'input input-bordered w-full bg-slate-950/50 border-slate-700 font-mono text-cyan-400 cursor-not-allowed',
                ],
            ])
            ->add('jumpRating', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('fuelEstimate', TextType::class, [
                'label' => 'Fuel Estimate (per jump)',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Route::class,
            'user' => null,
        ]);
    }
}
