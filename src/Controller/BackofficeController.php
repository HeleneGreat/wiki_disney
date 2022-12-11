<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class BackofficeController extends AbstractController
{

    #[Route('/dashboard', name: 'dashboard')]
    public function index(ManagerRegistry $doctrine, UserInterface $user): Response
    {
        // User must be registered to access this page
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $userEmail = $this->getUser()->getUserIdentifier();
        $userId = $doctrine->getRepository(User::class)->findOneBy(['email' => $userEmail])->getId();

        $userArticles = $doctrine->getRepository(Article::class)->findBy(['author' => $userId]);
        
        return $this->render('backoffice/dashboard.html.twig', [
            'articles' => $userArticles,
        ]);
    }

}
