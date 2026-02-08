<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Api;

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
     * @return array<int, array{id: string, name: string, logo?: string}>
     */
    public function fetchSeries(): array
    {
        return $this->tcgdex->fetch('series') ?? [];
    }

    /**
     * Fetch a single series by ID.
     *
     * @return array{id: string, name: string, logo?: string, sets?: array<int, array>}|null
     */
    public function fetchSerie(string $serieId): ?array
    {
        return $this->tcgdex->fetch('series', $serieId);
    }

    /**
     * Fetch all sets.
     *
     * @return array<int, array{id: string, name: string, logo?: string, symbol?: string, cardCount?: array}>
     */
    public function fetchSets(): array
    {
        return $this->tcgdex->fetch('sets') ?? [];
    }

    /**
     * Fetch a single set by ID, including its card list.
     *
     * @return array{id: string, name: string, logo?: string, symbol?: string, serie: array, cardCount: array, cards: array<int, array>}|null
     */
    public function fetchSet(string $setId): ?array
    {
        return $this->tcgdex->fetch('sets', $setId);
    }

    /**
     * Fetch a single card by its global ID (e.g., "swsh3-136").
     *
     * @return array{id: string, localId: string, name: string, image?: string, category?: string, illustrator?: string, rarity?: string, variants?: array, set: array, types?: array, hp?: int, stage?: string, description?: string}|null
     */
    public function fetchCard(string $cardId): ?array
    {
        return $this->tcgdex->fetch('cards', $cardId);
    }

    /**
     * Fetch a card by set ID and local card ID (e.g., set "swsh3", card "136").
     *
     * @return array|null
     */
    public function fetchCardFromSet(string $setId, string $localId): ?array
    {
        return $this->tcgdex->fetch('sets', $setId, $localId);
    }

    /**
     * Fetch all cards (summary list).
     *
     * @return array<int, array{id: string, localId: string, name: string, image?: string}>
     */
    public function fetchCards(): array
    {
        return $this->tcgdex->fetch('cards') ?? [];
    }

    /**
     * Search for cards by name using the API's filtering.
     *
     * @return array<int, array{id: string, localId: string, name: string, image?: string}>
     */
    public function searchCards(string $query): array
    {
        $params = ['name' => $query];

        return $this->tcgdex->fetchWithParams(['cards'], $params) ?? [];
    }

    /**
     * Fetch available rarities.
     *
     * @return array<int, string>
     */
    public function fetchRarities(): array
    {
        return $this->tcgdex->fetch('rarities') ?? [];
    }

    /**
     * Fetch available card categories.
     *
     * @return array<int, string>
     */
    public function fetchCategories(): array
    {
        return $this->tcgdex->fetch('categories') ?? [];
    }

    /**
     * Fetch available pokemon types.
     *
     * @return array<int, string>
     */
    public function fetchTypes(): array
    {
        return $this->tcgdex->fetch('types') ?? [];
    }
}
