<?php

namespace App\Form;

use App\Entity\ShipRole;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipRoleAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shipRoles', EntityType::class, [
                'class' => ShipRole::class,
                'choice_label' => fn (ShipRole $role) => sprintf('%s – %s', $role->getCode(), $role->getName()),
                'multiple' => true,
                'expanded' => false,
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')->orderBy('r.code', 'ASC');
                },
                'attr' => [
                    'class' => 'select m-1 w-full h-48',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'ship' => null,
            'user' => null,
        ]);
    }
}
