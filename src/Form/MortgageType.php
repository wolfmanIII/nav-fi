<?php

namespace App\Form;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Ship;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\ShipRepository;
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
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Mortgage $mortgage */
        $mortgage = $options['data'];
        $disabled = $mortgage->isSigned();
        $user = $options['user'];
        $currentShipId = $mortgage->getShip()?->getId();
        $campaignStartYear = $mortgage->getShip()?->getCampaign()?->getStartingYear();
        $minYear = max($this->limits->getYearMin(), $campaignStartYear ?? $this->limits->getYearMin());
        $startDate = new ImperialDate($mortgage?->getStartYear(), $mortgage?->getStartDay());

        $builder
            //->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full'],])
            ->add('startDate', ImperialDateType::class, [
                'mapped' => false,
                'label' => 'Start date',
                'required' => true,
                'data' => $startDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
                'attr' => ['class' => $disabled ? 'pointer-events-none opacity-60' : ''],
                'disabled' => $disabled,
            ])
            ->add('shipShares', IntegerType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
                'disabled' => $disabled,
                'required' => false,
            ])
            ->add('advancePayment', TravellerMoneyType::class, [
                'label' => 'Advance Payment(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
                'required' => false,
                'disabled' => $disabled,
            ])
            ->add('discount', IntegerType::class, [
                'label' => 'Discount(%)',
                'attr' => ['class' => 'input m-1 w-full'], 'required' => false,
                'disabled' => $disabled,
            ])
            ->add('ship', EntityType::class, [
                'placeholder' => '-- Select a Ship --',
                'class' => Ship::class,
                'choice_label' => fn (Ship $ship) =>
                    sprintf('%s - %s - %s',
                        $ship->getName(),
                        $ship->getType(),
                        number_format((float) $ship->getPrice(), 2, ',', '.') . " Cr"
                    ),
                'choice_attr' => function (Ship $ship): array {
                    $start = $ship->getCampaign()?->getStartingYear();
                    return ['data-start-year' => $start ?? ''];
                },
                'query_builder' => function (ShipRepository $repo) use ($user, $currentShipId) {
                    $qb = $repo->createQueryBuilder('s')
                        ->leftJoin('s.mortgage', 'm')
                        ->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    if ($currentShipId) {
                        $qb->andWhere('(m.id IS NULL OR s.id = :currentShip)')
                            ->setParameter('currentShip', $currentShipId);
                    } else {
                        $qb->andWhere('m.id IS NULL');
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-controller' => 'year-limit',
                    'data-year-limit-default-value' => $this->limits->getYearMin(),
                    'data-action' => 'change->year-limit#onShipChange',
                ],
                'disabled' => $disabled,
            ])
            ->add('interestRate', EntityType::class, [
                'class' => InterestRate::class,
                'choice_label' => fn (InterestRate $rate) =>
                    sprintf('%d years – x%s / %s – %s%%',
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
                'choice_label' => fn (Insurance $insurance) =>
                    sprintf('%s - %d%% Ship Price',
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
                'choice_label' => fn (Company $c) => sprintf('%s - %s', $c->getName(), $c->getCode()),
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => ['class' => 'select m-1 w-full'],
                'disabled' => $disabled,
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
                'disabled' => $disabled,
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Mortgage $mortgage */
            $mortgage = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $start */
            $start = $form->get('startDate')->getData();
            if ($start instanceof ImperialDate) {
                $mortgage->setStartDay($start->getDay());
                $mortgage->setStartYear($start->getYear());
            }
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
