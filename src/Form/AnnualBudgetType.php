<?php

namespace App\Form;

use App\Entity\AnnualBudget;
use App\Entity\Ship;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Model\ImperialDate;
use App\Repository\ShipRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Entity\Campaign;

class AnnualBudgetType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var AnnualBudget $budget */
        $budget = $builder->getData();
        $campaignStartYear = $budget?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        $startDate = new ImperialDate($budget?->getStartYear(), $budget?->getStartDay());
        $endDate = new ImperialDate($budget?->getEndYear(), $budget?->getEndDay());

        $builder
            ->add('startDate', ImperialDateType::class, [
                'mapped' => false,
                'label' => 'Start date',
                'required' => true,
                'data' => $startDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('endDate', ImperialDateType::class, [
                'mapped' => false,
                'label' => 'End date',
                'required' => true,
                'data' => $endDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => true,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => sprintf('%s (%03d/%04d)', $campaign->getTitle(), $campaign->getSessionDay(), $campaign->getSessionYear()),
                'data' => $budget->getShip()?->getCampaign(),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-campaign-ship-target' => 'campaign',
                    'data-action' => 'change->campaign-ship#onCampaignChange',
                ],
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'placeholder' => '-- Select a Ship --',
                'choice_label' => fn(Ship $ship) => sprintf('%s (%s)', $ship->getName(), $ship->getClass()),
                'choice_attr' => function (Ship $ship): array {
                    $start = $ship->getCampaign()?->getStartingYear();
                    $campaignId = $ship->getCampaign()?->getId();
                    return [
                        'data-start-year' => $start ?? '',
                        'data-campaign' => $campaignId ? (string) $campaignId : '',
                    ];
                },
                'query_builder' => function (ShipRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                    'data-campaign-ship-target' => 'ship',
                ],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var AnnualBudget $budget */
            $budget = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $start */
            $start = $form->get('startDate')->getData();
            if ($start instanceof ImperialDate) {
                $budget->setStartDay($start->getDay());
                $budget->setStartYear($start->getYear());
            }

            /** @var ImperialDate|null $end */
            $end = $form->get('endDate')->getData();
            if ($end instanceof ImperialDate) {
                $budget->setEndDay($end->getDay());
                $budget->setEndYear($end->getYear());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnnualBudget::class,
            'user' => null,
            'error_mapping' => [
                'endYear' => 'endDate',
                'endDay' => 'endDate',
            ],
        ]);
    }
}
