<?php

namespace Fonctionnal\Controller;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestControllerTest extends WebTestCase
{
    private UserRepository $userRepository;
    private MediaRepository $mediaRepository;
    private EntityManagerInterface $em;
    private User $adminUser;
    private User $baseUser;
    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->baseUser = $this->userRepository->findByRole(User::USER_ROLE)[0];
    }

    private function getTestClient(): KernelBrowser
    {
        $client =  static::getClient();
        assert($client instanceof KernelBrowser);
        return $client;
    }

    private function getUserById(int $id): User
    {
        return $this->userRepository->findOneBy(['id' => $id]);
    }

    public function testIndexPageRendersCorrectly(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/guest');

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('main div h1', 'Invités');
    }

    public function testManagePageRendersCorrectly(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/guest/manage');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main div h1', 'Gérer les invités');
    }

    public function testAddRoleFunctionality(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $nonGuestUsers = $this->userRepository->findAllNonGuestUsers();

        if (empty($nonGuestUsers)) {
            $this->markTestSkipped('No non-guest users available for testing');
        }

        $nonGuestUser = $nonGuestUsers[1];

        $client->request('GET', '/admin/guest/add-role/' . $nonGuestUser->getId());

        self::assertResponseRedirects('/admin/guest/manage');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main div h1', 'Gérer les invités');

        $this->assertTrue($nonGuestUser->isGuest(), 'User should now have guest role');
    }

    public function testUpdateGuestFunctionality(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $guestUsers = $this->userRepository->findAllGuestUsers();

        if (empty($guestUsers)) {
            $this->markTestSkipped('No guest users available for testing');
        }

        $guestUser = $guestUsers[1];

        $crawler = $client->request('GET', '/admin/guest/update/' . $guestUser->getId());

        self::assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('form[name="guest"]'));

        $form = $crawler->selectButton('Modifier')->form();
        $form['guest[name]'] = 'Updated Firstname';
        $form['guest[email]'] = 'test@email.com';
        $form['guest[description]'] = 'Updated Description';

        $client->submit($form);

        self::assertResponseRedirects('/admin/guest');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main div h1', 'Invités');

        $guestUser = $this->getUserById($guestUser->getId());

        $this->assertEquals('Updated Firstname', $guestUser->getName());
        $this->assertEquals('test@email.com', $guestUser->getEmail());
        $this->assertEquals('Updated Description', $guestUser->getDescription());
    }

    public function testThatUpdateGuestFunctionnalityRedirectsWhenUserIsNotGuest(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $nonGuestUsers = $this->userRepository->findAllNonGuestUsers();

        if (empty($nonGuestUsers)) {
            $this->markTestSkipped('No base users available for testing');
        }

        $guestUser = $nonGuestUsers[0];

        $client->request('GET', '/admin/guest/update/' . $guestUser->getId());

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/admin/guest/manage');
        $client->followRedirect();
    }

    public function testRemoveRoleFunctionality(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $guestUsers = $this->userRepository->findAllGuestUsers();

        if (empty($guestUsers)) {
            $this->markTestSkipped('No guest users available for testing');
        }

        $guestUser = $guestUsers[1];

        $client->request('GET', '/admin/guest/remove-role/' . $guestUser->getId());

        self::assertResponseRedirects('/admin/guest');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main div h1', 'Invités');

        $guestUser = $this->getUserById($guestUser->getId());

        $this->assertFalse($guestUser->isGuest());
    }

    public function testNonAdminCannotAccessGuestPages(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->baseUser);

        $client->request('GET', '/admin/guest');
        self::assertResponseStatusCodeSame(403);

        $client->request('GET', '/admin/guest/manage');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteGuestUserWithMedias(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $guestUser = new User();
        $guestUser->setName('Test Guest')
            ->setEmail('testguest@example.com')
            ->setIsGuest(true)
            ->setPassword('hashedpassword');

        $this->em->persist($guestUser);
        $this->em->flush();

        $media1 = new Media();
        $media1->setTitle('Test Media 1')
            ->setPath('uploads/test1.jpg')
            ->setUser($guestUser);

        $media2 = new Media();
        $media2->setTitle('Test Media 2')
            ->setPath('uploads/test2.jpg')
            ->setUser($guestUser);

        $this->em->persist($media1);
        $this->em->persist($media2);
        $this->em->flush();

        $this->assertTrue($guestUser->isGuest());
        $this->assertEquals($guestUser, $media1->getUser());
        $this->assertEquals($guestUser, $media2->getUser());

        $userId = $guestUser->getId();

        $client->request('GET', '/admin/guest/delete/' . $userId);

        self::assertResponseRedirects('/admin/guest');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $deletedUser = $this->userRepository->find($userId);
        $this->assertNull($deletedUser);

        $updatedMedia1 = $this->mediaRepository->find($media1->getId());
        $updatedMedia2 = $this->mediaRepository->find($media2->getId());
        $this->assertNotNull($updatedMedia1);
        $this->assertNotNull($updatedMedia2);
        $this->assertNull($updatedMedia1->getUser());
        $this->assertNull($updatedMedia2->getUser());
    }

    public function testDeleteGuestUserWithoutMedias(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $guestUser = new User();
        $guestUser->setName('Test Guest No Media')
            ->setEmail('testguest2@example.com')
            ->setIsGuest(true)
            ->setPassword('hashedpassword');

        $this->em->persist($guestUser);
        $this->em->flush();

        $userId = $guestUser->getId();

        $this->assertTrue($guestUser->isGuest());

        $client->request('GET', '/admin/guest/delete/' . $userId);

        self::assertResponseRedirects('/admin/guest');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $deletedUser = $this->userRepository->find($userId);
        $this->assertNull($deletedUser);
    }

    public function testDeleteNonGuestUserDoesNotDelete(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $nonGuestUser = $this->adminUser;
        $userId = $nonGuestUser->getId();

        $this->assertFalse($nonGuestUser->isGuest());

        $client->request('GET', '/admin/guest/delete/' . $userId);

        self::assertResponseRedirects('/admin/guest');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $existingUser = $this->userRepository->find($userId);
        $this->assertNotNull($existingUser);
        $this->assertEquals($nonGuestUser->getName(), $existingUser->getName());
    }
}
