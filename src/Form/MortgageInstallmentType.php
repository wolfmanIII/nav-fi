<?php

namespace App\Form;

use App\Entity\MortgageInstallment;
use App\Form\Config\DayYearLimits;
use App\Form\Type\TravellerMoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MortgageInstallmentType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MortgageInstallment $installments */
        $installment = $options['data'];
        $summary = $installment->getMortgage()->calculate();
        $campaignStartYear = $installment->getMortgage()?->getShip()?->getCampaign()?->getStartingYear();

        $builder
            ->add('paymentDay', NumberType::class, [
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('paymentYear', NumberType::class, [
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full'], $campaignStartYear),
            ])
            ->add('payment', TravellerMoneyType::class, [
                'attr' => ['class' => 'input m-1 w-full', 'readonly' => 'readonly'],
                'data' => $summary['total_monthly_payment'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MortgageInstallment::class,
            'summary' => [],
        ]);
    }
}
