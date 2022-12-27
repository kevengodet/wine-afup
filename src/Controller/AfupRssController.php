<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Service\AfupRssService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class AfupRssController extends AbstractController
{
    #[Route('/articles', name: 'app_afup_index')]
    public function index(Environment $twig, AfupRssService $afupFeed, ArticleRepository $articles): Response
    {
        $afupFeed->importNewArticlesIfRequired();

        return $this->render('articles.html.twig', [
            'articles' => $articles->findLatestArticles()
        ]);
    }

    #[Route('/article/:slug', name: 'app_afup_article')]
    public function show(): JsonResponse
    {
    }
}
