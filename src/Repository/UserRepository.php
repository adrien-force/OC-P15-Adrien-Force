<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @phpstan-type Criteria array<string, mixed>
 * @phpstan-type OrderBy array<string,string>
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(Criteria $criteria, OrderBy $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(Criteria $criteria, OrderBy $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findOneByRole(string $role): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('JSONB_CONTAINS(u.roles, :role) = true')
           ->setParameter('role', sprintf('"%s"', $role))
           ->getQuery()
           ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSONB_CONTAINS(u.roles, :role) = true')
            ->setParameter('role', sprintf('"%s"', $role))
            ->getQuery()
            ->getResult();
    }



}
