<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\CompanyRole;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('contact', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('signLabel', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('companyRole', EntityType::class, [
                'class' => CompanyRole::class,
                'choice_label' => fn (CompanyRole $role) => sprintf('%s - %s', $role->getCode(), $role->getDescription()),
                'attr' => ['class' => 'select m-1 w-full'],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('cr')->orderBy('cr.code', 'ASC');
                },
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'textarea m-1 w-full', 'rows' => 3],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
            'user' => null,
        ]);
    }
}
