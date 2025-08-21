<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    #[Route(path: '/', name: 'home')]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route(path: '/guests', name: 'guests')]
    public function guests(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 15);
        $search = $request->query->get('search');

        $guests = $this->userRepository->findAllGuestUsersPaginated(
            ['isGuest' => true],
            ['name' => 'ASC'],
            $limit,
            $limit * ($page - 1),
            $search
        );

        $total = $this->userRepository->countWithCriteria(['isGuest' => true], $search);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    #[Route(path: '/guest/{id}', name: 'guest')]
    public function guest(User $guest): Response
    {
        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route(path: '/portfolio/{id?}', name: 'portfolio')]
    public function portfolio(?Album $album, Request $request): Response
    {
        $mediaPage = $request->query->getInt('page', 1);
        $albumPage = $request->query->getInt('albumPage', 1);
        $limit = $request->query->getInt('limit', 15);

        $albums = $this->albumRepository->findAllPaginated(
            [],
            ['id' => 'ASC'],
            6,
            6 * ($albumPage - 1)
        );

        $totalAlbums = $this->albumRepository->countWithCriteria([]);

        $medias = $this->mediaRepository->findByAlbumPaginated(
            $album,
            $limit,
            $limit * ($mediaPage - 1)
        );

        $totalMedia = $this->mediaRepository->countByAlbum($album);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'totalAlbums' => $totalAlbums,
            'albumPage' => $albumPage,
            'album' => $album,
            'medias' => $medias,
            'total' => $totalMedia,
            'page' => $mediaPage,
            'limit' => $limit,
        ]);
    }

    #[Route(path: '/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
