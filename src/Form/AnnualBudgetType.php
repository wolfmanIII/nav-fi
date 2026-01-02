<?php

namespace App\Form;

use App\Entity\AnnualBudget;
use App\Entity\Ship;
use App\Form\Config\DayYearLimits;
use App\Repository\ShipRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnualBudgetType extends AbstractType
{
    public function __construct(private readonly DayYearLimits $limits)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('startDay', IntegerType::class, [
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('startYear', IntegerType::class, [
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('endDay', IntegerType::class, [
                'attr' => $this->limits->dayAttr(['class' => 'input m-1 w-full']),
            ])
            ->add('endYear', IntegerType::class, [
                'attr' => $this->limits->yearAttr(['class' => 'input m-1 w-full']),
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
            'data_class' => AnnualBudget::class,
            'user' => null,
        ]);
    }
}
