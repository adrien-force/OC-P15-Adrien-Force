<?php

namespace Repository;

use App\Entity\Album;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AlbumRepositoryTest extends KernelTestCase
{
    private AlbumRepository $albumRepository;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
    }

    public function testFindAllPaginatedWithoutCriteria(): void
    {
        $results = $this->albumRepository->findAllPaginated();
        
        $this->assertContainsOnlyInstancesOf(Album::class, $results);
    }

    public function testFindAllPaginatedWithCriteria(): void
    {
        $testAlbum = new Album();
        $testAlbum->setName('Test Album for Criteria');
        
        $this->entityManager->persist($testAlbum);
        $this->entityManager->flush();

        $results = $this->albumRepository->findAllPaginated(['name' => 'Test Album for Criteria']);
        
        $this->assertGreaterThan(0, count($results));
        $this->assertEquals('Test Album for Criteria', $results[0]->getName());
    }

    public function testFindAllPaginatedWithMultipleCriteria(): void
    {
        $testAlbum = new Album();
        $testAlbum->setName('Multi Criteria Album');
        
        $this->entityManager->persist($testAlbum);
        $this->entityManager->flush();

        $albumId = $testAlbum->getId();

        $results = $this->albumRepository->findAllPaginated([
            'name' => 'Multi Criteria Album',
            'id' => $albumId
        ]);
        
        $this->assertGreaterThan(0, count($results));
        $this->assertEquals('Multi Criteria Album', $results[0]->getName());
        $this->assertEquals($albumId, $results[0]->getId());
    }

    public function testFindAllPaginatedWithCustomOrderBy(): void
    {
        $album1 = new Album();
        $album1->setName('Album A');
        
        $album2 = new Album();
        $album2->setName('Album B');
        
        $this->entityManager->persist($album1);
        $this->entityManager->persist($album2);
        $this->entityManager->flush();

        $results = $this->albumRepository->findAllPaginated([], ['name' => 'DESC']);
        
        $this->assertGreaterThanOrEqual(2, count($results));
        
        $names = array_map(static fn ($album) => $album->getName(), $results);
        $albumBIndex = array_search('Album B', $names, true);
        $albumAIndex = array_search('Album A', $names, true);
        
        if ($albumBIndex !== false && $albumAIndex !== false) {
            $this->assertLessThan($albumAIndex, $albumBIndex, 'Album B should come before Album A in DESC order');
        }
    }

    public function testFindAllPaginatedWithLimitAndOffset(): void
    {
        $firstPage = $this->albumRepository->findAllPaginated([], ['id' => 'ASC'], 2);
        $secondPage = $this->albumRepository->findAllPaginated([], ['id' => 'ASC'], 2, 2);
        
        $this->assertLessThanOrEqual(2, count($firstPage));
        $this->assertLessThanOrEqual(2, count($secondPage));
        
        if (count($firstPage) > 0 && count($secondPage) > 0) {
            $firstPageIds = array_map(static fn ($album) => $album->getId(), $firstPage);
            $secondPageIds = array_map(static fn ($album) => $album->getId(), $secondPage);
            $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds), 'Pages should not overlap');
        }
    }

    public function testCountWithCriteriaWithoutCriteria(): void
    {
        $count = $this->albumRepository->countWithCriteria();
        
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountWithCriteriaWithCriteria(): void
    {
        $testAlbum = new Album();
        $testAlbum->setName('Unique Count Test Album');
        
        $this->entityManager->persist($testAlbum);
        $this->entityManager->flush();

        $count = $this->albumRepository->countWithCriteria(['name' => 'Unique Count Test Album']);
        
        $this->assertEquals(1, $count);
    }

    public function testCountWithCriteriaWithMultipleCriteria(): void
    {
        $testAlbum = new Album();
        $testAlbum->setName('Multi Count Album');
        
        $this->entityManager->persist($testAlbum);
        $this->entityManager->flush();

        $albumId = $testAlbum->getId();

        $count = $this->albumRepository->countWithCriteria([
            'name' => 'Multi Count Album',
            'id' => $albumId
        ]);
        
        $this->assertEquals(1, $count);
    }

    public function testCountWithCriteriaWithNonExistentCriteria(): void
    {
        $count = $this->albumRepository->countWithCriteria(['name' => 'This Album Does Not Exist']);
        
        $this->assertEquals(0, $count);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
