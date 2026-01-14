<?php

namespace App\Form;

use App\Entity\MortgageInstallment;
use App\Form\Config\DayYearLimits;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MortgageInstallmentType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MortgageInstallment $installments */
        $installment = $options['data'];
        $summary = $installment->getMortgage()->calculate();
        $campaignStartYear = $installment->getMortgage()?->getShip()?->getCampaign()?->getStartingYear();
        $minYear = $campaignStartYear ?? $this->limits->getYearMin();
        $paymentDate = new ImperialDate($installment?->getPaymentYear(), $installment?->getPaymentDay());

        $builder
            ->add('paymentDate', ImperialDateType::class, [
                'mapped' => false,
                'label' => 'Payment date',
                'required' => true,
                'data' => $paymentDate,
                'min_year' => $minYear,
                'max_year' => $this->limits->getYearMax(),
            ])
            ->add('payment', TravellerMoneyType::class, [
                'attr' => ['class' => 'input m-1 w-full', 'readonly' => 'readonly'],
                'data' => $summary['total_monthly_payment'],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var MortgageInstallment $installment */
            $installment = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $payment */
            $payment = $form->get('paymentDate')->getData();
            if ($payment instanceof ImperialDate) {
                $installment->setPaymentDay($payment->getDay());
                $installment->setPaymentYear($payment->getYear());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MortgageInstallment::class,
            'summary' => [],
        ]);
    }
}
