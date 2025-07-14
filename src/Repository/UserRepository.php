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
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(mixed[] $criteria, string[] $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(mixed[] $criteria, string[] $orderBy = null, $limit = null, $offset = null)
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

    /**
     * @return User[]
     */
    public function findWithoutRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('JSONB_CONTAINS(u.roles, :role) = false')
            ->setParameter('role', sprintf('"%s"', $role))
            ->getQuery()
            ->getResult();
    }

    public function findAllGuestUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isGuest = true')
            ->getQuery()
            ->getResult();
    }

    public function findAllNonGuestUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isGuest = false')
            ->getQuery()
            ->getResult();
    }

    public function findAllGuestsWithEagerMedias(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.isGuest = true')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds user with pagination
     *
     * @param array<string, string> $criteria Filtering criteria
     * @param array{id: string} $orderBy Order options
     * @param int $limit Max results
     * @param int $offset Result offset
     * @return User[] Returns an array of Media objects
     */
    public function findAllGuestUsersPaginated(array $criteria = [], array $orderBy = ['id' => 'ASC'], int $limit = 25, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.isGuest = true');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("u.$field = :$field")
               ->setParameter($field, $value);
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("u.$field", $direction);
        }

        return $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total User matching criteria
     *
     * @param array<string, string> $criteria Filtering criteria
     * @return int Total count
     */
    public function countWithCriteria(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isGuest = true');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("u.$field = :$field")
               ->setParameter($field, $value);
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

}
