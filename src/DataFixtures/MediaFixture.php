<?php

namespace App\DataFixtures;

use App\Entity\Media;
use App\Repository\AlbumRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaFixture extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly AlbumRepository $albumRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $albums = $this->albumRepository->findAll();
        $medias = [];

        for ($i = 0; $i < 10; ++$i) {
            $mediaFolder = dirname(__DIR__, 2) . '/public/uploads/';
            $imageName = $i+1 . '.jpg';
            $mediaPath = $mediaFolder . $imageName;
            $relativePath = 'uploads/' . $imageName;
            $medias[] = (new Media())
                ->setTitle($this->faker->sentence())
                ->setPath($relativePath)
                ->setFile(new UploadedFile(
                    path: $mediaPath,
                    originalName: $imageName,
                ))
                ->setAlbum($albums[$this->faker->numberBetween(0, count($albums) - 1)])
            ;
        }

        foreach ($medias as $media) {
            $manager->persist($media);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AlbumFixture::class];
    }
}
