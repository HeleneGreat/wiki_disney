<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\Persistence\ManagerRegistry;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository): Response
    {
        // 2 random Articles
        $randomArtId = $articleRepository->twoRandomArticles();
        $randomArtOne = $articleRepository->findBy(['id' => $randomArtId[0]]);
        $randomArtTwo = $articleRepository->findBy(['id' => $randomArtId[1]]);
        $randomArticles = array_merge($randomArtOne, $randomArtTwo);
        
        // 2 random Categories
        $randomCatId = $categoryRepository->twoRandomCategories();
        $randomCatOne = $categoryRepository->findBy(['id' => $randomCatId[0]]);
        $randomCatTwo = $categoryRepository->findBy(['id' => $randomCatId[1]]);
        $randomCategories = array_merge($randomCatOne, $randomCatTwo);
        

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'random_articles' => $randomArticles,
            'random_categories' => $randomCategories,
        ]);
    }
}
