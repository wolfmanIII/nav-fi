<?php

namespace App\Form;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Ship;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\EventSubscriber\IncomeDetailsSubscriber;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\ShipRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Campaign;

class IncomeType extends AbstractType
{
    public function __construct(
        private readonly IncomeDetailsSubscriber $incomeDetailsSubscriber,
        private readonly DayYearLimits $dayYearLimits,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Income $income */
        $income = $builder->getData();
        $campaignStartYear = $income?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = $campaignStartYear ?? $this->dayYearLimits->getYearMin();

        $signingDate = new ImperialDate($income?->getSigningYear(), $income?->getSigningDay());
        $paymentDate = new ImperialDate($income?->getPaymentYear(), $income?->getPaymentDay());
        $expirationDate = new ImperialDate($income?->getExpirationYear(), $income?->getExpirationDay());
        $cancelDate = new ImperialDate($income?->getCancelYear(), $income?->getCancelDay());

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signingDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Signing date',
                'data' => $signingDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('signingLocation', TextType::class, [
                'required' => true,
                'label' => 'Signing Location',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Payment date',
                'data' => $paymentDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('expirationDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Expiration date',
                'data' => $expirationDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('cancelDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Cancel date',
                'data' => $cancelDate,
                'min_year' => $minYear,
                'max_year' => $this->dayYearLimits->getYearMax(),
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incomeCategory', EntityType::class, [
                'class' => IncomeCategory::class,
                'placeholder' => '-- Select a Category --',
                'choice_label' => fn(IncomeCategory $cat) => sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'income-details',
                    'data-action' => 'change->income-details#change',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    Income::STATUS_DRAFT => Income::STATUS_DRAFT,
                    Income::STATUS_SIGNED => Income::STATUS_SIGNED,
                ],
                'required' => false,
                'disabled' => true,
                'data' => $income?->getStatus() ?? Income::STATUS_DRAFT,
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => '-- Select a Campaign --',
                'choice_label' => fn(Campaign $campaign) => $campaign->getTitle(),
                'data' => $income->getShip()?->getCampaign(),
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
                'required' => false,
                'choice_label' => fn(Ship $ship) => sprintf('%s - %s(%s)', $ship->getName(), $ship->getType(), $ship->getClass()),
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
                    'data-controller' => 'income-details year-limit',
                    'data-year-limit-default-value' => $this->dayYearLimits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                    'data-campaign-ship-target' => 'ship',
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
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Income $income */
            $income = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $signing */
            $signing = $form->get('signingDate')->getData();
            if ($signing instanceof ImperialDate) {
                $income->setSigningDay($signing->getDay());
                $income->setSigningYear($signing->getYear());
            }
            if ($signing instanceof ImperialDate && $signing->getDay() !== null && $signing->getYear() !== null) {
                $income->setStatus(Income::STATUS_SIGNED);
            } else {
                $income->setStatus(Income::STATUS_DRAFT);
            }

            /** @var ImperialDate|null $payment */
            $payment = $form->get('paymentDate')->getData();
            if ($payment instanceof ImperialDate) {
                $income->setPaymentDay($payment->getDay());
                $income->setPaymentYear($payment->getYear());
            }

            /** @var ImperialDate|null $expiration */
            $expiration = $form->get('expirationDate')->getData();
            if ($expiration instanceof ImperialDate) {
                $income->setExpirationDay($expiration->getDay());
                $income->setExpirationYear($expiration->getYear());
            }

            /** @var ImperialDate|null $cancel */
            $cancel = $form->get('cancelDate')->getData();
            if ($cancel instanceof ImperialDate) {
                $income->setCancelDay($cancel->getDay());
                $income->setCancelYear($cancel->getYear());
            }
        });

        $builder->addEventSubscriber($this->incomeDetailsSubscriber);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Income::class,
            'user' => null,
        ]);
    }
}
