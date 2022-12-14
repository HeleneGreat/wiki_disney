<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Category;
use App\Entity\Article;
use App\Repository\CategoryRepository;

class CategoryController extends AbstractController
{

    // List of all categories
    #[Route('/category', name: 'category_list')]
    public function categoryList(): Response
    {
        return $this->render('category/category-all.html.twig', []);
    }

    // Category page = list of all articles from one category
    #[Route('/category/{categoryId}', name: 'one_category', requirements:['categoryId' => '\d+'])]
    public function oneCategory(CategoryRepository $categoryRepository, int $categoryId): Response
    {
        $category = $categoryRepository->findOneBy(['id' => $categoryId]);
        // Redirection if no article matches the id
        if(!$category){
            $this->addFlash(
                "error",
                "Aucune catégorie ne correspond à cette adresse."
            );
            return $this->redirectToRoute('category_list');
        }
        $categoryArticles = $category->getArticle();
        
        return $this->render('category/category-one.html.twig', [
            'category' => $category,
            'category_articles' => $categoryArticles,
        ]);
    }





    // INIT THE CATEGORY TABLE IN DB 
    // Only do it once
    #[Route('/category/init', name: 'category_init')]
    public function categoryInit(ManagerRegistry $doctrine): Response
    {
        // Princes & princesses
        $entityManager = $doctrine->getManager();
        $prince = new Category();
        $prince->setName('Princes & princesses');
        $prince->setImage('princes-princesses.png');
        $entityManager->persist($prince);

        // Méchants
        $mechant = new Category();
        $mechant->setName('Méchants');
        $mechant->setImage('mechants.png');
        $entityManager->persist($mechant);

        // Créatures fantastiques
        $creature = new Category();
        $creature->setName('Créatures fantastiques');
        $creature->setImage('creatures-fantastiques.png');
        $entityManager->persist($creature);

        // Animaux
        $creature = new Category();
        $creature->setName('Animaux');
        $creature->setImage('animaux.png');
        $entityManager->persist($creature);

        $entityManager->flush();
        return $this->redirectToRoute('category_list');
    }


}
