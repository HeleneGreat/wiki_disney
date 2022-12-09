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
        
        return $this->render('backoffice/index.html.twig', [
            'articles' => $userArticles,
        ]);
    }

        // Delete one article if the current user is the author of the article
        #[Route('/dashboard/{articleId}/delete', name: 'dash_delete_article', requirements: ['articleId' => '\d+'])]
        public function deleteArticle(ManagerRegistry $doctrine, int $articleId):Response
        {
            $article = $doctrine->getRepository(Article::class)->find($articleId);
            if($this->getUser() == $article->getAuthor()){
                $entityManager = $doctrine->getManager();
                $entityManager->remove($article);
                $entityManager->flush();
                $this->addFlash(
                    "notice",
                    "L'article a été supprimé"
                );
                return $this->redirectToRoute('dashboard');
            }else{
                $this->addFlash(
                    "error",
                    "Vous n'avez pas les droits pour effectuer cette action"
                );
                return $this->render('backoffice/index.htlm.twig', ['articleId' => $articleId]);
            }
           
    
        }
}
