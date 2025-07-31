<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(User::ADMIN_ROLE)]
class GuestController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly MediaRepository $mediaRepository,
    ) {
    }

    #[Route(path: '/admin/guest', name: 'admin_guest_index')]
    #[IsGranted(User::ADMIN_ROLE)]
    public function index(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 15);
        $search = $request->query->get('search');

        $guests = $this->userRepository->findAllGuestUsersPaginated(
            ['isGuest' => true],
            ['email' => 'ASC'],
            $limit,
            $limit * ($page - 1),
            $search
        );

        $total = $this->userRepository->countWithCriteria(['isGuest' => true], $search);

        return $this->render('admin/guest/index.html.twig', [
            'guests' => $guests,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    #[Route(path: '/admin/guest/manage', name: 'admin_guest_manage')]
    #[IsGranted(User::ADMIN_ROLE)]
    public function manage(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 15);
        $search = $request->query->get('search');

        $users = $this->userRepository->findAllNonGuestUsersPaginated(
            ['isGuest' => false],
            ['email' => 'ASC'],
            $limit,
            $limit * ($page - 1),
            $search
        );

        $total = $this->userRepository->countNonGuestUsersWithCriteria(['isGuest' => false], $search);

        return $this->render('admin/guest/manage.html.twig', [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    #[Route(path: '/admin/guest/add-role/{id}', name: 'admin_guest_add_role')]
    #[IsGranted(User::ADMIN_ROLE)]
    public function addRole(User $user): RedirectResponse
    {
        if (!$user->isGuest()) {
            $user->setIsGuest(true);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_guest_manage');
    }

    #[Route(path: '/admin/guest/update/{id}', name: 'admin_guest_update')]
    public function update(Request $request, User $guest): RedirectResponse|Response
    {
        if (!$guest->isGuest()) {
            return $this->redirectToRoute('admin_guest_manage');
        }

        $form = $this->createForm(GuestType::class, $guest, ['require_password' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/update.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/guest/remove-role/{id}', name: 'admin_guest_remove_role')]
    #[IsGranted(User::ADMIN_ROLE)]
    public function removeRole(User $guest): RedirectResponse
    {
        if ($guest->isGuest()) {
            $guest->setIsGuest(false);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route(path: '/admin/guest/delete/{id}', name: 'admin_guest_delete')]
    public function delete(User $guest): RedirectResponse
    {
        if ($guest->isGuest()) {
            $medias = $this->mediaRepository->findBy(['user' => $guest]);
            foreach ($medias as $media) {
                $media->setUser(null);
                $this->em->persist($media);
            }
            $this->em->remove($guest);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_guest_index');
    }
}
