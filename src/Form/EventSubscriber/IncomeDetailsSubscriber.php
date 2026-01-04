<?php

namespace App\Form\EventSubscriber;

use App\Entity\Income;
use App\Entity\IncomeCharterDetails;
use App\Entity\IncomeContractDetails;
use App\Entity\IncomeFreightDetails;
use App\Entity\IncomeInsuranceDetails;
use App\Entity\IncomeInterestDetails;
use App\Entity\IncomeMailDetails;
use App\Entity\IncomePassengersDetails;
use App\Entity\IncomePrizeDetails;
use App\Entity\IncomeSalvageDetails;
use App\Entity\IncomeServicesDetails;
use App\Entity\IncomeSubsidyDetails;
use App\Entity\IncomeTradeDetails;
use App\Form\Type\IncomeCharterDetailsType;
use App\Form\Type\IncomeContractDetailsType;
use App\Form\Type\IncomeFreightDetailsType;
use App\Form\Type\IncomeInsuranceDetailsType;
use App\Form\Type\IncomeInterestDetailsType;
use App\Form\Type\IncomeMailDetailsType;
use App\Form\Type\IncomePassengersDetailsType;
use App\Form\Type\IncomePrizeDetailsType;
use App\Form\Type\IncomeSalvageDetailsType;
use App\Form\Type\IncomeServicesDetailsType;
use App\Form\Type\IncomeSubsidyDetailsType;
use App\Form\Type\IncomeTradeDetailsType;
use App\Repository\IncomeCategoryRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class IncomeDetailsSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, array{property: string, type: string, class: string}>
     */
    private const DETAIL_FORMS = [
        'CHARTER' => ['property' => 'charterDetails', 'type' => IncomeCharterDetailsType::class, 'class' => IncomeCharterDetails::class],
        'SUBSIDY' => ['property' => 'subsidyDetails', 'type' => IncomeSubsidyDetailsType::class, 'class' => IncomeSubsidyDetails::class],
        'FREIGHT' => ['property' => 'freightDetails', 'type' => IncomeFreightDetailsType::class, 'class' => IncomeFreightDetails::class],
        'PASSENGERS' => ['property' => 'passengersDetails', 'type' => IncomePassengersDetailsType::class, 'class' => IncomePassengersDetails::class],
        'SERVICES' => ['property' => 'servicesDetails', 'type' => IncomeServicesDetailsType::class, 'class' => IncomeServicesDetails::class],
        'INSURANCE' => ['property' => 'insuranceDetails', 'type' => IncomeInsuranceDetailsType::class, 'class' => IncomeInsuranceDetails::class],
        'MAIL' => ['property' => 'mailDetails', 'type' => IncomeMailDetailsType::class, 'class' => IncomeMailDetails::class],
        'INTEREST' => ['property' => 'interestDetails', 'type' => IncomeInterestDetailsType::class, 'class' => IncomeInterestDetails::class],
        'TRADE' => ['property' => 'tradeDetails', 'type' => IncomeTradeDetailsType::class, 'class' => IncomeTradeDetails::class],
        'SALVAGE' => ['property' => 'salvageDetails', 'type' => IncomeSalvageDetailsType::class, 'class' => IncomeSalvageDetails::class],
        'PRIZE' => ['property' => 'prizeDetails', 'type' => IncomePrizeDetailsType::class, 'class' => IncomePrizeDetails::class],
        'CONTRACT' => ['property' => 'contractDetails', 'type' => IncomeContractDetailsType::class, 'class' => IncomeContractDetails::class],
    ];

    public function __construct(
        private readonly IncomeCategoryRepository $incomeCategoryRepository,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        $income = $event->getData();
        if (!$income instanceof Income) {
            return;
        }

        $code = $income->getIncomeCategory()?->getCode();
        if ($code === null) {
            return;
        }

        $this->ensureDetailInstance($income, $code);
        $campaignStartYear = $income->getShip()?->getCampaign()?->getStartingYear();
        $this->addDetailField($event->getForm(), $code, $campaignStartYear);
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!is_array($data)) {
            return;
        }

        $code = $this->resolveCategoryCode($data['incomeCategory'] ?? null);
        if ($code === null) {
            return;
        }

        $income = $event->getForm()->getData();
        $campaignStartYear = null;
        if ($income instanceof Income) {
            $this->ensureDetailInstance($income, $code);
            $campaignStartYear = $income->getShip()?->getCampaign()?->getStartingYear();
        }

        $this->addDetailField($event->getForm(), $code, $campaignStartYear);
    }

    private function resolveCategoryCode(null|string|int $categoryId): ?string
    {
        if (empty($categoryId)) {
            return null;
        }

        $category = $this->incomeCategoryRepository->find($categoryId);

        return $category?->getCode();
    }

    private function ensureDetailInstance(Income $income, string $code): void
    {
        if (!isset(self::DETAIL_FORMS[$code])) {
            return;
        }

        $config = self::DETAIL_FORMS[$code];
        $property = $config['property'];
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);

        if (!method_exists($income, $getter) || !method_exists($income, $setter)) {
            return;
        }

        $detail = $income->$getter();
        if (!$detail) {
            $class = $config['class'];
            $detail = new $class();
            if (method_exists($detail, 'setIncome')) {
                $detail->setIncome($income);
            }
            $income->$setter($detail);
            return;
        }

        if (method_exists($detail, 'setIncome') && $detail->getIncome() !== $income) {
            $detail->setIncome($income);
        }
    }

    private function addDetailField(FormInterface $form, string $code, ?int $campaignStartYear): void
    {
        if (!isset(self::DETAIL_FORMS[$code])) {
            return;
        }

        $config = self::DETAIL_FORMS[$code];
        if ($form->has($config['property'])) {
            return;
        }

        $form->add($config['property'], $config['type'], [
            'required' => false,
            'label' => false,
            'campaign_start_year' => $campaignStartYear,
        ]);
    }
}
