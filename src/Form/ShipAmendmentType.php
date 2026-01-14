<?php

namespace App\Form;

use App\Dto\ShipDetailsData;
use App\Entity\Cost;
use App\Entity\Ship;
use App\Entity\ShipAmendment;
use App\Form\Config\DayYearLimits;
use App\Repository\CostRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipAmendmentType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ShipAmendment $amendment */
        $amendment = $options['data'];
        /** @var Ship $ship */
        $ship = $options['ship'];
        $user = $options['user'];

        $minYear = $ship->getCampaign()?->getStartingYear() ?? $this->limits->getYearMin();
        $detailsData = ShipDetailsData::fromArray($amendment->getPatchDetails() ?? []);

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 6],
            ])
            ->add('cost', EntityType::class, [
                'class' => Cost::class,
                'required' => true,
                'placeholder' => '-- Select cost reference --',
                'choice_label' => fn (Cost $cost) => sprintf('%s — %s', $cost->getTitle(), $cost->getAmount()),
                'query_builder' => function (CostRepository $repo) use ($ship, $user, $amendment) {
                    $amendmentId = $amendment?->getId();
                    $qb = $repo->createQueryBuilder('c')
                        ->leftJoin('c.costCategory', 'cc')
                        ->leftJoin(ShipAmendment::class, 'sa', 'WITH', 'sa.cost = c')
                        ->andWhere('c.ship = :ship')
                        ->andWhere('cc.code IN (:codes)')
                        ->andWhere('c.paymentDay IS NOT NULL')
                        ->andWhere('c.paymentYear IS NOT NULL')
                        ->andWhere('sa.id IS NULL' . ($amendmentId ? ' OR sa.id = :amendmentId' : ''))
                        ->setParameter('ship', $ship)
                        ->setParameter('codes', ['SHIP_GEAR', 'SHIP_SOFTWARE'])
                        ->orderBy('c.paymentYear', 'DESC')
                        ->addOrderBy('c.paymentDay', 'DESC');

                    if ($amendmentId) {
                        $qb->setParameter('amendmentId', $amendmentId);
                    }

                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'select select-bordered w-full bg-slate-950/50 border-slate-700',
                    'data-controller' => 'tom-select',
                    'data-tom-select-placeholder-value' => 'Search cost reference…',
                ],
            ])
            ->add('patchDetails', ShipDetailsType::class, [
                'mapped' => false,
                'data' => $detailsData,
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var ShipAmendment $amendment */
            $amendment = $event->getData();
            $form = $event->getForm();

            $cost = $amendment->getCost();
            if ($cost) {
                $amendment->setEffectiveDay($cost->getPaymentDay());
                $amendment->setEffectiveYear($cost->getPaymentYear());
            }

            /** @var ShipDetailsData $details */
            $details = $form->get('patchDetails')->getData();
            if ($details instanceof ShipDetailsData) {
                $amendment->setPatchDetails($details->toArray());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShipAmendment::class,
            'ship' => null,
            'user' => null,
        ]);
    }
}
