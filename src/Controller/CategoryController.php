<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Category;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'category-list')]
    public function categoryList(ManagerRegistry $doctrine): Response
    {
        $allCategories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('category/category.html.twig', [
            'all_categories' => $allCategories,
        ]);
    }



    #[Route('/category/init', name: 'category-init')]
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

        $entityManager->flush();
        return $this->redirectToRoute('category-list');
    }


}
