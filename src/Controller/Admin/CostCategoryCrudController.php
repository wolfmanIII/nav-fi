<?php

namespace App\Controller\Admin;

use App\Entity\CostCategory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CostCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CostCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('code'),
            TextField::new('description'),
        ];
    }
}
