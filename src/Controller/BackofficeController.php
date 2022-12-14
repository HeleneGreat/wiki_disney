<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;
use App\Entity\User;

class BackofficeController extends AbstractController
{

    #[Route('/dashboard', name: 'dashboard')]
    public function index(ManagerRegistry $doctrine): Response
    {
        if($this->getUser()){
        $userEmail = $this->getUser()->getUserIdentifier();
        $userId = $doctrine->getRepository(User::class)->findOneBy(['email' => $userEmail])->getId();

        $userArticles = $doctrine->getRepository(Article::class)->findBy(['author' => $userId]);
        
        return $this->render('backoffice/dashboard.html.twig', [
            'articles' => $userArticles,
        ]);
        }else{
            $this->addFlash(
                "error",
                "Vous n'avez pas les droits pour effectuer cette action"
            );
            return $this->redirectToRoute('home');
        }
        
    }
    #[Route('/mesinfos', name: 'mesinfos')]
    public function afficheMesInfos(): Response
    {
        if($this->getUser()){        
        return $this->render('backoffice/mesinfos.html.twig');
        }else{
            $this->addFlash(
                    "error",
                    "Vous n'avez pas les droits pour effectuer cette action"
                );
                return $this->redirectToRoute('home');
        }
       
    }

}
