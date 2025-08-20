<?php

namespace Repository;

use App\Entity\Media;
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

    public function testFindByRole(): void
    {
        $adminUsers = $this->userRepository->findByRole(User::ADMIN_ROLE);

        $this->assertContainsOnlyInstancesOf(User::class, $adminUsers);

        foreach ($adminUsers as $user) {
            $this->assertContains(User::ADMIN_ROLE, $user->getRoles());
        }
    }

    public function testFindByRoleWithNonExistentRole(): void
    {
        $users = $this->userRepository->findByRole('ROLE_NONEXISTENT');

        $this->assertEmpty($users);
    }

    public function testFindWithoutRole(): void
    {
        $nonAdminUsers = $this->userRepository->findWithoutRole(User::ADMIN_ROLE);

        $this->assertContainsOnlyInstancesOf(User::class, $nonAdminUsers);

        foreach ($nonAdminUsers as $user) {
            $this->assertNotContains(User::ADMIN_ROLE, $user->getRoles());
        }
    }

    public function testFindWithoutRoleWithNonExistentRole(): void
    {
        $users = $this->userRepository->findWithoutRole('ROLE_NONEXISTENT');

        $this->assertGreaterThan(0, count($users));
    }

    public function testFindAllGuestUsers(): void
    {
        $guestUsers = $this->userRepository->findAllGuestUsers();

        $this->assertContainsOnlyInstancesOf(User::class, $guestUsers);

        foreach ($guestUsers as $user) {
            $this->assertTrue($user->isGuest());
        }
    }

    public function testFindAllNonGuestUsers(): void
    {
        $nonGuestUsers = $this->userRepository->findAllNonGuestUsers();

        $this->assertContainsOnlyInstancesOf(User::class, $nonGuestUsers);

        foreach ($nonGuestUsers as $user) {
            $this->assertFalse($user->isGuest());
        }
    }

    public function testFindAllGuestsWithEagerMedias(): void
    {
        $existingGuests = $this->userRepository->findAllGuestUsers();

        if (empty($existingGuests)) {
            $guestUser = new User();
            $guestUser->setName('Guest with Media')
                      ->setEmail('guestwithmedia@example.com')
                      ->setIsGuest(true)
                      ->setPassword('password');

            $this->entityManager->persist($guestUser);
            $this->entityManager->flush();
        } else {
            $guestUser = $existingGuests[0];
        }

        $media = new Media();
        $media->setTitle('Guest Media')
              ->setPath('uploads/guest.jpg')
              ->setUser($guestUser);

        $this->entityManager->persist($media);
        $this->entityManager->flush();

        $guestsWithMedias = $this->userRepository->findAllGuestsWithEagerMedias();

        $this->assertContainsOnlyInstancesOf(User::class, $guestsWithMedias);

        foreach ($guestsWithMedias as $user) {
            $this->assertTrue($user->isGuest());
        }

        $testGuest = null;
        foreach ($guestsWithMedias as $guest) {
            if ($guest->getId() === $guestUser->getId()) {
                $testGuest = $guest;
                break;
            }
        }

        $this->assertNotNull($testGuest, 'Test guest should be found');
        $this->assertGreaterThan(0, $testGuest->getMedias()->count());
    }

    public function testFindAllGuestUsersPaginatedWithoutSearch(): void
    {
        $results = $this->userRepository->findAllGuestUsersPaginated();

        $this->assertContainsOnlyInstancesOf(User::class, $results);

        foreach ($results as $user) {
            $this->assertTrue($user->isGuest());
        }
    }

    public function testFindAllGuestUsersPaginatedWithSearch(): void
    {
        $testGuest = new User();
        $testGuest->setName('Searchable Guest')
                  ->setEmail('searchable@example.com')
                  ->setIsGuest(true)
                  ->setPassword('password');

        $this->entityManager->persist($testGuest);
        $this->entityManager->flush();

        $results = $this->userRepository->findAllGuestUsersPaginated([], ['id' => 'ASC'], 25, 0, 'Searchable');

        $this->assertGreaterThan(0, count($results));

        $found = false;
        foreach ($results as $user) {
            if ('Searchable Guest' === $user->getName()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Test guest should be found in search results');

        $emailResults = $this->userRepository->findAllGuestUsersPaginated([], ['id' => 'ASC'], 25, 0, 'searchable@example.com');

        $this->assertGreaterThan(0, count($emailResults));
    }

    public function testFindAllGuestUsersPaginatedWithCriteria(): void
    {
        $testGuest = new User();
        $testGuest->setName('Criteria Guest')
                  ->setEmail('criteria@example.com')
                  ->setIsGuest(true)
                  ->setPassword('password');

        $this->entityManager->persist($testGuest);
        $this->entityManager->flush();

        $results = $this->userRepository->findAllGuestUsersPaginated(['name' => 'Criteria Guest']);

        $this->assertCount(1, $results);
        $this->assertEquals('Criteria Guest', $results[0]->getName());
    }

    public function testFindAllGuestUsersPaginatedWithOrderBy(): void
    {
        $results = $this->userRepository->findAllGuestUsersPaginated([], ['name' => 'DESC'], 10);

        if (count($results) > 1) {
            for ($i = 0; $i < count($results) - 1; ++$i) {
                $comparison = strcmp($results[$i]->getName(), $results[$i + 1]->getName());
                $this->assertGreaterThanOrEqual(
                    0,
                    $comparison,
                    'Results should be ordered by name DESC (first: "'.$results[$i]->getName().'" should be >= second: "'.$results[$i + 1]->getName().'")'
                );
            }
        } else {
            $this->markTestIncomplete('Need at least 2 guest users to test ordering');
        }
    }

    public function testFindAllGuestUsersPaginatedWithLimitAndOffset(): void
    {
        $firstPage = $this->userRepository->findAllGuestUsersPaginated([], ['id' => 'ASC'], 2);
        $secondPage = $this->userRepository->findAllGuestUsersPaginated([], ['id' => 'ASC'], 2, 2);

        $this->assertLessThanOrEqual(2, count($firstPage));
        $this->assertLessThanOrEqual(2, count($secondPage));

        if (count($firstPage) > 0 && count($secondPage) > 0) {
            $firstPageIds = array_map(static fn ($user) => $user->getId(), $firstPage);
            $secondPageIds = array_map(static fn ($user) => $user->getId(), $secondPage);
            $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds), 'Pages should not overlap');
        }
    }

    public function testCountWithCriteriaWithoutSearch(): void
    {
        $count = $this->userRepository->countWithCriteria();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithCriteriaWithSearch(): void
    {
        $testGuest = new User();
        $testGuest->setName('Count Search Guest')
                  ->setEmail('countsearch@example.com')
                  ->setIsGuest(true)
                  ->setPassword('password');

        $this->entityManager->persist($testGuest);
        $this->entityManager->flush();

        $count = $this->userRepository->countWithCriteria([], 'Count Search');

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountWithCriteriaWithCriteriaAndSearch(): void
    {
        $testGuest = new User();
        $testGuest->setName('Count Criteria Guest')
                  ->setEmail('countcriteria@example.com')
                  ->setIsGuest(true)
                  ->setPassword('password');

        $this->entityManager->persist($testGuest);
        $this->entityManager->flush();

        $count = $this->userRepository->countWithCriteria(['name' => 'Count Criteria Guest'], 'Criteria');

        $this->assertEquals(1, $count);
    }

    public function testFindAllNonGuestUsersPaginatedWithoutSearch(): void
    {
        $results = $this->userRepository->findAllNonGuestUsersPaginated();

        $this->assertContainsOnlyInstancesOf(User::class, $results);

        foreach ($results as $user) {
            $this->assertFalse($user->isGuest());
        }
    }

    public function testFindAllNonGuestUsersPaginatedWithSearch(): void
    {
        $testUser = new User();
        $testUser->setName('Searchable Non-Guest')
                 ->setEmail('searchablenonguest@example.com')
                 ->setIsGuest(false)
                 ->setPassword('password');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $results = $this->userRepository->findAllNonGuestUsersPaginated([], ['id' => 'ASC'], 25, 0, 'Searchable Non-Guest');

        $this->assertGreaterThan(0, count($results));

        $found = false;
        foreach ($results as $user) {
            if ('Searchable Non-Guest' === $user->getName()) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Test non-guest should be found in search results');
    }

    public function testFindAllNonGuestUsersPaginatedWithCriteria(): void
    {
        $testUser = new User();
        $testUser->setName('Criteria Non-Guest')
                 ->setEmail('criterianonguest@example.com')
                 ->setIsGuest(false)
                 ->setPassword('password');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $results = $this->userRepository->findAllNonGuestUsersPaginated(['name' => 'Criteria Non-Guest']);

        $this->assertCount(1, $results);
        $this->assertEquals('Criteria Non-Guest', $results[0]->getName());
    }

    public function testCountNonGuestUsersWithCriteriaWithoutSearch(): void
    {
        $count = $this->userRepository->countNonGuestUsersWithCriteria();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountNonGuestUsersWithCriteriaWithSearch(): void
    {
        $testUser = new User();
        $testUser->setName('Count Search Non-Guest')
                 ->setEmail('countsearchnonguest@example.com')
                 ->setIsGuest(false)
                 ->setPassword('password');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $count = $this->userRepository->countNonGuestUsersWithCriteria([], 'Count Search Non-Guest');

        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountNonGuestUsersWithCriteriaWithCriteriaAndSearch(): void
    {
        $testUser = new User();
        $testUser->setName('Count Criteria Non-Guest')
                 ->setEmail('countcriterianonguest@example.com')
                 ->setIsGuest(false)
                 ->setPassword('password');

        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        $count = $this->userRepository->countNonGuestUsersWithCriteria(['name' => 'Count Criteria Non-Guest'], 'Criteria Non-Guest');

        $this->assertEquals(1, $count);
    }

    public function testFindAllGuestUsersPaginatedIgnoresIsGuestCriteria(): void
    {
        $results = $this->userRepository->findAllGuestUsersPaginated(['isGuest' => false]);

        foreach ($results as $user) {
            $this->assertTrue($user->isGuest());
        }
    }

    public function testFindAllNonGuestUsersPaginatedIgnoresIsGuestCriteria(): void
    {
        $results = $this->userRepository->findAllNonGuestUsersPaginated(['isGuest' => true]);

        foreach ($results as $user) {
            $this->assertFalse($user->isGuest());
        }
    }

    public function testCountWithCriteriaIgnoresIsGuestCriteria(): void
    {
        $count1 = $this->userRepository->countWithCriteria();
        $count2 = $this->userRepository->countWithCriteria(['isGuest' => false]);

        $this->assertEquals($count1, $count2, 'Count should be same regardless of isGuest criteria');
    }

    public function testCountNonGuestUsersWithCriteriaIgnoresIsGuestCriteria(): void
    {
        $count1 = $this->userRepository->countNonGuestUsersWithCriteria();
        $count2 = $this->userRepository->countNonGuestUsersWithCriteria(['isGuest' => true]);

        $this->assertEquals($count1, $count2, 'Count should be same regardless of isGuest criteria');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
