<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public function __construct(
        private readonly Generator $faker,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        for ($users = [], $i = 0; $i < 400; ++$i) {
            /** @var User[] $users */
            $ranInt = $this->faker->randomNumber();
            $lastnameRan = $this->faker->lastName();

            $user = (new User())
                ->setName($name = $this->faker->userName)
                ->setEmail(sprintf('%s%d%s@mail.com', $name, $ranInt, $lastnameRan))
                ->setIsGuest(0 === $i % 2)
            ;
            //Set password without hashing for base user to reduce CPU utilization on large fixture set
            $user->setPassword('password');
            $users[] = $user;
        }

        $userAdmin = (new User())
            ->setName('Ina')
            ->setEmail('ina@zaoui.com')
            ->addRole(User::ADMIN_ROLE)
        ;
        $userAdmin->setPassword($this->hasher->hashPassword($userAdmin, 'password'));
        $users[] = $userAdmin;

        foreach ($users as $user) {
            $manager->persist($user);
        }

        $manager->flush();
    }
}
