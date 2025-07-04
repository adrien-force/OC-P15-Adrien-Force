<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use App\Security\Voter\MediaVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MediaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '/admin/media', name: 'admin_media_index')]
    #[IsGranted(attribute: MediaVoter::VIEW, subject: Media::class)]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);

        $criteria = [];

        if (!$this->isGranted(MediaVoter::VIEW, Media::class)) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $this->em->getRepository(Media::class)->findBy(
            $criteria,
            ['id' => 'ASC'],
            25,
            25 * ($page - 1)
        );
        $total = $this->em->getRepository(Media::class)->count([]);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
        ]);
    }

    #[Route(path: '/admin/media/add', name: 'admin_media_add')]
    #[isGranted(attribute: MediaVoter::ADD, subject: Media::class)]
    public function add(Request $request): RedirectResponse|Response
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $media->getFile()) {
            //Todo: fix here
            if (!$this->isGranted('ROLE_ADMIN')) {
                if (($user = $this->getUser()) instanceof User) {
                    $media->setUser($user);
                } else {
                    throw new LogicException('You must be logged in to add media.');
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
    #[IsGranted(attribute: MediaVoter::DELETE, subject: Media::class)]
    public function delete(Media $media): RedirectResponse
    {
        $this->em->remove($media);
        $this->em->flush();
        unlink($media->getPath());

        return $this->redirectToRoute('admin_media_index');
    }

}
