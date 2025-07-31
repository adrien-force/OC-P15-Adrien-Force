<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(mixed[] $criteria, string[] $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(mixed[] $criteria, string[] $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * Finds media with pagination and joins on album and user.
     *
     * @param array<string, string> $criteria Filtering criteria
     * @param array{id: string}     $orderBy  Order options
     * @param int                   $limit    Max results
     * @param int                   $offset   Result offset
     *
     * @return Media[] Returns an array of Media objects
     */
    public function findAllMediaPaginatedWithAlbumAndUser(array $criteria = [], array $orderBy = ['id' => 'ASC'], int $limit = 25, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->leftJoin('m.album', 'a')
            ->addSelect('u')
            ->addSelect('a');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("m.$field = :$field")
               ->setParameter($field, $value);
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("m.$field", $direction);
        }

        return $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total medias matching criteria.
     *
     * @param array<string, string> $criteria Filtering criteria
     *
     * @return int Total count
     */
    public function countWithCriteria(array $criteria = []): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere("m.$field = :$field")
               ->setParameter($field, $value);
        }

        return $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Media[] Returns an array of Media objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Media
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
