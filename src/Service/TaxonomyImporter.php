<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Generator\TaxonSlugGeneratorInterface;
use TCGdex\Model\SetResume;

/**
 * Imports Pokemon TCG series and sets from TCGdex as Sylius taxons.
 *
 * Creates a taxonomy tree:
 *   Pokemon TCG (root)
 *   ├── Scarlet & Violet (series)
 *   │   ├── Obsidian Flames (set)
 *   │   └── ...
 *   ├── Sword & Shield (series)
 *   │   ├── Darkness Ablaze (set)
 *   │   └── ...
 *   └── ...
 */
final class TaxonomyImporter
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
        private readonly RepositoryInterface $taxonRepository,
        private readonly FactoryInterface $taxonFactory,
        private readonly TaxonSlugGeneratorInterface $slugGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $rootTaxonCode,
        private readonly string $defaultLocale,
    ) {
    }

    /**
     * Import all series and their sets as taxons.
     *
     * @return array{series: int, sets: int} Count of imported items
     */
    public function importAll(): array
    {
        $rootTaxon = $this->getOrCreateRootTaxon();
        $seriesCount = 0;
        $setsCount = 0;

        $allSeries = $this->tcgdexClient->fetchSeries();

        foreach ($allSeries as $serieResume) {
            $serieTaxon = $this->getOrCreateSerieTaxon($rootTaxon, $serieResume->id, $serieResume->name);
            $seriesCount++;

            $fullSerie = $this->tcgdexClient->fetchSerie($serieResume->id);
            if ($fullSerie === null) {
                continue;
            }

            foreach ($fullSerie->sets as $setResume) {
                $this->getOrCreateSetTaxon($serieTaxon, $setResume);
                $setsCount++;
            }
        }

        $this->entityManager->flush();

        return ['series' => $seriesCount, 'sets' => $setsCount];
    }

    /**
     * Import a single series and its sets.
     *
     * @return array{series: string, sets: int}
     */
    public function importSerie(string $serieId): array
    {
        $rootTaxon = $this->getOrCreateRootTaxon();
        $setsCount = 0;

        $fullSerie = $this->tcgdexClient->fetchSerie($serieId);
        if ($fullSerie === null) {
            throw new \RuntimeException(sprintf('Series "%s" not found in TCGdex API.', $serieId));
        }

        $serieTaxon = $this->getOrCreateSerieTaxon($rootTaxon, $fullSerie->id, $fullSerie->name);

        foreach ($fullSerie->sets as $setResume) {
            $this->getOrCreateSetTaxon($serieTaxon, $setResume);
            $setsCount++;
        }

        $this->entityManager->flush();

        return ['series' => $fullSerie->name, 'sets' => $setsCount];
    }

    /**
     * Import a single set.
     */
    public function importSet(string $setId): TaxonInterface
    {
        $rootTaxon = $this->getOrCreateRootTaxon();

        $fullSet = $this->tcgdexClient->fetchSet($setId);
        if ($fullSet === null) {
            throw new \RuntimeException(sprintf('Set "%s" not found in TCGdex API.', $setId));
        }

        $serieTaxon = $this->getOrCreateSerieTaxon($rootTaxon, $fullSet->serie->id, $fullSet->serie->name);
        $setTaxon = $this->getOrCreateSetTaxon($serieTaxon, $fullSet);

        $this->entityManager->flush();

        return $setTaxon;
    }

    /**
     * Find the taxon for a given set ID.
     */
    public function findSetTaxon(string $setId): ?TaxonInterface
    {
        $code = $this->makeSetTaxonCode($setId);

        return $this->taxonRepository->findOneBy(['code' => $code]);
    }

    private function getOrCreateRootTaxon(): TaxonInterface
    {
        $existing = $this->taxonRepository->findOneBy(['code' => $this->rootTaxonCode]);
        if ($existing !== null) {
            return $existing;
        }

        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode($this->rootTaxonCode);
        $taxon->setCurrentLocale($this->defaultLocale);
        $taxon->setFallbackLocale($this->defaultLocale);
        $taxon->setName('Pokemon TCG');
        $taxon->setSlug($this->slugGenerator->generate($taxon, $this->defaultLocale));
        $taxon->setDescription('Pokemon Trading Card Game products');

        $this->entityManager->persist($taxon);

        return $taxon;
    }

    private function getOrCreateSerieTaxon(TaxonInterface $rootTaxon, string $serieId, string $serieName): TaxonInterface
    {
        $code = $this->makeSerieTaxonCode($serieId);

        $existing = $this->taxonRepository->findOneBy(['code' => $code]);
        if ($existing !== null) {
            return $existing;
        }

        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode($code);
        $taxon->setCurrentLocale($this->defaultLocale);
        $taxon->setFallbackLocale($this->defaultLocale);
        $taxon->setName($serieName);
        $taxon->setParent($rootTaxon);
        $taxon->setSlug($this->slugGenerator->generate($taxon, $this->defaultLocale));

        $this->entityManager->persist($taxon);

        return $taxon;
    }

    private function getOrCreateSetTaxon(TaxonInterface $serieTaxon, SetResume $setData): TaxonInterface
    {
        $code = $this->makeSetTaxonCode($setData->id);

        $existing = $this->taxonRepository->findOneBy(['code' => $code]);
        if ($existing !== null) {
            return $existing;
        }

        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonFactory->createNew();
        $taxon->setCode($code);
        $taxon->setCurrentLocale($this->defaultLocale);
        $taxon->setFallbackLocale($this->defaultLocale);
        $taxon->setName($setData->name);
        $taxon->setParent($serieTaxon);
        $taxon->setSlug($this->slugGenerator->generate($taxon, $this->defaultLocale));

        if ($setData->cardCount !== null) {
            $taxon->setDescription(sprintf(
                'Total cards: %d | Official: %d',
                $setData->cardCount->total ?? 0,
                $setData->cardCount->official ?? 0,
            ));
        }

        $this->entityManager->persist($taxon);

        return $taxon;
    }

    private function makeSerieTaxonCode(string $serieId): string
    {
        return sprintf('ptcg-serie-%s', $serieId);
    }

    private function makeSetTaxonCode(string $setId): string
    {
        return sprintf('ptcg-set-%s', $setId);
    }
}
