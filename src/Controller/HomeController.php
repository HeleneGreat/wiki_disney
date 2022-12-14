<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ManagerRegistry $doctrine): Response
    {
        //Récupère tout les articles
        $getArticles = $doctrine->getRepository(Article::class)->findAll();
        //Mélange tout les articles
        shuffle($getArticles);
        //Récupère que 2 articles
        $mixedArticles = array_slice($getArticles, 1, 2);

        //Récupère et mélange toutes les catégories
        $getCategory = $doctrine->getRepository(Category::class)->findAll();
        //Mélange tout les catégories
        shuffle($getCategory);
        //Récupère que 2 catégories
        $mixedCats = array_slice($getCategory, 1, 2);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'mixed_articles' => $mixedArticles,
            'mixed_cats' => $mixedCats,
        ]);
    }
}
