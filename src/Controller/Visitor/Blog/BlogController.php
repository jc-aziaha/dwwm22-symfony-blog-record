<?php

namespace App\Controller\Visitor\Blog;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Like;
use App\Entity\Post;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\CommentFormType;
use App\Repository\CategoryRepository;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager,
        private readonly LikeRepository $likeRepository,
    ) {
    }

    #[Route('/blog', name: 'app_visitor_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $categories = $this->categoryRepository->findAll();
        $tags = $this->tagRepository->findAll();
        $query = $this->postRepository->findBy(['isPublished' => true], ['publishedAt' => 'DESC']);

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

    #[Route('/blog/articles-filtre-par-categorie/{id<\d+>}/{slug}', name: 'app_visitor_blog_filter_by_category', methods: ['GET'])]
    public function filterPostsByCategory(Category $category, Request $request): Response
    {
        $categories = $this->categoryRepository->findAll();
        $tags = $this->tagRepository->findAll();
        $query = $this->postRepository->findBy(['category' => $category, 'isPublished' => true], ['publishedAt' => 'DESC']);

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

    #[Route('/blog/articles-filtre-par-tag/{id<\d+>}/{slug}', name: 'app_visitor_blog_filter_by_tag', methods: ['GET'])]
    public function filterPostsByTag(Tag $tag, Request $request): Response
    {
        $categories = $this->categoryRepository->findAll();
        $tags = $this->tagRepository->findAll();
        $query = $this->postRepository->filterPostsByTag($tag->getId());

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

    #[Route('/blog/article/{id<\d+>}/{slug}', name: 'app_visitor_blog_post_show', methods: ['GET', 'POST'])]
    public function showPost(Post $post, Request $request): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_USER')) {
                return $this->redirectToRoute('app_visitor_blog_post_show', [
                    'id' => $post->getId(),
                    'slug' => $post->getSlug(),
                ]);
            }

            /**
             * @var User
             */
            $user = $this->getUser();

            $comment->setPost($post);
            $comment->setUser($user);
            $comment->setIsActivated(true);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setActivatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_visitor_blog_post_show', [
                'id' => $post->getId(),
                'slug' => $post->getSlug(),
            ]);
        }

        return $this->render('pages/visitor/blog/show.html.twig', [
            'post' => $post,
            'commentForm' => $form->createView(),
        ]);
    }

    #[Route('/blog/article/{id<\d+>}/{slug}/aimer', name: 'app_visitor_blog_post_like', methods: ['GET'])]
    public function likePost(Post $post): Response
    {
        /** @var User */
        $user = $this->getUser();

        if (null == $user) {
            return $this->json([
                'message' => "Veuillez vous connecter afin d'aimer cet article",
            ], Response::HTTP_FORBIDDEN);
        }

        // Si l'article est déjà aimé,
        if ($post->isAlreadyLikedBy($user)) {
            // Récupérer le like en question
            $like = $this->likeRepository->findOneBy(['post' => $post, 'user' => $user]);

            // Le supprimer de la base de données
            $this->entityManager->remove($like);
            $this->entityManager->flush();

            // Retourner le message correspondant ainsi que le nombre de likes mis à jour au client
            return $this->json([
                'message' => "Vous avez retiré votre like de cet article {$post->getTitle()}",
                'totalLikesUpdated' => $this->likeRepository->count(['post' => $post]),
            ]);
        }

        // Dans le cas contraire,
        // Créer le nouveau like
        $like = new Like();

        // Initialiser ses propriétés
        $like->setUser($user);
        $like->setPost($post);
        $like->setCreatedAt(new \DateTimeImmutable());

        // Le sauvegarder en base de données
        $this->entityManager->persist($like);
        $this->entityManager->flush();

        // Retourner le message correspondant ainsi que le nombre de likes mis à jour au client
        return $this->json([
            'message' => "Vous avez liké l'article {$post->getTitle()}",
            'totalLikesUpdated' => $this->likeRepository->count(['post' => $post]),
        ]);
    }
}
