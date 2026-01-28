<?php

namespace App\Form;

use App\Entity\Cost;
use App\Entity\CostCategory;
use App\Entity\Asset;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\Config\DayYearLimits;
use App\Form\CostDetailItemType;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\AssetRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use App\Entity\Campaign;
use App\Entity\FinancialAccount;
use App\Repository\FinancialAccountRepository;

class CostType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Cost $cost */
        $cost = $builder->getData();
        $asset = $cost?->getFinancialAccount()?->getAsset();
        $campaignStartYear = $asset?->getCampaign()?->getStartingYear();
        $paymentDate = new ImperialDate($cost?->getPaymentYear(), $cost?->getPaymentDay());
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full', 'readonly' => true],
                //'empty_data' => '0.00',
            ])
            ->add('detailItems', CollectionType::class, [
                'entry_type' => CostDetailItemType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => false,
                'prototype' => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Add at least one cost detail'),
                ],
                'error_bubbling' => false,
            ])
            ->add('paymentDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Payment date',
                'data' => $paymentDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('costCategory', EntityType::class, [
                'class' => CostCategory::class,
                'placeholder' => '-- Select a Category --',
                'choice_label' => fn(CostCategory $cat) =>
                sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cc')
                        ->orderBy('cc.code', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => true,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $asset?->getCampaign(),
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
            ->add('financialAccount', EntityType::class, [
                'class' => FinancialAccount::class,
                'placeholder' => '-- Select a Financial Account --',
                'choice_label' => fn(FinancialAccount $fa) => $fa->getDisplayName(),
                'choice_attr' => function (FinancialAccount $fa): array {
                    $asset = $fa->getAsset();
                    if (!$asset) return [];
                    $start = $asset->getCampaign()?->getStartingYear();
                    $session = $asset->getCampaign()?->getSessionYear();
                    $campaignId = $asset->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-session-year' => $session ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (FinancialAccountRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('fa')
                        ->leftJoin('fa.asset', 'a')
                        ->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('fa.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onAssetChange',
                    'data-campaign-asset-target' => 'asset',
                ],
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
            ->add('note', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
            ->add('targetDestination', TextType::class, [
                'required' => false,
                'label' => 'Target Trade Destination (Optional)',
                'attr' => [
                    'class' => 'input m-1 w-full',
                    'placeholder' => 'e.g. Sol System, Station X... (Used for Trade Goods)'
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Cost $cost */
            $cost = $event->getData();
            $form = $event->getForm();

            $date = $form->get('paymentDate')->getData();
            if ($date instanceof \App\Model\ImperialDate) {
                $cost->setPaymentDay($date->getDay());
                $cost->setPaymentYear($date->getYear());
            }



            // Calcolo server-side dell'importo totale dai detailItems per evitare valori null.
            $details = $cost->getDetailItems() ?? [];
            $validDetails = [];
            $total = 0.0;
            foreach ($details as $idx => $item) {
                $description = isset($item['description']) ? trim((string) $item['description']) : '';
                $qtyRaw = $item['quantity'] ?? null;
                $costRaw = $item['cost'] ?? null;
                $hasAny = $description !== '' || $qtyRaw !== null && $qtyRaw !== '' || $costRaw !== null && $costRaw !== '';
                if (!$hasAny) {
                    continue; // riga vuota: ignorata
                }
                $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;
                $lineCost = is_numeric($costRaw) ? (float) $costRaw : null;
                if ($description === '' || $qty === null || $lineCost === null) {
                    $form->get('detailItems')->addError(new FormError('Each detail needs description, quantity and cost.'));
                    continue;
                }
                $validDetails[] = [
                    'description' => $description,
                    'quantity' => $qty,
                    'cost' => $lineCost,
                    'isSold' => $item['isSold'] ?? false, // Preserva lo stato di vendita
                ];
                $total += $qty * $lineCost;
            }
            if (count($validDetails) < 1) {
                $form->get('detailItems')->addError(new FormError('Add at least one cost detail'));
            }
            $cost->setDetailItems($validDetails);
            $cost->setAmount(sprintf('%.2f', $total));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cost::class,
            'user' => null,
        ]);
    }
}
