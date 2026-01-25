<?php

namespace App\Form\EventSubscriber;

use App\Entity\Income;
use App\Entity\Cost;
use App\Form\Type\IncomeDetailsType;
use App\Repository\IncomeCategoryRepository;
use App\Repository\CostRepository;
use App\Service\ContractFieldConfig;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class IncomeDetailsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IncomeCategoryRepository $incomeCategoryRepository,
        private readonly ContractFieldConfig $contractFieldConfig,
    ) {}

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

        $campaignStartYear = $income->getAsset()?->getCampaign()?->getStartingYear();
        $this->addDetailField($event->getForm(), $code, $campaignStartYear, $income);
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
            $campaignStartYear = $income->getAsset()?->getCampaign()?->getStartingYear();
        }

        $this->addDetailField($event->getForm(), $code, $campaignStartYear, $income instanceof Income ? $income : null);
    }

    private function resolveCategoryCode(null|string|int $categoryId): ?string
    {
        if (empty($categoryId)) {
            return null;
        }

        $category = $this->incomeCategoryRepository->find($categoryId);

        return $category?->getCode();
    }

    /**
     * Aggiunge il campo 'details' alla form utilizzando il tipo dinamico IncomeDetailsType.
     * Gestisce anche il campo speciale 'purchaseCost' per la categoria TRADE.
     */
    private function addDetailField(FormInterface $form, string $code, ?int $campaignStartYear, ?Income $income): void
    {
        // Pulizia campi precedenti per evitare conflitti durante il cambio categoria AJAX
        if ($form->has('details')) {
            $form->remove('details');
        }
        if ($form->has('purchaseCost')) {
            $form->remove('purchaseCost');
        }

        // Aggiunta del blocco JSON dinamico
        $form->add('details', IncomeDetailsType::class, [
            'required' => false,
            'label' => false,
            'campaign_start_year' => $campaignStartYear,
            'enabled_fields' => $this->contractFieldConfig->getOptionalFields($code),
            'field_placeholders' => $this->contractFieldConfig->getPlaceholders($code),
        ]);

        // Gestione speciale per la relazione purchaseCost (solo TRADE)
        if ($code === 'TRADE') {
            $user = $income?->getUser();
            $asset = $income?->getAsset();

            $form->add('purchaseCost', EntityType::class, [
                'class' => Cost::class,
                'required' => false,
                'placeholder' => '-- Select Purchase Cost (for Liquidation) --',
                'choice_label' => fn(Cost $c) => sprintf('%s - %s (Cr %s)', $c->getTitle(), $c->getAsset()?->getName(), $c->getAmount()),
                'query_builder' => function (CostRepository $repo) use ($user, $asset) {
                    $qb = $repo->createQueryBuilder('c')
                        ->join('c.costCategory', 'cat')
                        ->where('cat.code = :code')
                        ->setParameter('code', 'TRADE');

                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    if ($asset) {
                        $qb->andWhere('c.asset = :asset')->setParameter('asset', $asset);
                    }
                    return $qb->orderBy('c.id', 'DESC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
                'label' => 'Purchase Cost Reference',
            ]);
        }
    }
}
