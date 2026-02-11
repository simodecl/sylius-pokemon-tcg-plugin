<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use SimoDecl\SyliusPokemonTcgPlugin\Api\TcgdexClient;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use TCGdex\Model\Card;

/**
 * Creates Sylius products from Pokemon TCG cards fetched via TCGdex.
 *
 * Each card becomes a Product with:
 * - Name: card name + set name + card number
 * - Variants: one per card language the seller offers
 * - Taxon: linked to the card's set (and therefore series)
 * - Description: rarity, illustrator, type, HP, etc.
 */
final class CardProductCreator
{
    public function __construct(
        private readonly TcgdexClient $tcgdexClient,
        private readonly TaxonomyImporter $taxonomyImporter,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly FactoryInterface $productFactory,
        private readonly FactoryInterface $productVariantFactory,
        private readonly FactoryInterface $productTaxonFactory,
        private readonly FactoryInterface $channelPricingFactory,
        private readonly FactoryInterface $productOptionFactory,
        private readonly FactoryInterface $productOptionValueFactory,
        private readonly RepositoryInterface $productOptionRepository,
        private readonly RepositoryInterface $channelRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $defaultLocale,
        /** @var array<string> */
        private readonly array $cardLanguages,
    ) {
    }

    /**
     * Create a product from a TCGdex card ID (e.g., "swsh3-136").
     */
    public function createFromCard(string $cardId, ?int $defaultPriceCents = null): ProductInterface
    {
        $existingCode = $this->makeProductCode($cardId);
        $existing = $this->productRepository->findOneBy(['code' => $existingCode]);
        if ($existing !== null) {
            return $existing;
        }

        $cardData = $this->tcgdexClient->fetchCard($cardId);
        if ($cardData === null) {
            throw new \RuntimeException(sprintf('Card "%s" not found in TCGdex API.', $cardId));
        }

        return $this->createProductFromCardData($cardData, $defaultPriceCents);
    }

    /**
     * Create products for all cards in a given set.
     *
     * @return array{created: int, skipped: int}
     */
    public function createFromSet(string $setId, ?int $defaultPriceCents = null): array
    {
        $setData = $this->tcgdexClient->fetchSet($setId);
        if ($setData === null) {
            throw new \RuntimeException(sprintf('Set "%s" not found in TCGdex API.', $setId));
        }

        $created = 0;
        $skipped = 0;

        foreach ($setData->cards as $cardSummary) {
            $fullCardId = $cardSummary->id !== '' ? $cardSummary->id : sprintf('%s-%s', $setId, $cardSummary->localId);

            $existingCode = $this->makeProductCode($fullCardId);
            if ($this->productRepository->findOneBy(['code' => $existingCode]) !== null) {
                $skipped++;

                continue;
            }

            $cardData = $this->tcgdexClient->fetchCard($fullCardId);
            if ($cardData === null) {
                $skipped++;

                continue;
            }

            $this->createProductFromCardData($cardData, $defaultPriceCents);
            $created++;

            // Flush in batches to avoid memory issues
            if (($created + $skipped) % 50 === 0) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function createProductFromCardData(Card $cardData, ?int $defaultPriceCents): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();

        $code = $this->makeProductCode($cardData->id);
        $product->setCode($code);
        $product->setCurrentLocale($this->defaultLocale);
        $product->setFallbackLocale($this->defaultLocale);

        // Build product name: "Charizard VMAX (Darkness Ablaze 020/189)"
        $setName = $cardData->set !== null ? $cardData->set->name : 'Unknown Set';
        $localId = $cardData->localId;
        $cardName = $cardData->name;
        $product->setName(sprintf('%s (%s %s)', $cardName, $setName, $localId));

        $product->setSlug($this->generateSlug($code));
        $product->setDescription($this->buildCardDescription($cardData));
        $product->setShortDescription($this->buildShortDescription($cardData));

        $this->linkToSetTaxon($product, $cardData);

        // Ensure the card language option exists and link it
        $languageOption = $this->getOrCreateCardLanguageOption();
        $product->addOption($languageOption);

        // Create a variant for each configured card language
        foreach ($this->cardLanguages as $language) {
            $this->createLanguageVariant($product, $languageOption, $language, $defaultPriceCents);
        }

        $this->enableForAllChannels($product);

        $this->entityManager->persist($product);

        return $product;
    }

    private function linkToSetTaxon(ProductInterface $product, Card $cardData): void
    {
        $setId = $cardData->set !== null ? $cardData->set->id : null;
        if ($setId === null || $setId === '') {
            return;
        }

        $setTaxon = $this->taxonomyImporter->findSetTaxon($setId);
        if ($setTaxon === null) {
            $setTaxon = $this->taxonomyImporter->importSet($setId);
        }

        /** @var ProductTaxonInterface $productTaxon */
        $productTaxon = $this->productTaxonFactory->createNew();
        $productTaxon->setTaxon($setTaxon);
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);
        $product->setMainTaxon($setTaxon);
    }

    private function getOrCreateCardLanguageOption(): ProductOptionInterface
    {
        $existing = $this->productOptionRepository->findOneBy(['code' => 'ptcg_card_language']);
        if ($existing !== null) {
            return $existing;
        }

        /** @var ProductOptionInterface $option */
        $option = $this->productOptionFactory->createNew();
        $option->setCode('ptcg_card_language');
        $option->setCurrentLocale($this->defaultLocale);
        $option->setFallbackLocale($this->defaultLocale);
        $option->setName('Card Language');

        $languageLabels = [
            'en' => 'English',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh-tw' => 'Chinese (Traditional)',
            'zh-cn' => 'Chinese (Simplified)',
        ];

        foreach ($this->cardLanguages as $lang) {
            /** @var ProductOptionValueInterface $value */
            $value = $this->productOptionValueFactory->createNew();
            $value->setCode(sprintf('ptcg_lang_%s', $lang));
            $value->setCurrentLocale($this->defaultLocale);
            $value->setFallbackLocale($this->defaultLocale);
            $value->setValue($languageLabels[$lang] ?? strtoupper($lang));
            $option->addValue($value);
        }

        $this->entityManager->persist($option);

        return $option;
    }

    private function createLanguageVariant(
        ProductInterface $product,
        ProductOptionInterface $languageOption,
        string $language,
        ?int $defaultPriceCents,
    ): void {
        $optionValueCode = sprintf('ptcg_lang_%s', $language);
        $optionValue = null;
        foreach ($languageOption->getValues() as $value) {
            if ($value->getCode() === $optionValueCode) {
                $optionValue = $value;

                break;
            }
        }

        if ($optionValue === null) {
            return;
        }

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantFactory->createNew();
        $variant->setCode(sprintf('%s-%s', $product->getCode(), $language));
        $variant->setCurrentLocale($this->defaultLocale);
        $variant->setName(sprintf('%s (%s)', $product->getName(), strtoupper($language)));
        $variant->addOptionValue($optionValue);
        $variant->setOnHand(0);
        $variant->setTracked(true);

        if ($defaultPriceCents !== null) {
            /** @var ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                /** @var ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
                $channelPricing->setChannelCode($channel->getCode());
                $channelPricing->setPrice($defaultPriceCents);
                $variant->addChannelPricing($channelPricing);
            }
        }

        $product->addVariant($variant);
    }

    private function enableForAllChannels(ProductInterface $product): void
    {
        /** @var ChannelInterface $channel */
        foreach ($this->channelRepository->findAll() as $channel) {
            $product->addChannel($channel);
        }
    }

    private function buildCardDescription(Card $cardData): string
    {
        $parts = [];

        if ($cardData->description !== null) {
            $parts[] = $cardData->description;
        }

        $meta = [];
        if ($cardData->rarity !== null) {
            $meta[] = sprintf('Rarity: %s', $cardData->rarity);
        }
        if ($cardData->illustrator !== null) {
            $meta[] = sprintf('Illustrator: %s', $cardData->illustrator);
        }
        if ($cardData->category !== null) {
            $meta[] = sprintf('Category: %s', $cardData->category);
        }
        if ($cardData->hp !== null) {
            $meta[] = sprintf('HP: %s', $cardData->hp);
        }
        if ($cardData->types !== null && $cardData->types !== []) {
            $meta[] = sprintf('Type(s): %s', implode(', ', $cardData->types));
        }
        if ($cardData->stage !== null) {
            $meta[] = sprintf('Stage: %s', $cardData->stage);
        }
        if ($cardData->set !== null) {
            $meta[] = sprintf('Set: %s', $cardData->set->name);
        }
        if ($cardData->localId !== '') {
            $meta[] = sprintf('Card Number: %s', $cardData->localId);
        }

        if ($meta !== []) {
            $parts[] = implode("\n", $meta);
        }

        return implode("\n\n", $parts);
    }

    private function buildShortDescription(Card $cardData): string
    {
        $parts = [];
        if ($cardData->rarity !== null) {
            $parts[] = $cardData->rarity;
        }
        if ($cardData->set !== null) {
            $parts[] = $cardData->set->name;
        }
        if ($cardData->localId !== '') {
            $parts[] = sprintf('#%s', $cardData->localId);
        }

        return implode(' | ', $parts);
    }

    private function makeProductCode(string $cardId): string
    {
        return sprintf('ptcg-card-%s', $cardId);
    }

    private function generateSlug(string $code): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $code));
    }
}
