<?php

namespace App\Form;

use App\Entity\Campaign;
use App\Entity\Ship;
use App\Dto\ShipDetailsData;
use App\Repository\CampaignRepository;
use App\Form\ShipDetailsType;
use App\Form\Type\TravellerMoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Ship $ship */
        $ship = $options['data'];
        $detailsData = ShipDetailsData::fromArray($ship->getShipDetails() ?? []);
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('type', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('class', TextType::class, [
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('campaign', EntityType::class, [
                'class' => Campaign::class,
                'placeholder' => '-- Select a Mission --',
                'required' => false,
                'choice_label' => fn(Campaign $c) => $c->getTitle(),
                'query_builder' => function (CampaignRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.title', 'ASC');
                },
                'attr' => ['class' => 'select m-1 w-full'],
            ])
            ->add('price', TravellerMoneyType::class, [
                'label' => 'Price(Cr)',
                'attr' => ['class' => 'input m-1 w-full'],
            ])
            ->add('shipDetails', ShipDetailsType::class, [
                'mapped' => false,
                'data' => $detailsData,
                'label' => 'Ship Details',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ship::class,
        ]);
    }
}
