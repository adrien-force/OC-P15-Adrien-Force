<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testUpgradePasswordSuccessfullyChangesPassword(): void
    {
        $testUser = $this->userRepository->findAll()[0];
        $newHashedPassword = 'a-new-super-hashed-password';

        $this->userRepository->upgradePassword($testUser, $newHashedPassword);

        $this->entityManager->clear();
        $updatedUser = $this->userRepository->find($testUser->getId());

        $this->assertSame($newHashedPassword, $updatedUser->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $unsupportedUser = $this->createMock(PasswordAuthenticatedUserInterface::class);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Instances of "Mock_PasswordAuthenticatedUserInterface');

        $this->userRepository->upgradePassword($unsupportedUser, 'some-password');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}

