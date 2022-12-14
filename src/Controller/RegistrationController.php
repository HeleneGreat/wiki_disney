<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Persistence\ManagerRegistry;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                "notice",
                "Félicitations, votre compte a bien été créé ! Vous pouvez dès à présent rajouter des articles et ainsi participer à la vie de notre wiki."
            );
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }


    

    // INIT THE USER TABLE IN DB 
    // Only do it once
    #[Route('/user/init', name: 'user_init')]
    public function userInit(ManagerRegistry $doctrine, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $entityManager = $doctrine->getManager();

        // Walt
        $walt = new User();
        $walt->setPseudo('Walt');
        $walt->setEmail('walt@disney.com');
        $walt->setPassword($userPasswordHasher->hashPassword($walt, '123456'));
        $entityManager->persist($walt);

        // Edith
        $edith = new User();
        $edith->setPseudo('Edith');
        $edith->setEmail('edith@disney.com');
        $edith->setPassword($userPasswordHasher->hashPassword($edith, '123456'));
        $entityManager->persist($edith);

        $entityManager->flush();
        return $this->redirectToRoute('home');
    }
}
