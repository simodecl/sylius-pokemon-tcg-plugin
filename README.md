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

- PHP 8.2+
- Sylius 2.0+
- Internet access (for TCGdex API calls)

## Installation

### Step 1: Require the plugin via Composer

```bash
composer require simo-decl/sylius-pokemon-tcg-plugin
```

### Step 2: Register the plugin

Add to `config/bundles.php`:

```php
return [
    // ... other bundles
    SimoDecl\SyliusPokemonTcgPlugin\SimoDeclSyliusPokemonTcgPlugin::class => ['all' => true],
];
```

### Step 3: Import the routes

Create `config/routes/simo_decl_sylius_pokemon_tcg.yaml`:

```yaml
simo_decl_sylius_pokemon_tcg_admin:
    resource: "@SimoDeclSyliusPokemonTcgPlugin/config/routes/admin.yaml"
    prefix: /admin
```

### Step 4: Configure the plugin (optional)

Create `config/packages/simo_decl_sylius_pokemon_tcg.yaml`:

```yaml
simo_decl_sylius_pokemon_tcg:
    # Language for TCGdex API queries (en, fr, es, de, it, pt)
    api_language: en

    # Card print languages to offer as product variants
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

Go to **Pokemon TCG → Import Categories**:
- **Import All** — Imports every series and set from TCGdex
- **Import by Series** — Import individual series (e.g., just "Scarlet & Violet")

### 2. Search & Add Cards

Go to **Pokemon TCG → Search Cards**:
- Search by card name (e.g., "Charizard") or browse by set
- Click a card to view its details and create a product

### 3. Add Sealed Products

Go to **Pokemon TCG → Sealed Products**:
- Create booster boxes, packs, tins, ETBs, etc.
- Link to a set and set a price

### 4. Manage in Sylius

After creating products, manage prices, stock, and images in the standard Sylius product management.

## Development

This plugin was scaffolded from [Sylius/PluginSkeleton](https://github.com/Sylius/PluginSkeleton).

### Setup

```bash
# Install dependencies
composer install

# Frontend (in test application)
(cd vendor/sylius/test-application && yarn install && yarn build)
vendor/bin/console assets:install

# Database
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n
```

### Docker

```bash
cp compose.override.dist.yml compose.override.yml
docker compose up -d
```

### Testing

```bash
# PHPUnit
vendor/bin/phpunit

# Behat (non-JS)
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"

# PHPStan
vendor/bin/phpstan analyse -c phpstan.neon -l max src/

# Coding standards
vendor/bin/ecs check
```

## TCGdex API

This plugin uses the free [TCGdex API](https://tcgdex.dev/) — no API key required. Please be respectful of their service.

## License

MIT
