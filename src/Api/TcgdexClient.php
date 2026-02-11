<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Api;

use TCGdex\Model\Card;
use TCGdex\Model\CardResume;
use TCGdex\Model\Serie;
use TCGdex\Model\SerieResume;
use TCGdex\Model\Set;
use TCGdex\Model\SetResume;
use TCGdex\TCGdex;

/**
 * Wrapper around the TCGdex PHP SDK providing a convenient interface
 * for fetching Pokemon TCG data (series, sets, cards).
 */
final class TcgdexClient
{
    private TCGdex $tcgdex;

    public function __construct(
        private readonly string $defaultLanguage = 'en',
    ) {
        $this->tcgdex = new TCGdex($this->defaultLanguage);
    }

    public function setLanguage(string $language): void
    {
        $this->tcgdex = new TCGdex($language);
    }

    public function getLanguage(): string
    {
        return $this->defaultLanguage;
    }

    /**
     * Fetch all series.
     *
     * @return SerieResume[]
     */
    public function fetchSeries(): array
    {
        return $this->tcgdex->fetchSeries() ?? [];
    }

    /**
     * Fetch a single series by ID.
     */
    public function fetchSerie(string $serieId): ?Serie
    {
        return $this->tcgdex->fetchSerie($serieId);
    }

    /**
     * Fetch all sets.
     *
     * @return SetResume[]
     */
    public function fetchSets(): array
    {
        return $this->tcgdex->fetchSets() ?? [];
    }

    /**
     * Fetch a single set by ID, including its card list.
     */
    public function fetchSet(string $setId): ?Set
    {
        return $this->tcgdex->fetchSet($setId);
    }

    /**
     * Fetch a single card by its global ID (e.g., "swsh3-136").
     */
    public function fetchCard(string $cardId): ?Card
    {
        return $this->tcgdex->fetchCard($cardId);
    }

    /**
     * Fetch all cards (summary list).
     *
     * @return CardResume[]
     */
    public function fetchCards(): array
    {
        return $this->tcgdex->fetchCards() ?? [];
    }

    /**
     * Search for cards by name.
     *
     * @return CardResume[]
     */
    public function searchCards(string $query): array
    {
        $results = $this->tcgdex->fetchWithParams(['cards'], ['name' => $query]);

        if (!is_array($results)) {
            return [];
        }

        return $results;
    }
}
