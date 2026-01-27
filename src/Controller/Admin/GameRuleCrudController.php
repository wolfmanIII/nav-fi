<?php

namespace App\Controller\Admin;

use App\Entity\GameRule;
use App\Service\GameRulesEngine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GameRuleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly GameRulesEngine $ruleEngine
    ) {}

    public static function getEntityFqcn(): string
    {
        return GameRule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Game Rule')
            ->setEntityLabelInPlural('Game Rules')
            ->setSearchFields(['ruleKey', 'description', 'category'])
            ->setDefaultSort(['category' => 'ASC', 'ruleKey' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('type');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('ruleKey')
            ->setHelp('Identificativo univoco (es. trade.base_markup). Usa dot notation.')
            ->setColumns(6);

        yield TextField::new('category')
            ->setHelp('Etichetta di raggruppamento per Admin UI (es. PASSENGERS, TRADE)')
            ->setColumns(6);

        yield ChoiceField::new('type')
            ->setChoices([
                'String' => 'string',
                'Integer' => 'int',
                'Float' => 'float',
                'Boolean' => 'bool',
                'JSON' => 'json'
            ])
            ->renderAsBadges();

        yield TextareaField::new('value')
            ->setHelp('Il valore. Verrà castato automaticamente in base al Tipo.');

        yield TextareaField::new('description')
            ->hideOnIndex();
    }

    public function persistEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof GameRule) {
            $this->ruleEngine->invalidate($entityInstance->getRuleKey());
        }
    }

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);
        if ($entityInstance instanceof GameRule) {
            $this->ruleEngine->invalidate($entityInstance->getRuleKey());
        }
    }
}
