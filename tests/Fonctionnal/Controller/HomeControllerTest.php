<?php

namespace Fonctionnal\Controller;

use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private UserRepository $userRepository;
    private AlbumRepository $albumRepository;
    public function setUp(): void
    {
        parent::setUp();
        static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
    }

    private function getTestClient(): KernelBrowser
    {
        $client =  static::getClient();
        assert($client instanceof KernelBrowser);
        return $client;
    }

    public function testHomePage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testGuestsPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/guests');
        self::assertResponseIsSuccessful();
    }

    public function testGuestPage(): void
    {
        $client = $this->getTestClient();
        $guest = $this->userRepository->findAllGuestUsers()[0] ?? null;
        if ($guest) {
            $client->request('GET', '/guest/' . $guest->getId());
            self::assertResponseIsSuccessful();
        } else {
            self::markTestSkipped('No guest user found.');
        }
    }

    public function testPortfolioPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/portfolio');
        self::assertResponseIsSuccessful();

        $album = $this->albumRepository->findOneBy([]);
        if ($album) {
            $client->request('GET', '/portfolio/' . $album->getId());
            self::assertResponseIsSuccessful();
        } else {
            self::markTestSkipped('No album found.');
        }
    }

    public function testAboutPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/about');
        self::assertResponseIsSuccessful();
    }

}
