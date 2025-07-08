<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\GuestType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[isGranted(User::ADMIN_ROLE)]
class GuestController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route(path: '/admin/guest', name: 'admin_guest_index')]
    #[isGranted(User::ADMIN_ROLE)]
    public function index(): Response
    {
        $guests = $this->userRepository->findByRole(User::GUEST_ROLE);

        return $this->render('admin/guest/index.html.twig', ['guests' => $guests]);
    }

    #[Route(path: '/admin/guest/manage', name: 'admin_guest_manage')]
    #[isGranted(User::ADMIN_ROLE)]
    public function manage(): Response
    {
        $guests = $this->userRepository->findWithoutRole(User::GUEST_ROLE);

        return $this->render('admin/guest/manage.html.twig', ['users' => $guests]);
    }

    #[Route(path: '/admin/guest/add-role/{id}', name: 'admin_guest_add_role')]
    #[isGranted(User::ADMIN_ROLE)]
    public function addRole(User $user): RedirectResponse
    {
        if (!in_array(User::GUEST_ROLE, $user->getRoles(), true)) {
            $user->addRole(User::GUEST_ROLE);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_guest_manage');
    }

    #[Route(path: '/admin/guest/update/{id}', name: 'admin_guest_update')]
    public function update(Request $request, User $guest): RedirectResponse|Response
    {
        if (!in_array(User::GUEST_ROLE, $guest->getRoles(), true)) {
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
    #[isGranted(User::ADMIN_ROLE)]
    public function removeRole(User $guest): RedirectResponse
    {
        if (in_array(User::GUEST_ROLE, $guest->getRoles(), true)) {
            $guest->removeRole(User::GUEST_ROLE);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_guest_index');
    }
}
