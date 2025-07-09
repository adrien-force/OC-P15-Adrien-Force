<?php

namespace App\Tests\Fonctionnal\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationFormSubmission(): void
    {
        $client = static::createClient();
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
    }
}