<?php

namespace Fonctionnal\Controller;

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
            'registration[password][first]' => 'zklsjdqlzkjdlkqzjdkqzndjkqzhjdbqzhjd8378373££**¨¨',
            'registration[password][second]' => 'zklsjdqlzkjdlkqzjdkqzndjkqzhjdbqzhjd8378373££**¨¨',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/login');
        $client->followRedirect();
    }
}
