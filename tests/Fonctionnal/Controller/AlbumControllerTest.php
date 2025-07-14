<?php

namespace App\Tests\Fonctionnal\Controller;

use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \App\Controller\Admin\AlbumController
 */
class AlbumControllerTest extends WebTestCase
{

    private User $baseUser;
    private User $adminUser;
    private UserRepository $userRepository;
    private AlbumRepository $albumRepository;
    private MediaRepository $mediaRepository;
    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
        $this->baseUser = $this->userRepository->findAllGuestUsers()[0];
        $this->adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];

    }
    public function testIndexRenders(): void
    {
        $client = $this->client;

        $client->loginUser($this->baseUser);
        $client->request('GET', '/admin/album');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main div h1', 'Albums');
        self::assertSelectorTextContains('td a', 'Modifier');
        self::assertSelectorTextContains('a.btn-danger', 'Supprimer');

    }

    public function testAddRenders(): void
    {
        $client = $this->client;

        $client->loginUser($this->baseUser);
        $client->request('GET', '/admin/album/add');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('form button', 'Ajouter');
    }

    public function testUpdateRenders(): void
    {
        $client = $this->client;

        $client->loginUser($this->baseUser);
        $client->request('GET', '/admin/album/update/1');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('form button', 'Modifier');
    }

    public function testDeleteAlbum(): void
    {
        $client = $this->client;

        $client->loginUser($this->baseUser);
        $client->request('GET', '/admin/album/delete/1');

        self::assertResponseRedirects('/admin/album');
        self::assertTrue($client->getResponse()->isRedirect());
    }

    public function testDeleteAlbumWithMedia(): void
    {
        $client = $this->client;
        $client->loginUser($this->adminUser);

        $album = $this->albumRepository->findAll()[0];
        $media = $this->mediaRepository->findAll()[0];
        $media->setAlbum($album);


        $client->request('GET', '/admin/album/delete/1');
        self::assertResponseRedirects('/admin/album');
        $client->followRedirect();

        self::assertNull($this->albumRepository->find($media->getId()));
        self::assertNotNull($this->mediaRepository->find($media->getId()));
    }

    public function testNewAlbumValidForm(): void
    {
        $client = $this->client;
        $client->loginUser($this->adminUser);


        $crawler = $client->request('GET', '/admin/album/add');
        $this->assertCount(1, $crawler->filter('form[name="album"]'));
        $form = $crawler->selectButton('Ajouter')->form();
        $form['album[name]'] = 'Test Album';

        $client->submit($form);

        self::assertResponseRedirects('/admin/album');
        $client->followRedirect();

        self::assertNotNull($this->albumRepository->findOneBy(['name' => 'Test Album']));
    }

    public function testUpdateAlbumValidForm(): void
    {
        $client = $this->client;
        $client->loginUser($this->adminUser);

        $crawler = $client->request('GET', '/admin/album/update/1');
        $this->assertCount(1, $crawler->filter('form[name="album"]'));
        $form = $crawler->selectButton('Modifier')->form();
        $form['album[name]'] = 'Updated Album Name';

        $client->submit($form);

        self::assertResponseRedirects('/admin/album');
        $client->followRedirect();

        self::assertNotNull($this->albumRepository->findOneBy(['name' => 'Updated Album Name']));
    }

}