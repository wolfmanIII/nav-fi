<?php

namespace App\Controller\Admin;

use App\Entity\Insurance;
use App\Entity\InterestRate;
use App\Entity\AssetRole;
use App\Entity\CostCategory;
use App\Entity\IncomeCategory;
use App\Entity\CompanyRole;
use App\Entity\LocalLaw;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly AdminUrlGenerator $adminUrlGenerator) {}

    public function index(): Response
    {
        $links = [
            'Interest Rate' => $this->adminUrlGenerator->setController(InterestRateCrudController::class)->setAction('index')->generateUrl(),
            'Insurance' => $this->adminUrlGenerator->setController(InsuranceCrudController::class)->setAction('index')->generateUrl(),
            'Asset Role' => $this->adminUrlGenerator->setController(AssetRoleCrudController::class)->setAction('index')->generateUrl(),
            'Cost Category' => $this->adminUrlGenerator->setController(CostCategoryCrudController::class)->setAction('index')->generateUrl(),
            'Income Category' => $this->adminUrlGenerator->setController(IncomeCategoryCrudController::class)->setAction('index')->generateUrl(),
            'Company Role' => $this->adminUrlGenerator->setController(CompanyRoleCrudController::class)->setAction('index')->generateUrl(),
            'Local Law' => $this->adminUrlGenerator->setController(LocalLawCrudController::class)->setAction('index')->generateUrl(),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'links' => $links,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Nav-Fi Web');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Nav-Fi', 'fa fa-home', 'app_home');

        yield MenuItem::section('Settings');
        yield MenuItem::linkToCrud('Interest Rate', 'fas fa-list', InterestRate::class);
        yield MenuItem::linkToCrud('Insurance', 'fas fa-list', Insurance::class);
        yield MenuItem::linkToCrud('AssetRole', 'fas fa-list', AssetRole::class);
        yield MenuItem::linkToCrud('Cost Category', 'fas fa-list', CostCategory::class);
        yield MenuItem::linkToCrud('Income Category', 'fas fa-list', IncomeCategory::class);
        yield MenuItem::linkToCrud('Company Role', 'fas fa-list', CompanyRole::class);
        yield MenuItem::linkToCrud('Local Law', 'fas fa-list', LocalLaw::class);
    }
}
