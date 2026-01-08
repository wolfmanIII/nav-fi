<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\CompanyRole;
use App\Form\CompanyType;
use App\Security\Voter\CompanyVoter;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyController extends BaseController
{
    public const CONTROLLER_NAME = 'CompanyController';

    #[Route('/company/index', name: 'app_company_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        $filters = $listViewHelper->collectFilters($request, [
            'name',
            'contact',
            'role' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $companies = [];
        $total = 0;
        $totalPages = 1;
        $roles = [];

        if ($user instanceof \App\Entity\User) {
            $result = $em->getRepository(Company::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $companies = $result['items'];
            $total = $result['total'];

            $totalPages = max(1, (int) ceil($total / $perPage));
            $clampedPage = $listViewHelper->clampPage($page, $totalPages);
            if ($clampedPage !== $page) {
                $page = $clampedPage;
                $result = $em->getRepository(Company::class)->findForUserWithFilters($user, $filters, $page, $perPage);
                $companies = $result['items'];
            }

            $roles = $em->getRepository(CompanyRole::class)->findBy([], ['code' => 'ASC']);
        }

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('company/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'companies' => $companies,
            'filters' => $filters,
            'roles' => $roles,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/company/new', name: 'app_company_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($company);
            $em->flush();

            return $this->redirectToRoute('app_company_index');
        }

        return $this->renderTurbo('company/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/company/edit/{id}', name: 'app_company_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $company = $em->getRepository(Company::class)->findOneForUser($id, $user);
        if (!$company) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(CompanyVoter::EDIT, $company);

        $form = $this->createForm(CompanyType::class, $company, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_company_index');
        }

        return $this->renderTurbo('company/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'company' => $company,
            'form' => $form,
        ]);
    }

    #[Route('/company/delete/{id}', name: 'app_company_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $company = $em->getRepository(Company::class)->findOneForUser($id, $user);
        if (!$company) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(CompanyVoter::DELETE, $company);

        $em->remove($company);
        $em->flush();

        return $this->redirectToRoute('app_company_index');
    }
}
