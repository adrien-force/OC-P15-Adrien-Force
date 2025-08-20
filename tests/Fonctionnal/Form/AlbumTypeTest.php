<?php

namespace Fonctionnal\Form;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AlbumTypeTest extends WebTestCase
{
    private AlbumRepository $albumRepository;
    private Album $album;

    protected function setUp(): void
    {
        parent::setUp();

        $client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findByRole(User::ADMIN_ROLE)[0];
        $client->loginUser($adminUser);

        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
        $this->album = $this->albumRepository->findAll()[0];
    }

    public function testThatAlbumUpdateFormRendersCorrectly(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/album/update/' . $this->album->getId());

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form[name="album"]'));
        $this->assertCount(1, $crawler->filter('input[name="album[name]"]'));
    }

    public function testSubmittingValidData(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/album/update/' . $this->album->getId());

        $form = $crawler->selectButton('Modifier')->form();
        $newName = 'Updated Album Name';
        $form['album[name]'] = $newName;

        $client->submit($form);

        self::assertResponseRedirects('/admin/album');
        $client->followRedirect();

        $updatedAlbum = $this->albumRepository->findBy(['id' => $this->album->getId()])[0];
        self::assertSame($newName, $updatedAlbum->getName());

    }

    public function testSubmittingInvalidData(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', '/admin/album/update/' . $this->album->getId());

        $form = $crawler->selectButton('Modifier')->form();
        $form['album[name]'] = '';

        $client->submit($form);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('.invalid-feedback', 'Cette valeur ne doit pas être vide.');
    }
}

