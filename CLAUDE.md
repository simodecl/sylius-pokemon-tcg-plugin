# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Docker Environment (Recommended)
```bash
# Initialize Docker environment and install dependencies
make init

# Initialize database and run migrations
make database-init

# Load fixtures (optional)
make load-fixtures

# Start/stop containers
make up
make down

# Access containers
make php-shell
make node-shell
```

### Traditional Development
```bash
# Frontend setup
(cd vendor/sylius/test-application && yarn install)
(cd vendor/sylius/test-application && yarn build)
vendor/bin/console assets:install

# Database setup
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n

# Start server
symfony server:start -d
```

### Testing
```bash
# PHPUnit tests
vendor/bin/phpunit
make phpunit  # Docker

# Behat tests (non-JS)
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
make behat  # Docker

# Behat tests (JS scenarios)
# Requires Chrome headless and symfony server
APP_ENV=test symfony server:start --port=8080 --daemon
vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
```

### Code Quality
```bash
# PHPStan analysis
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
make phpstan  # Docker

# Coding standards
vendor/bin/ecs check
make ecs  # Docker
```

### Composer Scripts
```bash
# Database reset with fixtures
composer run database-reset

# Frontend rebuild
composer run frontend-clear

# Complete test app initialization
composer run test-app-init
```

## Architecture

This is a **Sylius Pokemon TCG Plugin** — manages Pokemon Trading Card Game catalog via the TCGdex API.

### Core Structure
- **Main Plugin Class**: `src/SimoDeclSyliusPokemonTcgPlugin.php` - Entry point using `SyliusPluginTrait`
- **DI Extension**: `src/DependencyInjection/SimoDeclSyliusPokemonTcgExtension.php` - Handles service loading, config, and Doctrine migrations
- **API Client**: `src/Api/TcgdexClient.php` - Wrapper around TCGdex PHP SDK
- **Services**: `src/Service/` - TaxonomyImporter, CardProductCreator, SealedProductCreator
- **Controllers**: `src/Controller/Admin/` - Dashboard, Import, CardSearch, SealedProduct
- **Service Config**: `config/services.xml` - Service definitions with XML configuration
- **Routes**: `config/routes/admin.yaml` - Admin route definitions
- **Templates**: `templates/admin/pokemon_tcg/` - Twig templates extending Sylius admin layout

### Key Features
- **TCGdex Integration**: Fetches series, sets, cards from TCGdex API (no API key needed)
- **Taxonomy Import**: Creates taxon tree: Pokemon TCG → Series → Sets
- **Card Products**: Creates products from cards with language variants
- **Sealed Products**: Booster boxes, packs, tins, ETBs with set linking
- **Test Application**: Uses `sylius/test-application` for plugin testing in isolation
- **Code Quality**: PHPStan, ECS (Easy Coding Standard), and Rector integration

### Development Environment
- **Docker**: Complete containerized environment with PHP, Node.js, and database
- **Traditional**: Local Symfony server with manual dependency management
- **Frontend**: Yarn-based asset pipeline through test application

### Testing Strategy
- **Unit/Integration**: PHPUnit for isolated component testing
- **Functional**: Behat for feature testing with browser automation
- **Static Analysis**: PHPStan for type checking and code quality
- **Standards**: ECS for coding standard enforcement

### Database Configuration
Database credentials should be configured in:
- `tests/TestApplication/.env` (for development)
- `tests/TestApplication/.env.test` (for testing)

## AI Development Guides

This project includes specialized AI guides to assist with common plugin development tasks:

- **CLEANUP_GUIDE.md** - Guidelines for cleaning up and organizing plugin code
- **RENAME_GUIDE.md** - Step-by-step instructions for renaming plugins and components
- **COMPATIBILITY_GUIDE.md** - Best practices for maintaining compatibility across different Sylius versions

These guides provide detailed instructions and automated workflows to help maintain code quality and ensure proper plugin structure.
