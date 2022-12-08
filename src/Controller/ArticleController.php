<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ArticleController extends AbstractController
{

    // List of all articles, no matter their category
    #[Route('/article', name: 'article_list')]
    public function articleList(ManagerRegistry $doctrine): Response
    {
        $allArticles = $doctrine->getRepository(Article::class)->findAll();
        return $this->render('article/article-all.html.twig', [
            'all_articles' => $allArticles,
        ]);
    }

    // Detail page of 1 article
    #[Route('/article/{articleId}', name: 'one_article', requirements: ['articleId' => '\d+'])]
    public function oneArticle(ManagerRegistry $doctrine, int $articleId, Request $request): Response
    {
        $article = $doctrine->getRepository(Article::class)->findOneBy(['id' => $articleId]);
        if(!$article){
            throw $this->createNotFoundException(
                'Aucun article ne correspond à cette URL.'
            );
        }
        return $this->render('article/article-one.html.twig', [
            'article' => $article,
        ]);
    }

    //Function to edit one article from dashboard
    #[Route('/article/{articleId}/edit/', name: 'article_edit', requirements: ['articleId' => '\d+'])]
    public function articleEdit(int $articleId, ManagerRegistry $doctrine, Request $request)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        
        $allCategories = $doctrine->getRepository(Category::class)->findAll();
        $article = $doctrine->getRepository(Article::class)->find($articleId);
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash("success","L'article a été modifié");
            return $this->redirectToRoute('dashboard');
        }
        
        return $this->renderForm('article/article-edit.html.twig', [
            'articleId' => $articleId,
            'all_categories' => $allCategories,
            'articleForm' => $form
        ]);
    }

    // Form to add a new article
    #[Route('/article/add', name: 'article_add', requirements:['add' => 'a-zA-Z'])]
    public function createArticle(ManagerRegistry $doctrine, Request $request):Response
    {
        // User must be registered to access this page
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $allCategories = $doctrine->getRepository(Category::class)->findAll();
        $article = new Article();
        $article->setAuthor($this->getUser());
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $article = $form->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($article);
            $entityManager->flush();
            return $this->redirectToRoute('article_list');
        }
        
        return $this->renderForm('article/article-add.html.twig', [
            'all_categories' => $allCategories,
            'articleForm' => $form
        ]);
    }

    // Delete one article if the current user is the author of the article
    #[Route('/article/{articleId}/delete', name: 'delete_article', requirements: ['articleId' => '\d+'])]
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
            return $this->redirectToRoute('article_list');
        }else{
            $this->addFlash(
                "error",
                "Vous n'avez pas les droits pour effectuer cette action"
            );
            return $this->redirectToRoute('one_article', ['articleId' => $articleId]);
        }
       

    }
 





    // INIT THE ARTICLE & ARTICLE_CATEGORY TABLES IN DB 
    // Only do it once
    #[Route('/article/init', name: 'article_init')]
    public function articleInit(ManagerRegistry $doctrine): Response
    {
        $princes = $doctrine->getRepository(Category::class)->findOneBy(['name' => "Princes & princesses"]);
        $mechants = $doctrine->getRepository(Category::class)->findOneBy(['name' => "Méchants"]);
        $creatures = $doctrine->getRepository(Category::class)->findOneBy(['name' => "Créatures fantastiques"]);

        // Prince Florian
        $entityManager = $doctrine->getManager();
        $florian = new Article();
        $florian->setTitle('Prince Florian');
        $florian->addCategory($princes);
        $florian->setSummary("L'amoureux de Blanche-Neige");
        $florian->setContent('Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vivamus suscipit tortor eget felis porttitor volutpat. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus.

        Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec rutrum congue leo eget malesuada. Proin eget tortor risus. Proin eget tortor risus. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.
        
        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.
        
        Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Proin eget tortor risus. Proin eget tortor risus. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vivamus suscipit tortor eget felis porttitor volutpat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.');
        $florian->setPicture('florian.png');
        $entityManager->persist($florian);
        
        // La grenouille
        $entityManager = $doctrine->getManager();
        $grenouille = new Article();
        $grenouille->setTitle('La grenouille');
        $grenouille->addCategory($princes);
        $grenouille->addCategory($creatures);
        $grenouille->setSummary("Un prince Naveen de Maldonia transformé en grenouille, ça se passe dans La princesse et la grenouille.");
        $grenouille->setContent('Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vivamus suscipit tortor eget felis porttitor volutpat. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus.

        Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec rutrum congue leo eget malesuada. Proin eget tortor risus. Proin eget tortor risus. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.
        
        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.
        
        Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Proin eget tortor risus. Proin eget tortor risus. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vivamus suscipit tortor eget felis porttitor volutpat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.');
        $grenouille->setPicture('grenouille.png');
        $entityManager->persist($grenouille);

        // La Bête
        $entityManager = $doctrine->getManager();
        $bete = new Article();
        $bete->setTitle('La Bête');
        $bete->addCategory($princes);
        $bete->addCategory($creatures);
        $bete->setSummary("Le méchant prince qui suite à un sort a été transfomé en ignoble bête.");
        $bete->setContent('Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vivamus suscipit tortor eget felis porttitor volutpat. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus.

        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.
        
        Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Proin eget tortor risus. Proin eget tortor risus. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vivamus suscipit tortor eget felis porttitor volutpat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.');
        $bete->setPicture('bete.png');
        $entityManager->persist($bete);

        // Mérida
        $entityManager = $doctrine->getManager();
        $merida = new Article();
        $merida->setTitle('Mérida');
        $merida->addCategory($princes);
        $merida->setSummary("Mérida, la plus rebelle des princesses !");
        $merida->setContent('Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vivamus suscipit tortor eget felis porttitor volutpat. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus.

        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.
        
        Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Proin eget tortor risus. Proin eget tortor risus. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vivamus suscipit tortor eget felis porttitor volutpat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.');
        $merida->setPicture('merida.png');
        $entityManager->persist($merida);

        // Frolo
        $entityManager = $doctrine->getManager();
        $frolo = new Article();
        $frolo->setTitle('Frolo');
        $frolo->addCategory($mechants);
        $frolo->setSummary("Frolo, c'est le méchant du Bossu de Notre-Dame...");
        $frolo->setContent('Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec rutrum congue leo eget malesuada. Proin eget tortor risus. Proin eget tortor risus. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.
        
        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.
        
        Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Proin eget tortor risus. Proin eget tortor risus. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vivamus suscipit tortor eget felis porttitor volutpat. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi.');
        $frolo->setPicture('frolo.png');
        $entityManager->persist($frolo);

        // Cruella
        $entityManager = $doctrine->getManager();
        $cruella = new Article();
        $cruella->setTitle('Cruella');
        $cruella->addCategory($mechants);
        $cruella->setSummary("La méchante qui voulait un manteau fait de la fourrure des 101 dalmatiens.");
        $cruella->setContent('Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vivamus suscipit tortor eget felis porttitor volutpat. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Proin eget tortor risus.

        Sed porttitor lectus nibh. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec rutrum congue leo eget malesuada. Proin eget tortor risus. Proin eget tortor risus. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a.
        
        Curabitur aliquet quam id dui posuere blandit. Donec sollicitudin molestie malesuada. Curabitur aliquet quam id dui posuere blandit. Nulla quis lorem ut libero malesuada feugiat. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Vivamus suscipit tortor eget felis porttitor volutpat. Nulla quis lorem ut libero malesuada feugiat. Donec sollicitudin molestie malesuada. Nulla quis lorem ut libero malesuada feugiat. Vivamus suscipit tortor eget felis porttitor volutpat.');
        $cruella->setPicture('cruella.png');
        $entityManager->persist($cruella);



        $entityManager->flush();
        return $this->redirectToRoute('article_list');
    }


}
