<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;
use App\Entity\User;
use App\Entity\Category;
use App\Form\ArticleType;

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
            $this->addFlash(
                "error",
                "Aucun article ne correspond à cette adresse."
            );
            return $this->redirectToRoute('article_list');
        }
        return $this->render('article/article-one.html.twig', [
            'article' => $article,
        ]);
    }

    // Form to add a new article
    #[Route('/article/add', name: 'article_add', requirements:['add' => 'a-zA-Z'])]
    public function createArticle(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger):Response
    {
        // User must be registered to access this page
        if($this->getUser()){
            $article = new Article();
            $article->setAuthor($this->getUser());
            $form = $this->createForm(ArticleType::class, $article);
            // ok
            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'image' field is not required
            // so the image file must be processed only when a file is uploaded
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $imageFile->move(
                        $this->getParameter('article_image'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }  

                // updates the 'imageFilename' property to store the PDF file name
                // instead of its contents
                $article->setImage($newFilename);
            }
                $article = $form->getData();
                $entityManager = $doctrine->getManager();
                $entityManager->persist($article);
                $entityManager->flush();
                return $this->redirectToRoute('article_list');
            }

            return $this->renderForm('article/article-control.html.twig', [
                'action' => "Ajouter un article",
                'articleForm' => $form
            ]);
        }else{
            $this->addFlash(
                "error",
                "Vous devez être connecté pour ajouter un article"
            );
            return $this->redirectToRoute('app_login');
        }
    }

    // Update one article from the wiki
    #[Route('/article/{articleId}/update', name: 'article_update_wiki', requirements:['articleId' => '\d+'])]
    public function updateArticleFromWiki(int $articleId, ManagerRegistry $doctrine, Request $request)
    {
        $response = $this->updateArticle($articleId, $doctrine, $request);
        if($response == "successful-update" || $response == "notAuthor"){
            return $this->redirectToRoute('one_article', ['articleId' => $articleId]);
        }elseif($response == "invalid_id"){
            return $this->redirectToRoute('article_list');
        }else{
            return $response;
        }
    }

    // Update one article from dashboard
    #[Route('/dashboard/{articleId}/update/', name: 'article_update_dashboard', requirements: ['articleId' => '\d+'])]
    public function updateArticleFromDashboard(int $articleId, ManagerRegistry $doctrine, Request $request)
    {
        $response = $this->updateArticle($articleId, $doctrine, $request);
        if($response == "successful-update" || $response == "invalid_id" || $response == "notAuthor"){
            return $this->redirectToRoute('dashboard');
        }else{
            return $response;
        }
    }

    public function updateArticle(int $articleId, ManagerRegistry $doctrine, Request $request)
    {
        $article = $doctrine->getRepository(Article::class)->find($articleId);
        // If no article matches the id
        if(!$article){
            $this->addFlash(
                "error",
                "Aucun article ne correspond à cette adresse."
            );
            return "invalid-id";
        }
        // If the user is the article's author
        if($this->getUser() == $article->getAuthor()){
            $form = $this->createForm(ArticleType::class, $article);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $doctrine->getManager()->flush();
                $this->addFlash("success", "L'article a bien été modifié");
                return "successful-update";
            }
            return $this->renderForm('article/article-control.html.twig', [
                'action' => "Modifier un article",
                'articleForm' => $form,
            ]);
        }else{
            $this->addFlash(
                "error",
                "Vous n'avez pas les droits pour effectuer cette action"
            );
            return "notAuthor";
        }
    }

    // Delete one article from the wiki
    #[Route('/article/{articleId}/delete', name: 'article_delete_wiki', requirements:['articleId' => '\d+'])]
    public function deleteArticleFromWiki(int $articleId, ManagerRegistry $doctrine)
    {
        $this->deleteArticle($articleId, $doctrine);
        return $this->redirectToRoute('article_list');
    }

    // Delete one article from dashboard
    #[Route('/dashboard/{articleId}/delete/', name: 'article_delete_dashboard', requirements: ['articleId' => '\d+'])]
    public function deleteArticleFromDashboard(int $articleId, ManagerRegistry $doctrine)
    {
        $this->deleteArticle($articleId, $doctrine);
        return $this->redirectToRoute('dashboard');
    }

    // Delete one article
    public function deleteArticle(int $articleId, ManagerRegistry $doctrine)
    {
        $article = $doctrine->getRepository(Article::class)->find($articleId);
        // If no article matches the id
        if(!$article){
            $this->addFlash(
                "error",
                "Aucun article ne correspond à cette adresse."
            );
        }else{
            // If the user is the article's author
            if($this->getUser() == $article->getAuthor()){
                unlink('../public/uploads/articles/' . $article->getImage());
                $entityManager = $doctrine->getManager();
                $entityManager->remove($article);
                $entityManager->flush();
                $this->addFlash(
                    "success",
                    "L'article a été supprimé"
                );
            }else{
                $this->addFlash(
                    "error",
                    "Vous n'avez pas les droits pour effectuer cette action"
                );
            }
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

        $walt = $doctrine->getRepository(User::class)->findOneBy(['Pseudo' => "Walt"]);
        $edith = $doctrine->getRepository(User::class)->findOneBy(['Pseudo' => "Edith"]);

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
        $florian->setImage('florian.png');
        $florian->setMovie('Blanche-neige');
        $florian->setAuthor($walt);
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
        $grenouille->setImage('grenouille.png');
        $grenouille->setMovie('La princesse et la grenouille');
        $grenouille->setAuthor($walt);
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
        $bete->setImage('bete.png');
        $bete->setMovie('La belle et la bête');
        $bete->setAuthor($edith);
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
        $merida->setImage('merida.png');
        $merida->setMovie('Rebel');
        $merida->setAuthor($walt);
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
        $frolo->setImage('frolo.png');
        $frolo->setMovie('Le bossu de Notre-Dame');
        $frolo->setAuthor($walt);
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
        $cruella->setImage('cruella.png');
        $cruella->setMovie('Les 101 dalmatiens');
        $cruella->setAuthor($edith);
        $entityManager->persist($cruella);



        $entityManager->flush();
        return $this->redirectToRoute('article_list');
    }

}
