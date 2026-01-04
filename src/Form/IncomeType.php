<?php

namespace App\Form;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Ship;
use App\Entity\Company;
use App\Entity\LocalLaw;
use App\Form\EventSubscriber\IncomeDetailsSubscriber;
use App\Form\Config\DayYearLimits;
use App\Form\Type\TravellerMoneyType;
use App\Repository\ShipRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeType extends AbstractType
{
    public function __construct(
        private readonly IncomeDetailsSubscriber $incomeDetailsSubscriber,
        private readonly DayYearLimits $dayYearLimits,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signingDay', NumberType::class, [
                'required' => true,
                'attr' => $this->dayYearLimits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('signingYear', NumberType::class, [
                'required' => true,
                'attr' => $this->dayYearLimits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('signingLocation', TextType::class, [
                'required' => false,
                'label' => 'Signing Location',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentDay', NumberType::class, [
                'required' => false,
                'attr' => $this->dayYearLimits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('paymentYear', NumberType::class, [
                'required' => false,
                'attr' => $this->dayYearLimits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('expirationDay', NumberType::class, [
                'label' => 'Expiration Day',
                'required' => false,
                'attr' => $this->dayYearLimits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('expirationYear', NumberType::class, [
                'label' => 'Expiration Year',
                'required' => false,
                'attr' => $this->dayYearLimits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('cancelDay', NumberType::class, [
                'required' => false,
                'attr' => $this->dayYearLimits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('cancelYear', NumberType::class, [
                'required' => false,
                'attr' => $this->dayYearLimits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incomeCategory', EntityType::class, [
                'class' => IncomeCategory::class,
                'placeholder' => '-- Select a Category --',
                'choice_label' => fn (IncomeCategory $cat) => sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'attr' => [
                    'class' => 'select m-1 w-full',
                    'data-controller' => 'income-details',
                    'data-action' => 'change->income-details#change',
                ],
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'placeholder' => '-- Select a Ship --',
                'required' => false,
                'choice_label' => fn (Ship $ship) => sprintf('%s - %s(%s)', $ship->getName(), $ship->getType(), $ship->getClass()),
                'query_builder' => function (ShipRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    $qb->andWhere('s.campaign IS NOT NULL');
                    return $qb;
                },
                'attr' => ['class' => 'select m-1 w-full'],
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
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;

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
