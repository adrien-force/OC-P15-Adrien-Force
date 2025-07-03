<?php

namespace App\Tests\Fonctionnal\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MediaFormTest extends WebTestCase
{
    private UserRepository $userRepository;

    private User $adminUser;
    private User $baseUser;


    public function provideUncorrectMediaFiles(): \Generator
    {
        yield ['image.gif' => 'image.gif'];
        yield ['image.svg' => 'image.svg'];
        yield ['audio.mp3' => 'audio.mp3'];
        yield ['video.mp4' => 'video.mp4'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::createClient();

        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->adminUser = $this->userRepository->findByRole(User::ADMIN_ROLE)[0];
        $this->baseUser = $this->userRepository->findByRole(User::USER_ROLE)[0];
    }

    public function testThatMediaFormRendersCorrectly(): void
    {
        $client = static::getClient();

        $client->loginUser($this->adminUser);


        $crawler = $client->request('GET', '/admin/media/add');

        self::assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('form[name="media"]'));
        $this->assertCount(1, $crawler->filter('select[name="media[user]"]'));
        $this->assertCount(1, $crawler->filter('select[name="media[album]"]'));
        $this->assertCount(1, $crawler->filter('input[name="media[title]"]'));
        $this->assertCount(1, $crawler->filter('input[name="media[file]"]'));
    }

    public function testThatFileAbove2MOAreRejected(): void
    {
        $client = static::getClient();

        $client->loginUser($this->adminUser);

        $crawler = $client->request('GET', '/admin/media/add');

        $form = $crawler->selectButton('Ajouter')->form();

        $form['media[file]']->upload(__DIR__.'/MediaContent/large_image.jpeg');
        $form['media[title]'] = 'Test Image';

        $client->submit($form);

        self::assertResponseIsUnprocessable();
    }

    /**
     * @dataProvider provideUncorrectMediaFiles
     */
    public function testThatFormRefusesUncorrectExtensions(string $media): void
    {
        $client = static::getClient();

        $client->loginUser($this->adminUser);

        $crawler = $client->request('GET', '/admin/media/add');

        $form = $crawler->selectButton('Ajouter')->form();

        $form['media[file]']->upload(__DIR__.'/MediaContent/' . $media);
        $form['media[title]'] = 'Test Image';

        $client->submit($form);

        self::assertResponseIsUnprocessable();
    }

}
