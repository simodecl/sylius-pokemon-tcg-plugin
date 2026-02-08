<?php

declare(strict_types=1);

namespace SimoDecl\SyliusPokemonTcgPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * Creates Sylius products for sealed Pokemon TCG items.
 *
 * Sealed products include booster packs, booster boxes, elite trainer boxes,
 * collection boxes, tins, etc. These are not in the TCGdex card database,
 * so they are created manually by the shop owner but linked to a set taxon.
 */
final class SealedProductCreator
{
    public const TYPE_BOOSTER_PACK = 'booster_pack';
    public const TYPE_BOOSTER_BOX = 'booster_box';
    public const TYPE_ELITE_TRAINER_BOX = 'elite_trainer_box';
    public const TYPE_COLLECTION_BOX = 'collection_box';
    public const TYPE_TIN = 'tin';
    public const TYPE_BLISTER = 'blister';
    public const TYPE_BUNDLE = 'bundle';
    public const TYPE_OTHER = 'other';

    public const PRODUCT_TYPES = [
        self::TYPE_BOOSTER_PACK => 'Booster Pack',
        self::TYPE_BOOSTER_BOX => 'Booster Box',
        self::TYPE_ELITE_TRAINER_BOX => 'Elite Trainer Box',
        self::TYPE_COLLECTION_BOX => 'Collection Box',
        self::TYPE_TIN => 'Tin',
        self::TYPE_BLISTER => 'Blister Pack',
        self::TYPE_BUNDLE => 'Bundle',
        self::TYPE_OTHER => 'Other',
    ];

    public function __construct(
        private readonly TaxonomyImporter $taxonomyImporter,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly FactoryInterface $productFactory,
        private readonly FactoryInterface $productVariantFactory,
        private readonly FactoryInterface $productTaxonFactory,
        private readonly FactoryInterface $channelPricingFactory,
        private readonly RepositoryInterface $channelRepository,
        private readonly RepositoryInterface $taxonRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $defaultLocale,
        private readonly string $rootTaxonCode,
    ) {
    }

    /**
     * Create a sealed product.
     *
     * @param string      $name          Product name (e.g., "Obsidian Flames Booster Box")
     * @param string      $type          Product type (one of the TYPE_ constants)
     * @param string|null $setId         Optional TCGdex set ID to link to
     * @param int|null    $priceCents    Price in cents
     * @param string|null $description   Product description
     */
    public function create(
        string $name,
        string $type = self::TYPE_OTHER,
        ?string $setId = null,
        ?int $priceCents = null,
        ?string $description = null,
    ): ProductInterface {
        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();

        $code = $this->generateCode($name, $type, $setId);

        // Check if already exists
        $existing = $this->productRepository->findOneBy(['code' => $code]);
        if ($existing !== null) {
            return $existing;
        }

        $product->setCode($code);
        $product->setCurrentLocale($this->defaultLocale);
        $product->setFallbackLocale($this->defaultLocale);
        $product->setName($name);
        $product->setSlug($this->generateSlug($code));

        $typeLabel = self::PRODUCT_TYPES[$type] ?? 'Sealed Product';
        $product->setShortDescription($typeLabel);
        $product->setDescription($description ?? sprintf('%s - %s', $name, $typeLabel));

        // Link to set taxon if provided
        if ($setId !== null) {
            $this->linkToSetTaxon($product, $setId);
        }

        // Link to sealed products taxon
        $this->linkToSealedTaxon($product);

        // Create a single variant (sealed products don't need language variants)
        $this->createDefaultVariant($product, $priceCents);

        // Enable for all channels
        $this->enableForAllChannels($product);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    private function linkToSetTaxon(ProductInterface $product, string $setId): void
    {
        $setTaxon = $this->taxonomyImporter->findSetTaxon($setId);
        if ($setTaxon === null) {
            // Try to import it
            try {
                $setTaxon = $this->taxonomyImporter->importSet($setId);
            } catch (\RuntimeException) {
                return;
            }
        }

        /** @var ProductTaxonInterface $productTaxon */
        $productTaxon = $this->productTaxonFactory->createNew();
        $productTaxon->setTaxon($setTaxon);
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);
        $product->setMainTaxon($setTaxon);
    }

    private function linkToSealedTaxon(ProductInterface $product): void
    {
        $sealedTaxonCode = $this->rootTaxonCode . '-sealed';
        $sealedTaxon = $this->taxonRepository->findOneBy(['code' => $sealedTaxonCode]);

        if ($sealedTaxon === null) {
            // Create the "Sealed Products" taxon under the root
            $rootTaxon = $this->taxonRepository->findOneBy(['code' => $this->rootTaxonCode]);
            if ($rootTaxon === null) {
                return;
            }

            /** @var TaxonInterface $sealedTaxon */
            $sealedTaxon = $this->taxonRepository->createNew();
            $sealedTaxon->setCode($sealedTaxonCode);
            $sealedTaxon->setCurrentLocale($this->defaultLocale);
            $sealedTaxon->setFallbackLocale($this->defaultLocale);
            $sealedTaxon->setName('Sealed Products');
            $sealedTaxon->setSlug('pokemon-tcg-sealed');
            $sealedTaxon->setParent($rootTaxon);
            $sealedTaxon->setDescription('Sealed Pokemon TCG products (booster boxes, packs, tins, etc.)');

            $this->entityManager->persist($sealedTaxon);
        }

        /** @var ProductTaxonInterface $productTaxon */
        $productTaxon = $this->productTaxonFactory->createNew();
        $productTaxon->setTaxon($sealedTaxon);
        $productTaxon->setProduct($product);
        $product->addProductTaxon($productTaxon);
    }

    private function createDefaultVariant(ProductInterface $product, ?int $priceCents): void
    {
        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantFactory->createNew();
        $variant->setCode($product->getCode() . '-default');
        $variant->setCurrentLocale($this->defaultLocale);
        $variant->setName($product->getName());
        $variant->setOnHand(0);
        $variant->setTracked(true);

        if ($priceCents !== null) {
            /** @var ChannelInterface $channel */
            foreach ($this->channelRepository->findAll() as $channel) {
                /** @var ChannelPricingInterface $channelPricing */
                $channelPricing = $this->channelPricingFactory->createNew();
                $channelPricing->setChannelCode($channel->getCode());
                $channelPricing->setPrice($priceCents);
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

    private function generateCode(string $name, string $type, ?string $setId): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

        return sprintf('ptcg-sealed-%s-%s%s',
            $type,
            $setId ? $setId . '-' : '',
            substr($slug, 0, 40),
        );
    }

    private function generateSlug(string $code): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $code));
    }
}
