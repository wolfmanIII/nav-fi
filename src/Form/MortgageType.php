<?php

namespace App\Form;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Ship;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\Config\DayYearLimits;
use App\Form\Type\TravellerMoneyType;
use App\Repository\ShipRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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

        $builder
            //->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full'],])
            ->add('startDay', NumberType::class, [
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
                'disabled' => $disabled,
                ])
            ->add('startYear', NumberType::class, [
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
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
                        number_format($ship->getPrice(), 2, ',', '.') . " Cr"
                    ),
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
                'attr' => ['class' => 'select m-1 w-full'],
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
                'required' => false,
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
                'required' => false,
                'choice_label' => function (LocalLaw $l): string {
                    $label = $l->getShortDescription() ?: $l->getDescription();
                    return sprintf('%s - %s', $l->getCode(), $label);
                },
                'attr' => ['class' => 'select m-1 w-full'],
                'disabled' => $disabled,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mortgage::class,
            'user' => null,
        ]);
    }
}
