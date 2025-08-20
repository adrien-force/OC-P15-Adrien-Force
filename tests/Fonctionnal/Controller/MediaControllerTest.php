<?php

namespace Fonctionnal\Controller;

use App\Entity\Album;
use App\Entity\User;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaControllerTest extends WebTestCase
{

    private MediaRepository $mediaRepository;
    private User $adminUser;
    private User $baseUser;
    private User $guestUser;
    private Album $album;

    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $uploadsDir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $userRepository = static::getContainer()->get(UserRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
        $albumRepository = static::getContainer()->get(AlbumRepository::class);
        $this->adminUser = $userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->baseUser = $userRepository->findByRole(User::USER_ROLE)[0];

        $guestUsers = $userRepository->findAllGuestUsers();
        $this->guestUser = !empty($guestUsers) ? $guestUsers[0] : $this->baseUser;
        $this->album = $albumRepository->findAll()[0];
    }

    private function getTestClient(): KernelBrowser
    {
        $client =  static::getClient();
        assert($client instanceof KernelBrowser);
        return $client;
    }
    public function testIndex(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);
        $client->request('GET', '/admin/media');
        self::assertResponseIsSuccessful();

        $client->loginUser($this->guestUser);
        $client->request('GET', '/admin/media');
        self::assertResponseIsSuccessful();
    }

    public function testAddMediaForAdmin(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image',
            'media[file]' => $uploadedFile,
            'media[album]' => $this->album->getId(),
            'media[user]' => $this->adminUser->getId(),
        ]);

        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }
        self::assertResponseIsSuccessful();


        $image = $this->mediaRepository->findOneBy(['title' => 'Test Image']);
        self::assertNotNull($image);
        self::assertNotNull($image->getUser());
        self::assertNotNull($image->getAlbum());
        self::assertSame($this->album->getId(), $image->getAlbum()->getId());
    }

    public function testAddMediaForUser(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->baseUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image',
            'media[file]' => $uploadedFile,
        ]);

        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }
        self::assertResponseIsSuccessful();


        $image = $this->mediaRepository->findOneBy(['title' => 'Test Image']);
        self::assertNotNull($image);
        self::assertNotNull($image->getUser());

        $client->request('GET', '/admin/media/delete/'.$image->getId());
    }

    public function testDeleteMedia(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $client->request('GET', '/admin/media/add');

        $filePath = __DIR__.'/MediaContent/img.jpg';
        $uploadedFile = new UploadedFile(
            $filePath,
            'img.jpg',
            'image/jpeg',
            null,
            true
        );

        $client->submitForm('Ajouter', [
            'media[title]' => 'Test Image 2',
            'media[file]' => $uploadedFile,
            'media[user]' => $this->adminUser->getId(),
        ]);

        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }



        $media = $this->mediaRepository->findOneBy(['title' => 'Test Image 2']);

        self::assertNotNull($media);
        self::assertNotNull($media->getUser());

        $client->request('GET', '/admin/media/delete/'.$media->getId());
        
        if ($client->getResponse()->isRedirection()) {
            $client->followRedirect();
        }
        self::assertResponseIsSuccessful();
    }


    public function testDeleteMediaWithFileExists(): void
    {
        $client = $this->getTestClient();
        $client->loginUser($this->adminUser);

        $uploadsDir = __DIR__ . '/../../../public/uploads';
        $testFile = $uploadsDir . '/test_file_to_delete.jpg';
        
        copy(__DIR__.'/MediaContent/img.jpg', $testFile);
        self::assertFileExists($testFile);

        $media = $this->mediaRepository->findOneBy(['title' => 'Test Image 2']);
        if ($media) {
            $media->setPath('uploads/test_file_to_delete.jpg');
            $em = static::getContainer()->get('doctrine.orm.entity_manager');
            $em->flush();

            $client->request('GET', '/admin/media/delete/'.$media->getId());
            
            if ($client->getResponse()->isRedirection()) {
                $client->followRedirect();
                self::assertResponseIsSuccessful();
            }

            self::assertFileDoesNotExist($testFile);
        }
    }

}
