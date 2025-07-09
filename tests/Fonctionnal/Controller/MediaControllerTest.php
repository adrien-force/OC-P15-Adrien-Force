<?php

namespace App\Tests\Fonctionnal\Controller;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends WebTestCase
{

    private UserRepository $userRepository;
    private MediaRepository $mediaRepository;
    private AlbumRepository $albumRepository;
    private User $adminUser;
    private User $baseUser;
    private User $guestUser;
    private Album $album;

    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
        $this->adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->baseUser = $this->userRepository->findByRole(User::USER_ROLE)[0];

        $guestUsers = $this->userRepository->findByRole(User::GUEST_ROLE);
        $this->guestUser = !empty($guestUsers) ? $guestUsers[0] : $this->baseUser;
        $this->album = $this->albumRepository->findAll()[0];
    }
    public function testIndex(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);
        $client->request('GET', '/admin/media');
        self::assertResponseIsSuccessful();

        $client->loginUser($this->guestUser);
        $client->request('GET', '/admin/media');
        self::assertResponseIsSuccessful();
    }

    public function testAddMediaForAdmin(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image',
            'media[file]' => $uploadedFile,
            'media[album]' => $this->album->getId(),
            'media[user]' => $this->adminUser->getId(),
        ]);

        $client->followRedirect();

        self::assertResponseIsSuccessful();


        $image = $this->mediaRepository->findOneBy(['title' => 'Test Image']);
        self::assertNotNull($image);
        self::assertNotNull($image->getUser());
        self::assertNotNull($image->getAlbum());
        self::assertSame($this->album->getId(), $image->getAlbum()->getId());

        $client->request('GET', '/admin/media/delete/'.$image->getId());
    }

    public function testAddMediaForUser(): void
    {
        $client = static::getClient();
        $client->loginUser($this->baseUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image',
            'media[file]' => $uploadedFile,
        ]);

        $client->followRedirect();

        self::assertResponseIsSuccessful();


        $image = $this->mediaRepository->findOneBy(['title' => 'Test Image']);
        self::assertNotNull($image);
        self::assertNotNull($image->getUser());

        $client->request('GET', '/admin/media/delete/'.$image->getId());
    }

    public function testDeleteMedia(): void
    {
        $client = static::getClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true // test mode
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image 2',
            'media[file]' => $uploadedFile,
            'media[user]' => $this->adminUser->getId(),
        ]);

        $client->followRedirect();



        $media = $this->mediaRepository->findOneBy(['title' => 'Test Image 2']);

        self::assertNotNull($media);
        self::assertNotNull($media->getUser());

        $client->request('GET', '/admin/media/delete/'.$media->getId());
        $client->followRedirect();

        self::assertResponseIsSuccessful();
    }

}