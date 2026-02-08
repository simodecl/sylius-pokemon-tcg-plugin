# Sylius Pokemon TCG Plugin

A Sylius plugin for managing a Pokemon Trading Card Game catalog. Import card data from the [TCGdex API](https://tcgdex.dev/), create products with language variants, and manage sealed products — all from the Sylius admin panel.

Built for people who want to start their webshop to sell their Pokemon card collection.

## Features

- **Import Categories** — Import all Pokemon TCG series and sets from TCGdex as Sylius taxons (categories) with one click. Creates a tree: `Pokemon TCG → Series → Sets`.
- **Search & Create Card Products** — Search the TCGdex database by card name or browse by set. Create products with automatic set categorization and card language variants (English, Japanese, French, German, etc.).
- **Sealed Products** — Add booster packs, booster boxes, elite trainer boxes, tins, and other sealed items. Link them to their set for proper categorization.
- **Multi-Language Card Support** — Configure which card print languages you stock. Each card product gets a variant per language so you can track stock and pricing separately for English, Japanese, etc.
- **Admin Dashboard** — A dedicated Pokemon TCG section in the Sylius admin sidebar with quick access to all features.

## Requirements

- PHP 8.1+
- Sylius 1.12+ or 2.0+
- Internet access (for TCGdex API calls)

## Installation

### Step 1: Require the plugin via Composer

```bash
composer require simodecl/sylius-pokemon-tcg-plugin
```

### Step 2: Register the plugin in your kernel

Add the plugin to `config/bundles.php`:

```php
return [
    // ... other bundles
    SimoDecl\SyliusPokemonTcgPlugin\SimoDeclSyliusPokemonTcgPlugin::class => ['all' => true],
];
```

### Step 3: Import the routes

Create `config/routes/simo_decl_sylius_pokemon_tcg.yaml`:

```yaml
simo_decl_pokemon_tcg:
    resource: "@SimoDeclSyliusPokemonTcgPlugin/config/routes.yaml"
```

### Step 4: Configure the plugin (optional)

Create `config/packages/simo_decl_sylius_pokemon_tcg.yaml` to customize:

```yaml
simo_decl_sylius_pokemon_tcg:
    # Language for TCGdex API queries (en, fr, es, de, it, pt)
    api_language: en

    # Card print languages to offer as product variants
    # Add the languages of the physical cards you sell
    card_languages:
        - en
        - ja
        - fr
        - de

    # Taxon code for the root Pokemon TCG category
    root_taxon_code: pokemon-tcg
```

### Step 5: Clear the cache

```bash
bin/console cache:clear
```

## Usage

After installation, a new **Pokemon TCG** section appears in the Sylius admin sidebar.

### 1. Import Categories

Go to **Pokemon TCG → Import Categories**. You can:
- **Import All** — Imports every series and set from TCGdex in one go
- **Import by Series** — Import individual series (e.g., just "Scarlet & Violet")

This creates taxons (categories) organized as:
```
Pokemon TCG
├── Scarlet & Violet
│   ├── Obsidian Flames
│   ├── Paldea Evolved
│   └── ...
├── Sword & Shield
│   ├── Darkness Ablaze
│   └── ...
└── ...
```

### 2. Search & Add Cards

Go to **Pokemon TCG → Search Cards**:
- Search by card name (e.g., "Charizard")
- Browse by set using the dropdown
- Click a card to view its details (rarity, type, HP, illustrator, etc.)
- Click **Create Product** to add it to your catalog

Each card product is:
- Named with the card name, set, and number (e.g., "Charizard VMAX (Darkness Ablaze 020)")
- Linked to its set taxon
- Given variants for each configured card language
- Ready for you to set prices and stock

### 3. Add Sealed Products

Go to **Pokemon TCG → Sealed Products**:
- Enter a product name (e.g., "Obsidian Flames Booster Box")
- Select the product type (Booster Pack, Booster Box, Elite Trainer Box, etc.)
- Optionally link it to a set
- Set a price and description

### 4. Set Prices & Stock

After creating products through the plugin, manage them in the standard Sylius product management:
- Set prices per channel and currency
- Set stock levels per variant (per language for cards)
- Add images, manage SEO fields, etc.

## Architecture

### Directory Structure

```
src/
├── Api/
│   └── TcgdexClient.php              # TCGdex API wrapper
├── Controller/Admin/
│   ├── DashboardController.php        # Main plugin dashboard
│   ├── ImportController.php           # Taxonomy import actions
│   ├── CardSearchController.php       # Card search & product creation
│   └── SealedProductController.php    # Sealed product creation
├── DependencyInjection/
│   ├── Configuration.php              # Bundle configuration
│   └── SimoDeclSyliusPokemonTcgExtension.php
├── Form/Type/
│   └── SealedProductType.php          # Sealed product form
├── Menu/
│   └── AdminMenuListener.php          # Admin sidebar menu
├── Service/
│   ├── TaxonomyImporter.php           # Series/sets → taxon import
│   ├── CardProductCreator.php         # Card → product creation
│   └── SealedProductCreator.php       # Sealed product creation
└── SimoDeclSyliusPokemonTcgPlugin.php # Bundle class

config/
├── services.yaml                      # Service definitions
├── routes.yaml                        # Route loader
└── admin_routing.yaml                 # Admin route definitions

templates/admin/pokemon_tcg/
├── index.html.twig                    # Dashboard
├── import_taxonomies.html.twig        # Import page
├── card_search.html.twig              # Card search
├── card_view.html.twig                # Card detail/create
└── sealed_product.html.twig           # Sealed product form
```

### How It Works

- **TCGdex API**: The plugin uses the [TCGdex PHP SDK](https://github.com/tcgdex/php-sdk) to fetch Pokemon TCG data. TCGdex is a free, open-source API with data on 130k+ cards across 6+ languages.
- **Taxons**: Series and sets are imported as Sylius taxons in a tree structure. Products are linked to their set taxon.
- **Products**: Cards become products with a "Card Language" product option. Each configured language becomes a variant, so you can track stock per language.
- **Sealed Products**: Created manually via a form and linked to set taxons plus a "Sealed Products" category.

### Card Languages

Pokemon TCG cards are printed in multiple languages. Japanese sets are separate from international sets in TCGdex. Within international sets, the same card exists in English, French, German, etc.

The `card_languages` config controls which language variants are created for each card product. If you only sell English cards, set `['en']`. If you sell English and Japanese, set `['en', 'ja']`.

## TCGdex API

This plugin relies on the free [TCGdex API](https://tcgdex.dev/). TCGdex provides:
- Card data: name, rarity, type, HP, illustrator, image, and more
- Set data: name, card count, series grouping
- Series data: grouping of sets
- Multi-language support: en, fr, es, de, it, pt (and more coming)

No API key is required. Please be respectful of their service.

## License

MIT
