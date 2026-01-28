<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\Campaign;
use App\Entity\Crew;
use App\Entity\Salary;
use App\Form\Type\ImperialDateType;
use App\Form\Type\TravellerMoneyType;
use App\Model\ImperialDate;
use App\Repository\CrewRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalaryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        /** @var Salary $salary */
        $salary = $builder->getData();

        $firstPaymentDate = new ImperialDate($salary?->getFirstPaymentYear(), $salary?->getFirstPaymentDay());

        $builder
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => '-- Filter by Campaign --',
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                    if ($user) {
                        $qb->andWhere('c.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'choice_label' => 'title',
                'attr' => [
                    'class' => 'select select-bordered w-full',
                    'data-salary-target' => 'campaign',
                    'data-action' => 'change->salary#onCampaignChange',
                ],
            ])
            ->add('asset', EntityType::class, [
                'class' => Asset::class,
                'mapped' => false,
                'required' => false,
                'placeholder' => '-- Filter by Asset --',
                'choice_attr' => function (Asset $asset) {
                    return [
                        'data-campaign' => $asset->getCampaign()?->getId() ? (string) $asset->getCampaign()->getId() : '',
                        'data-start-year' => $asset->getCampaign()?->getStartingYear() ?? '',
                        'data-session-year' => $asset->getCampaign()?->getSessionYear() ?? '',
                    ];
                },
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('a')->orderBy('a.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('a.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'select select-bordered w-full',
                    'data-salary-target' => 'asset',
                    'data-controller' => 'year-limit',
                    'data-action' => 'change->salary#onAssetChange change->year-limit#onAssetChange',
                ],
            ])
            ->add('crew', EntityType::class, [
                'class' => Crew::class,
                'placeholder' => '-- Select Crew Member --',
                'query_builder' => function (CrewRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('c')
                        ->andWhere('c.status = :active')
                        ->setParameter('active', Crew::STATUS_ACTIVE)
                        ->orderBy('c.surname', 'ASC')
                        ->addOrderBy('c.name', 'ASC');

                    if ($user) {
                        $qb->join('c.asset', 'a')
                            ->andWhere('a.user = :user')
                            ->setParameter('user', $user);
                    }
                    return $qb;
                },
                'choice_label' => fn(Crew $c) => sprintf('%s %s (%s)', $c->getName(), $c->getSurname(), $c->getAsset()?->getName() ?? 'Unassigned'),
                'choice_attr' => function (Crew $c) {
                    return [
                        'data-asset' => $c->getAsset()?->getId() ? (string) $c->getAsset()->getId() : '',
                        'data-activation-day' => $c->getActiveDay() ?? '',
                        'data-activation-year' => $c->getActiveYear() ?? '',
                    ];
                },
                'attr' => [
                    'class' => 'select select-bordered w-full',
                    'data-salary-target' => 'crew',
                    'data-action' => 'change->salary#onCrewChange',
                ],
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Monthly Salary (Cr)',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'data-salary-target' => 'amount',
                    'data-action' => 'input->salary#recalculateProRata',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => Salary::STATUS_ACTIVE,
                    'Suspended' => 'Suspended',
                    'Completed' => 'Completed',
                ],
                'attr' => ['class' => 'select select-bordered w-full'],
            ])
            ->add('firstPaymentDate', ImperialDateType::class, [
                'mapped' => false,
                'required' => true,
                'label' => 'First Payment Date',
                'data' => $firstPaymentDate,
                'attr' => [
                    'data-salary-target' => 'firstPaymentDate',
                    'data-action' => 'change->salary#recalculateProRata',
                ],
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            /** @var Salary $salary */
            $salary = $event->getData();
            $form = $event->getForm();

            /** @var ImperialDate|null $firstPayment */
            $firstPayment = $form->get('firstPaymentDate')->getData();
            if ($firstPayment instanceof ImperialDate) {
                $salary->setFirstPaymentDay($firstPayment->getDay());
                $salary->setFirstPaymentYear($firstPayment->getYear());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salary::class,
            'user' => null,
        ]);
    }
}
