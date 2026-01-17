<?php

namespace App\Form;

use App\Entity\AssetRole;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetRoleAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assetRoles', EntityType::class, [
                'class' => AssetRole::class,
                'choice_label' => fn(AssetRole $role) => sprintf('%s – %s', $role->getCode(), $role->getName()),
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
            'asset' => null,
            'user' => null,
        ]);
    }
}
