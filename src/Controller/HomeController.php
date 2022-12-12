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
        //Récupère et mélange tout les articles
        $getArticles = $doctrine->getRepository(Article::class)->findAll();
        shuffle($getArticles);

        //Récupère et mélange toutes les catégories
        $getCategory = $doctrine->getRepository(Category::class)->findAll();
        shuffle($getCategory);

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'all_articles' => $getArticles,
            'all_cats' => $getCategory,
        ]);
    }
}
