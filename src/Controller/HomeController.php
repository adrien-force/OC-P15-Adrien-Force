<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
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
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 15);

        $albums = $this->em->getRepository(Album::class)->findAll();
        $mediaRepository = $this->em->getRepository(Media::class);

        $medias = $mediaRepository->findByAlbumPaginated(
            $album,
            $limit,
            $limit * ($page - 1)
        );

        $total = $mediaRepository->countByAlbum($album);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route(path: '/about', name: 'about')]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}
