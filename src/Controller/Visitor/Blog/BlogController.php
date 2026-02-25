<?php

namespace App\Controller\Visitor\Blog;

use App\Repository\CategoryRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TagRepository $tagRepository,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route('/blog', name: 'app_visitor_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $categories = $this->categoryRepository->findAll();
        $tags = $this->tagRepository->findAll();
        $query = $this->postRepository->findBy(['isPublished' => true]);

        $posts = $this->paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /* page number */
            10 /* limit per page */
        );

        return $this->render('pages/visitor/blog/index.html.twig', [
            'categories' => $categories,
            'tags' => $tags,
            'posts' => $posts,
        ]);
    }
}
