<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends BaseController
{
    public const CONTROLLER_NAME = 'ProfileController';

    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {

        return $this->render('profile/index.html.twig', [
            'user' => $this->getUser(),
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/profile/2fa/enable', name: 'app_profile_2fa_enable')]
    public function enable2fa(Request $request, GoogleAuthenticatorInterface $googleAuthenticator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('app_profile');
        }

        // Check if secret is already in session, otherwise generate new one
        $secret = $request->getSession()->get('2fa_secret');
        if (!$secret) {
            $secret = $googleAuthenticator->generateSecret();
            $request->getSession()->set('2fa_secret', $secret);
        }

        // Temporarily set secret on user for QR generation (but don't persist!)
        $user->setGoogleAuthenticatorSecret($secret);

        $qrCodeContent = $googleAuthenticator->getQRContent($user);

        return $this->render('profile/enable_2fa.html.twig', [
            'qrCodeContent' => $qrCodeContent,
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/profile/2fa/verify', name: 'app_profile_2fa_verify', methods: ['POST'])]
    public function verify2fa(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $code = $request->request->get('code');

        // Retrieve secret from session
        $secret = $request->getSession()->get('2fa_secret');

        if (!$secret) {
            $this->addFlash('error', 'Session expired. Please try enabling 2FA again.');
            return $this->redirectToRoute('app_profile_2fa_enable');
        }

        // Set secret temporarily to check code
        $user->setGoogleAuthenticatorSecret($secret);

        if ($googleAuthenticator->checkCode($user, $code)) {
            // Success! Now allow persistence
            $entityManager->persist($user);
            $entityManager->flush();
            $request->getSession()->remove('2fa_secret');

            $this->addFlash('success', '2FA enabled successfully!');
            return $this->redirectToRoute('app_profile');
        }

        $this->addFlash('error', 'Invalid code. Please try again.');
        return $this->redirectToRoute('app_profile_2fa_enable', [
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/profile/2fa/disable', name: 'app_profile_2fa_disable')]
    public function disable2fa(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/disable_2fa.html.twig', [
            'controller_name' => self::CONTROLLER_NAME,
        ]);
    }

    #[Route('/profile/2fa/disable/confirm', name: 'app_profile_2fa_disable_confirm', methods: ['POST'])]
    public function confirmDisable2fa(Request $request, GoogleAuthenticatorInterface $googleAuthenticator, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $code = $request->request->get('code');

        if (!$user->isGoogleAuthenticatorEnabled()) {
            return $this->redirectToRoute('app_profile');
        }

        if ($googleAuthenticator->checkCode($user, $code)) {
            $user->setGoogleAuthenticatorSecret(null);
            $entityManager->flush();

            $this->addFlash('success', 'Two-factor authentication has been disabled.');
            return $this->redirectToRoute('app_profile');
        }

        $this->addFlash('error', 'Invalid code. De-authorization failed.');
        return $this->redirectToRoute('app_profile_2fa_disable');
    }
}
