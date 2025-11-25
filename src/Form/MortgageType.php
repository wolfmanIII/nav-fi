<?php

namespace App\Form;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\Mortgage;
use App\Entity\Ship;
use App\Form\Type\TravellerMoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MortgageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            //->add('name', TextType::class, ['attr' => ['class' => 'input m-1 w-full'],])
            ->add('startDay', NumberType::class, ['attr' => ['class' => 'input m-1 w-full'],])
            ->add('startYear', NumberType::class, ['attr' => ['class' => 'input m-1 w-full'],])
            ->add('shipShares', NumberType::class, ['attr' => ['class' => 'input m-1 w-full'], 'required' => false])
            ->add('advancePayment', TravellerMoneyType::class, [
                'label' => 'Advance Payment(Cr)',
                'attr' => ['class' => 'input m-1 w-full'], 'required' => false
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Discount(%)',
                'attr' => ['class' => 'input m-1 w-full'], 'required' => false
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
                'attr' => ['class' => 'select m-1 w-full'],
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mortgage::class,
        ]);
    }
}
