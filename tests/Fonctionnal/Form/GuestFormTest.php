<?php

namespace App\Tests\Fonctionnal\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestFormTest extends WebTestCase
{
    private UserRepository $userRepository;
    private User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->guestUser = $this->userRepository->findAllGuestUsers()[0];

        $client->loginUser($adminUser);
    }

    public function testThatGuestUpdateFormRendersCorrectly(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/guest/update/'.$this->guestUser->getId());

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form[name="guest"]'));
        $this->assertCount(1, $crawler->filter('input[name="guest[name]"]'));
        $this->assertCount(1, $crawler->filter('input[name="guest[email]"]'));
        $this->assertCount(1, $crawler->filter('textarea[name="guest[description]"]'));
    }

    public function testSubmittingValidData(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/guest/update/'.$this->guestUser->getId());

        $form = $crawler->selectButton('Modifier')->form();
        $newName = 'Updated Guest Name';
        $form['guest[name]'] = $newName;

        $client->submit($form);

        self::assertResponseRedirects('/admin/guest');
        $updatedUser = $this->userRepository->findBy(['id' => $this->guestUser->getId()])[0];
        self::assertSame($newName, $updatedUser->getName());
    }

    public function testSubmittingInvalidData(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/guest/update/'.$this->guestUser->getId());

        $form = $crawler->selectButton('Modifier')->form();
        $form['guest[email]'] = 'blabla';

        $client->submit($form);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('.invalid-feedback', 'Cette valeur n\'est pas une adresse email valide.');
    }
}
