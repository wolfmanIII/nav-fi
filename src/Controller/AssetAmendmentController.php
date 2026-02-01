<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Entity\AssetAmendment;
use App\Form\AssetAmendmentType;
use App\Repository\AssetAmendmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class AssetAmendmentController extends BaseController
{
    const CONTROLLER_NAME = 'AssetAmendmentController';

    #[Route('/asset/{id}/amendments/new', name: 'app_asset_amendment_new', methods: ['GET', 'POST'])]
    public function new(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        AssetAmendmentRepository $repository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $asset = $em->getRepository(Asset::class)->findOneForUser($id, $user);
        if (!$asset) {
            throw new NotFoundHttpException();
        }

        if (!$asset->hasMortgageSigned()) {
            $this->addFlash('error', 'Amendments are available only after a mortgage is signed.');
            return $this->redirectToRoute('app_asset_edit', ['id' => $asset->getId()]);
        }

        $amendment = (new AssetAmendment())
            ->setAsset($asset)
            ->setUser($user);

        $form = $this->createForm(AssetAmendmentType::class, $amendment, [
            'asset' => $asset,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($amendment);
            $em->flush();

            $this->addFlash('success', 'Amendment recorded.');
            return $this->redirectToRoute('app_asset_edit', ['id' => $asset->getId()]);
        }

        $existing = $repository->findBy(['asset' => $asset, 'user' => $user], ['effectiveYear' => 'DESC', 'effectiveDay' => 'DESC']);

        return $this->renderTurbo('asset/amendment_new.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
            'asset' => $asset,
            'amendments' => $existing,
            'form' => $form,
        ]);
    }
}
