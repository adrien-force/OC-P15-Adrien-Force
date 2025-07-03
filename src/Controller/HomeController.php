<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route(path: '/guests', name: 'guests')]
    public function guests(): Response
    {
        $guests = $this->em->getRepository(User::class)->findByRole(User::ADMIN_ROLE);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route(path: '/guest/{id}', name: 'guest')]
    public function guest(int $id): Response
    {
        $guest = $this->em->getRepository(User::class)->find($id);

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route(path: '/portfolio/{id}', name: 'portfolio')]
    public function portfolio(?int $id = null): Response
    {
        $albums = $this->em->getRepository(Album::class)->findAll();
        $album = $id ? $this->em->getRepository(Album::class)->find($id) : null;
        $user = $this->em->getRepository(User::class)->findByRole(User::ADMIN_ROLE);

        $medias = $album instanceof Album
            ? $this->em->getRepository(Media::class)->findByAlbum($album)
            : $this->em->getRepository(Media::class)->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }

    #[Route(path: '/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
