<?php

namespace Fonctionnal\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    private UserRepository $userRepository;
    private AlbumRepository $albumRepository;
    private MediaRepository $mediaRepository;

    public function setUp(): void
    {
        parent::setUp();
        static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->albumRepository = static::getContainer()->get(AlbumRepository::class);
        $this->mediaRepository = static::getContainer()->get(MediaRepository::class);
    }

    private function getTestClient(): KernelBrowser
    {
        $client = static::getClient();
        assert($client instanceof KernelBrowser);

        return $client;
    }

    public function testHomePage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testGuestsPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/guests');
        self::assertResponseIsSuccessful();
    }

    public function guestsPaginationProvider(): \Generator
    {
        yield 'default pagination' => [
            'url' => '/guests',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'first page with limit 15' => [
            'url' => '/guests?page=1&limit=15',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'first page with limit 30' => [
            'url' => '/guests?page=1&limit=30',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'first page with limit 50' => [
            'url' => '/guests?page=1&limit=50',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'first page with limit 100' => [
            'url' => '/guests?page=1&limit=100',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'second page pagination' => [
            'url' => '/guests?page=2&limit=15',
            'expectedElements' => ['search-form', 'guests-list'],
        ];

        yield 'search with pagination' => [
            'url' => '/guests?search=test&page=1&limit=15',
            'expectedElements' => ['search-form', 'clear-search'],
        ];

        yield 'search without results' => [
            'url' => '/guests?search=nonexistentuser123',
            'expectedElements' => ['search-form', 'no-guests-message', 'clear-search'],
        ];
    }

    /**
     * @dataProvider guestsPaginationProvider
     */
    public function testGuestsPagination(string $url, array $expectedElements): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        foreach ($expectedElements as $element) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="'.$element.'"]')->count(),
                sprintf('Element with data-testid="%s" should be present on page %s', $element, $url)
            );
        }
    }

    public function testGuestsPaginationControls(): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/guests?page=1&limit=15');
        self::assertResponseIsSuccessful();

        $totalGuests = $this->userRepository->countWithCriteria(['isGuest' => true]);
        $totalPages = max(1, (int) ceil($totalGuests / 15));

        if ($totalPages > 1) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="guests-pagination"]')->count(),
                'Guests pagination should be visible when there are multiple pages'
            );

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="guests-pagination-next"]')->count(),
                'Next button should be present on first page when multiple pages exist'
            );
        }

        if ($totalPages > 2) {
            $crawler = $client->request('GET', '/guests?page=2&limit=15');
            self::assertResponseIsSuccessful();

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="guests-pagination-prev"]')->count(),
                'Previous button should be present on page 2'
            );

            if ($totalPages > 2) {
                self::assertGreaterThan(
                    0,
                    $crawler->filter('[data-testid="guests-pagination-next"]')->count(),
                    'Next button should be present on page 2 when more pages exist'
                );
            }
        }
    }

    public function testGuestsLimitFormVisibility(): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/guests?page=1&limit=15');
        self::assertResponseIsSuccessful();

        $totalGuests = $this->userRepository->countWithCriteria(['isGuest' => true]);
        $totalPages = max(1, (int) ceil($totalGuests / 15));

        if ($totalPages > 1) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="limit-form"]')->count(),
                'Limit form should be present when there are multiple pages'
            );

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="limit-select"]')->count(),
                'Limit select should be present when there are multiple pages'
            );
        } else {
            self::assertSame(
                0,
                $crawler->filter('[data-testid="limit-form"]')->count(),
                'Limit form should not be present when there is only one page'
            );
        }
    }

    public function testGuestsSearchFunctionality(): void
    {
        $guests = $this->userRepository->findAllGuestUsers();
        if (empty($guests)) {
            self::markTestSkipped('No guest users found for search testing.');
        }

        $firstGuest = $guests[0];
        $searchTerm = substr($firstGuest->getName(), 0, 3);

        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/guests?search='.$searchTerm);
        self::assertResponseIsSuccessful();

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="search-input"]')->count(),
            'Search input should be present'
        );

        self::assertSame(
            $searchTerm,
            $crawler->filter('[data-testid="search-input"]')->attr('value'),
            'Search input should contain the search term'
        );

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="clear-search"]')->count(),
            'Clear search button should be present when search is active'
        );
    }

    public function testGuestsEmptySearchResult(): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/guests?search=nonexistentuser123456789');
        self::assertResponseIsSuccessful();

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="no-guests-message"]')->count(),
            'No guests message should be displayed when search returns no results'
        );

        self::assertSame(
            0,
            $crawler->filter('[data-testid="guests-list"]')->count(),
            'Guests list should not be present when there are no results'
        );

        self::assertSame(
            0,
            $crawler->filter('[data-testid="guests-pagination"]')->count(),
            'Pagination should not be displayed when there are no results'
        );
    }

    public function testGuestPage(): void
    {
        $client = $this->getTestClient();
        $guest = $this->userRepository->findAllGuestUsers()[0] ?? null;
        if ($guest) {
            $client->request('GET', '/guest/'.$guest->getId());
            self::assertResponseIsSuccessful();
        } else {
            self::markTestSkipped('No guest user found.');
        }
    }

    public function testPortfolioPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/portfolio');
        self::assertResponseIsSuccessful();

        $album = $this->albumRepository->findOneBy([]);
        if ($album) {
            $client->request('GET', '/portfolio/'.$album->getId());
            self::assertResponseIsSuccessful();
        } else {
            self::markTestSkipped('No album found.');
        }
    }

    public function portfolioPaginationProvider(): \Generator
    {
        yield 'default pagination' => [
            'url' => '/portfolio',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'first page with limit 9' => [
            'url' => '/portfolio?page=1&limit=9',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'first page with limit 15' => [
            'url' => '/portfolio?page=1&limit=15',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'first page with limit 24' => [
            'url' => '/portfolio?page=1&limit=24',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'first page with limit 36' => [
            'url' => '/portfolio?page=1&limit=36',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'second page media pagination' => [
            'url' => '/portfolio?page=2&limit=15',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'second page album pagination' => [
            'url' => '/portfolio?albumPage=2',
            'expectedElements' => ['album-navigation'],
        ];

        yield 'combined pagination - second album page and second media page' => [
            'url' => '/portfolio?albumPage=2&page=2&limit=9',
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];
    }

    /**
     * @dataProvider portfolioPaginationProvider
     */
    public function testPortfolioPagination(string $url, array $expectedElements): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        foreach ($expectedElements as $element) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="'.$element.'"]')->count(),
                sprintf('Element with data-testid="%s" should be present on page %s', $element, $url)
            );
        }
    }

    public function portfolioSpecificAlbumProvider(): \Generator
    {
        yield 'specific album default pagination' => [
            'pageParams' => [],
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'specific album first page' => [
            'pageParams' => ['page' => 1, 'limit' => 15],
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'specific album second page' => [
            'pageParams' => ['page' => 2, 'limit' => 9],
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];

        yield 'specific album high limit' => [
            'pageParams' => ['page' => 1, 'limit' => 36],
            'expectedElements' => ['album-navigation', 'media-grid'],
        ];
    }

    /**
     * @dataProvider portfolioSpecificAlbumProvider
     */
    public function testPortfolioWithSpecificAlbum(array $pageParams, array $expectedElements): void
    {
        $album = $this->albumRepository->findOneBy([]);
        if (!$album) {
            self::markTestSkipped('No album found.');
            return;
        }

        $client = $this->getTestClient();
        $url = '/portfolio/'.$album->getId();
        if (!empty($pageParams)) {
            $url .= '?'.http_build_query($pageParams);
        }

        $crawler = $client->request('GET', $url);
        self::assertResponseIsSuccessful();

        foreach ($expectedElements as $element) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="'.$element.'"]')->count(),
                sprintf('Element with data-testid="%s" should be present on album page %s', $element, $url)
            );
        }

        $albumFilterExists = $crawler->filter('[data-testid="album-filter-'.$album->getId().'"]')->count() > 0;
        if ($albumFilterExists) {
            self::assertStringContainsString(
                'active',
                $crawler->filter('[data-testid="album-filter-'.$album->getId().'"]')->attr('class'),
                'Current album should have active class when visible in navigation'
            );
        }
    }

    public function testPortfolioPaginationControls(): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/portfolio?page=1&limit=9');
        self::assertResponseIsSuccessful();

        $totalMedia = $this->mediaRepository->countByAlbum(null);
        $totalPages = max(1, (int) ceil($totalMedia / 9));

        if ($totalPages > 1) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="media-pagination"]')->count(),
                'Media pagination should be visible when there are multiple pages'
            );

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="media-pagination-next"]')->count(),
                'Next button should be present on first page when multiple pages exist'
            );
        }

        if ($totalPages > 2) {
            $crawler = $client->request('GET', '/portfolio?page=2&limit=9');
            self::assertResponseIsSuccessful();

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="media-pagination-prev"]')->count(),
                'Previous button should be present on page 2'
            );

            if ($totalPages > 2) {
                self::assertGreaterThan(
                    0,
                    $crawler->filter('[data-testid="media-pagination-next"]')->count(),
                    'Next button should be present on page 2 when more pages exist'
                );
            }
        }
    }

    public function testPortfolioAlbumPaginationControls(): void
    {
        $totalAlbums = $this->albumRepository->countWithCriteria([]);
        $totalAlbumPages = max(1, (int) ceil($totalAlbums / 6));

        if ($totalAlbumPages <= 1) {
            self::markTestSkipped('Not enough albums to test album pagination.');
        }

        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/portfolio?albumPage=1');
        self::assertResponseIsSuccessful();

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="album-pagination-next"]')->count(),
            'Album next button should be present on first album page'
        );

        $crawler = $client->request('GET', '/portfolio?albumPage=2');
        self::assertResponseIsSuccessful();

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="album-pagination-prev"]')->count(),
            'Album previous button should be present on page 2'
        );
    }

    public function testPortfolioEmptyStateWhenNoMedia(): void
    {
        $albums = $this->albumRepository->findAll();
        $emptyAlbum = null;

        foreach ($albums as $album) {
            $mediaCount = $this->mediaRepository->countByAlbum($album);
            if ($mediaCount === 0) {
                $emptyAlbum = $album;
                break;
            }
        }

        if (!$emptyAlbum) {
            self::markTestSkipped('No empty album found for testing empty state.');
        }

        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/portfolio/'.$emptyAlbum->getId());
        self::assertResponseIsSuccessful();

        self::assertGreaterThan(
            0,
            $crawler->filter('[data-testid="no-media-message"]')->count(),
            'Empty state message should be displayed when album has no media'
        );

        self::assertSame(
            0,
            $crawler->filter('[data-testid="media-pagination"]')->count(),
            'Pagination should not be displayed when there are no media'
        );
    }

    public function testPortfolioLimitFormVisibility(): void
    {
        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/portfolio?page=1&limit=9');
        self::assertResponseIsSuccessful();

        $totalMedia = $this->mediaRepository->countByAlbum(null);
        $totalPages = max(1, (int) ceil($totalMedia / 9));

        if ($totalPages > 1) {
            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="limit-form"]')->count(),
                'Limit form should be present when there are multiple pages'
            );

            self::assertGreaterThan(
                0,
                $crawler->filter('[data-testid="limit-select"]')->count(),
                'Limit select should be present when there are multiple pages'
            );
        } else {
            self::assertSame(
                0,
                $crawler->filter('[data-testid="limit-form"]')->count(),
                'Limit form should not be present when there is only one page'
            );
        }
    }

    public function testAboutPage(): void
    {
        $client = $this->getTestClient();
        $client->request('GET', '/about');
        self::assertResponseIsSuccessful();
    }
}
