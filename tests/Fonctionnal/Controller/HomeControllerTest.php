<?php

namespace App\Tests\Fonctionnal\Controller;

use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
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

    public function testHomePage(): void
    {
        $client = static::getClient();
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testGuestsPage(): void
    {
        $client = static::getClient();
        $client->request('GET', '/guests');
        self::assertResponseIsSuccessful();
    }

    public function testGuestPage(): void
    {
        $client = static::getClient();
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
        $client = static::getClient();
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
        $client = static::getClient();
        $client->request('GET', '/about');
        self::assertResponseIsSuccessful();
    }



}
