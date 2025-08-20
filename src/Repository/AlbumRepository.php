<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(mixed[] $criteria, string[] $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(mixed[] $criteria, string[] $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

    /**
     * @param array<string, string> $criteria
     * @param array<string, string> $orderBy
     *
     * @return Album[]
     */
    public function findAllPaginated(array $criteria = [], array $orderBy = ['id' => 'ASC'], int $limit = 10, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("a.$field = :$field")
                ->setParameter($field, $value);
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("a.$field", $direction);
        }

        /** @var Album[] $result */
        $result = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @param array<string, string> $criteria
     */
    public function countWithCriteria(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('count(a.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("a.$field = :$field")
                ->setParameter($field, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
