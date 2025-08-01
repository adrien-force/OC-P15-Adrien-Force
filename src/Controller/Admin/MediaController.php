<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    #[Route(path: '/admin/media', name: 'admin_media_index')]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 25);

        $criteria = [];

        if (!$this->isGranted(User::ADMIN_ROLE)) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $this->mediaRepository->findAllMediaPaginatedWithAlbumAndUser(
            $criteria,
            ['id' => 'ASC'],
            $limit,
            $limit * ($page - 1)
        );

        $total = $this->mediaRepository->countWithCriteria($criteria);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route(path: '/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request): RedirectResponse|Response
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted(User::ADMIN_ROLE)]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $media->getFile()) {
            if (!$this->isGranted(User::ADMIN_ROLE)) {
                if (($user = $this->getUser()) instanceof User) {
                    $media->setUser($user);
                }
            }
            $media->setPath('uploads/'.md5(uniqid()).'.'.$media->getFile()->guessExtension());
            $media->getFile()->move('uploads/', $media->getPath());
            $this->em->persist($media);
            $this->em->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(Media $media): RedirectResponse
    {
        $this->em->remove($media);
        $this->em->flush();
        unlink($media->getPath());

        return $this->redirectToRoute('admin_media_index');
    }
}
