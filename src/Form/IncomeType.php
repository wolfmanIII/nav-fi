<?php

namespace App\Form;

use App\Entity\Income;
use App\Entity\IncomeCategory;
use App\Entity\Ship;
use App\Form\Type\TravellerMoneyType;
use App\Repository\ShipRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signingDay', IntegerType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signingYear', IntegerType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentDay', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('paymentYear', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('expirationDay', IntegerType::class, [
                'label' => 'Expiration Day',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('expirationYear', IntegerType::class, [
                'label' => 'Expiration Year',
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('cancelDay', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('cancelYear', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('amount', TravellerMoneyType::class, [
                'label' => 'Amount (Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('incomeCategory', EntityType::class, [
                'class' => IncomeCategory::class,
                'placeholder' => '-- Select a Category --',
                'choice_label' => fn (IncomeCategory $cat) => sprintf('%s - %s', $cat->getCode(), $cat->getDescription()),
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('ship', EntityType::class, [
                'class' => Ship::class,
                'placeholder' => '-- Select a Ship --',
                'choice_label' => fn (Ship $ship) => sprintf('%s (%s)', $ship->getName(), $ship->getClass()),
                'query_builder' => function (ShipRepository $repo) use ($user) {
                    $qb = $repo->createQueryBuilder('s')->orderBy('s.name', 'ASC');
                    if ($user) {
                        $qb->andWhere('s.user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Income::class,
            'user' => null,
        ]);
    }
}
