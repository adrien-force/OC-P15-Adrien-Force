<?php

namespace App\Tests\Fonctionnal\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestControllerTest extends WebTestCase
{
    private UserRepository $userRepository;
    private User $adminUser;
    private User $baseUser;
    private User $guestUser;
    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->baseUser = $this->userRepository->findByRole(User::USER_ROLE)[0];

        $guestUsers = $this->userRepository->findByRole(User::GUEST_ROLE);
        $this->guestUser = !empty($guestUsers) ? $guestUsers[0] : $this->baseUser;
    }

    private function getGuestUser(): User
    {
        return $this->guestUser;
    }

    private function getUserById(int $id): User
    {
        return $this->userRepository->findOneBy(['id' => $id]);
    }

    public function testIndexPageRendersCorrectly(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $crawler = $client->request('GET', '/admin/guest');

        self::assertResponseIsSuccessful();

        $this->assertSelectorTextContains('main div h1', 'Invités');
    }

    public function testManagePageRendersCorrectly(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $crawler = $client->request('GET', '/admin/guest/manage');

        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main div h1', 'Gérer les invités');
    }

    public function testAddRoleFunctionality(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $nonGuestUsers = $this->userRepository->findWithoutRole(User::GUEST_ROLE);

        if (empty($nonGuestUsers)) {
            $this->markTestSkipped('No non-guest users available for testing');
        }

        $nonGuestUser = $nonGuestUsers[1];

        $client->request('GET', '/admin/guest/add-role/' . $nonGuestUser->getId());

        self::assertResponseRedirects('/admin/guest/manage');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main div h1',  'Gérer les invités');

        $this->assertTrue(in_array(User::GUEST_ROLE, $nonGuestUser->getRoles(), true));
    }

    public function testUpdateGuestFunctionality(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        // Find a user with guest role
        $guestUsers = $this->userRepository->findByRole(User::GUEST_ROLE);

        if (empty($guestUsers)) {
            $this->markTestSkipped('No guest users available for testing');
        }

        $guestUser = $guestUsers[1];

        // Go to update page
        $crawler = $client->request('GET', '/admin/guest/update/' . $guestUser->getId());

        self::assertResponseIsSuccessful();

        // Check if form exists
        $this->assertCount(1, $crawler->filter('form[name="guest"]'));

        // Submit form with updated data
        $form = $crawler->selectButton('Modifier')->form();
        $form['guest[name]'] = 'Updated Firstname';
        $form['guest[email]'] = 'test@email.com';
        $form['guest[description]'] = 'Updated Description';

        $client->submit($form);

        // Should redirect to index page
        self::assertResponseRedirects('/admin/guest');

        // Follow redirect
        $client->followRedirect();

        // Check if we're on the index page
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main div h1', 'Invités');

        $guestUser = $this->getUserById($guestUser->getId());

        // Check if user data was updated
        $this->assertEquals('Updated Firstname', $guestUser->getName());
        $this->assertEquals('test@email.com', $guestUser->getEmail());
        $this->assertEquals('Updated Description', $guestUser->getDescription());
    }

    public function testThatUpdateGuestFunctionnalityRedirectsWhenUserIsNotGuest(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $guestUsers = $this->userRepository->findByRole(User::USER_ROLE);

        if (empty($guestUsers)) {
            $this->markTestSkipped('No guest users available for testing');
        }

        $guestUser = $guestUsers[0];

        $crawler = $client->request('GET', '/admin/guest/update/' . $guestUser->getId());

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/admin/guest/manage');
        $client->followRedirect();
    }

    public function testRemoveRoleFunctionality(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        // Find a user with guest role
        $guestUsers = $this->userRepository->findByRole(User::GUEST_ROLE);

        if (empty($guestUsers)) {
            $this->markTestSkipped('No guest users available for testing');
        }

        $guestUser = $guestUsers[1];

        // Remove guest role
        $client->request('GET', '/admin/guest/remove-role/' . $guestUser->getId());

        // Should redirect to index page
        self::assertResponseRedirects('/admin/guest');

        // Follow redirect
        $client->followRedirect();

        // Check if we're on the index page
        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main div h1', 'Invités');

        $guestUser = $this->getUserById($guestUser->getId());

        // Check if user no longer has guest role
        $this->assertFalse(in_array(User::GUEST_ROLE, $guestUser->getRoles(), true));
    }

    public function testNonAdminCannotAccessGuestPages(): void
    {
        $client = static::getClient();
        $client->loginUser($this->baseUser);

        // Try to access index page
        $client->request('GET', '/admin/guest');
        self::assertResponseStatusCodeSame(403); // Forbidden

        // Try to access manage page
        $client->request('GET', '/admin/guest/manage');
        self::assertResponseStatusCodeSame(403); // Forbidden
    }
}
