<?php

namespace App\Controller\Admin\Post;

use App\Entity\Post;
use App\Entity\User;
use App\Form\Admin\PostFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class PostController extends AbstractController
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/post/list', name: 'app_admin_post_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/admin/post/index.html.twig');
    }

    #[Route('/post/create', name: 'app_admin_post_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if (0 == $this->categoryRepository->count()) {
            $this->addFlash('warning', 'Vous devez créer au moins une catégorie afin de rédiger des articles.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        $post = new Post();

        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var User
             */
            $admin = $this->getUser();

            $post->setUser($admin);
            $post->setCreatedAt(new \DateTimeImmutable());
            $post->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', "L'article a été ajouté avec succès.");

            return $this->redirectToRoute('app_admin_post_index');
        }

        return $this->render('pages/admin/post/create.html.twig', [
            'postForm' => $form->createView(),
        ]);
    }
}
