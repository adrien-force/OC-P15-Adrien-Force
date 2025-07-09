<?php

namespace App\Tests\Fonctionnal\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }
    public function testLoginPageRendersCorrectly(): void
    {
        $client = static::getClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        $this->assertSelectorTextContains('form button', 'Connexion');
    }

    public function testLoginFormSubmission(): void
    {
        $client = static::getClient();

        $crawler = $client->request('GET', '/admin/register');

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[name]' => 'testuser',
            'registration[email]' => 'testemailuser@test.com',
            'registration[password][first]' => 'TestPassword123!',
            'registration[password][second]' => 'TestPassword123!'
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/admin/media');
        $client->followRedirect();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'testemailuser@test.com',
            '_password' => 'TestPassword123!'
        ]);
        $client->submit($form);
        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testThatLoginRedirectsToHomeIfAlreadyAuthenticated(): void
    {
        $client = static::getClient();
        $client->loginUser($this->userRepository->findAll()[0]);

        $client->request('GET', '/login');

        self::assertResponseRedirects('/');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testLogoutFunctionality(): void
    {
        $client = static::getClient();
        $client->loginUser($this->userRepository->findAll()[0]);

        $client->request('GET', '/logout');

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}