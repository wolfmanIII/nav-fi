<?php

namespace App\Controller;

use App\Entity\Salary;
use App\Entity\Asset;
use App\Entity\Campaign;
use App\Form\SalaryType;
use App\Service\ListViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class SalaryController extends BaseController
{
    public const CONTROLLER_NAME = 'SalaryController';

    #[Route('/salary/index', name: 'app_salary_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, ListViewHelper $listViewHelper): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $filters = $listViewHelper->collectFilters($request, [
            'search',
            'asset' => ['type' => 'int'],
            'campaign' => ['type' => 'int'],
        ]);
        $page = $listViewHelper->getPage($request);
        $perPage = 10;

        $result = $em->getRepository(Salary::class)->findForUserWithFilters($user, $filters, $page, $perPage);
        $salaries = $result['items'];
        $total = $result['total'];

        $totalPages = max(1, (int) ceil($total / $perPage));
        $clampedPage = $listViewHelper->clampPage($page, $totalPages);
        if ($clampedPage !== $page) {
            $page = $clampedPage;
            $result = $em->getRepository(Salary::class)->findForUserWithFilters($user, $filters, $page, $perPage);
            $salaries = $result['items'];
        }

        $assets = $em->getRepository(Asset::class)->findAllForUser($user);
        $campaigns = $em->getRepository(Campaign::class)->findAllForUser($user);

        $pagination = $listViewHelper->buildPaginationPayload($page, $perPage, $total);

        return $this->render('salary/index.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'salaries' => $salaries,
            'filters' => $filters,
            'assets' => $assets,
            'campaigns' => $campaigns,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/salary/new', name: 'app_salary_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $salary = new Salary();
        $form = $this->createForm(SalaryType::class, $salary, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($salary);
            $em->flush();

            return $this->redirectToRoute('app_salary_index');
        }

        return $this->renderTurbo('salary/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'salary' => $salary,
            'form' => $form,
        ]);
    }

    #[Route('/salary/edit/{id}', name: 'app_salary_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $salary = $em->getRepository(Salary::class)->findOneForUser($id, $user);
        if (!$salary) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(SalaryType::class, $salary, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_salary_index');
        }

        return $this->renderTurbo('salary/edit.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'salary' => $salary,
            'form' => $form,
        ]);
    }

    #[Route('/salary/delete/{id}', name: 'app_salary_delete', methods: ['GET', 'POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $salary = $em->getRepository(Salary::class)->findOneForUser($id, $user);
        if (!$salary) {
            throw new NotFoundHttpException();
        }

        $em->remove($salary);
        $em->flush();

        return $this->redirectToRoute('app_salary_index');
    }
}
