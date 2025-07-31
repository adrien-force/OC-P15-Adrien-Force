<?php

namespace App\Controller\Admin;

use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AlbumController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    #[Route(path: '/admin/album', name: 'admin_album_index')]
    public function index(): Response
    {
        $albums = $this->em->getRepository(Album::class)->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }

    #[Route(path: '/admin/album/add', name: 'admin_album_add')]
    public function add(Request $request): RedirectResponse|Response
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($album);
            $this->em->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/album/update/{id}', name: 'admin_album_update')]
    public function update(Request $request, Album $album): RedirectResponse|Response
    {
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/album/delete/{id}', name: 'admin_album_delete')]
    public function delete(Album $album): RedirectResponse
    {
        foreach ($this->mediaRepository->findBy(['album' => $album]) as $media) {
            $media->setAlbum(null);
            $this->em->persist($media);
        }

        $this->em->flush();
        $this->em->remove($album);
        $this->em->flush();

        return $this->redirectToRoute('admin_album_index');
    }
}
