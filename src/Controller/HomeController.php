<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    /**
     * @Route("/guests", name="guests")
     */
    public function guests()
    {
        $guests = $this->em->getRepository(User::class)->findBy(['admin' => false]);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    /**
     * @Route("/guest/{id}", name="guest")
     */
    public function guest(int $id)
    {
        $guest = $this->em->getRepository(User::class)->find($id);

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    /**
     * @Route("/portfolio/{id}", name="portfolio")
     */
    public function portfolio(?int $id = null)
    {
        $albums = $this->em->getRepository(Album::class)->findAll();
        $album = $id ? $this->em->getRepository(Album::class)->find($id) : null;
        $user = $this->em->getRepository(User::class)->findOneByAdmin(true);

        $medias = $album
            ? $this->em->getRepository(Media::class)->findByAlbum($album)
            : $this->em->getRepository(Media::class)->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
