.DEFAULT_GOAL := help

PHP      ?= php
COMPOSER ?= composer

.PHONY: help install update outdated test phpstan cs cs-check rector rector-check quality ci clean

help: ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage: make \033[36m<target>\033[0m\n\nTargets:\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

install: ## Install composer dependencies
	$(COMPOSER) install --no-interaction --no-progress

update: ## Update all composer dependencies
	$(COMPOSER) update --no-interaction --no-progress

outdated: ## Show outdated composer dependencies
	$(COMPOSER) outdated --direct

test: ## Run phpunit
	$(PHP) vendor/bin/phpunit

phpstan: ## Run phpstan at level max
	$(PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=512M

cs: ## Fix code style
	$(PHP) vendor/bin/php-cs-fixer fix

cs-check: ## Check code style without modifying files
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

rector: ## Apply rector refactors
	$(PHP) vendor/bin/rector process

rector-check: ## Preview rector refactors without modifying files
	$(PHP) vendor/bin/rector process --dry-run

quality: rector cs phpstan test ## Run all quality tools

ci: cs-check phpstan rector-check test ## Run the full CI pipeline locally

clean: ## Remove vendor and cache directories
	rm -rf vendor .phpstan.cache .phpunit.cache .php-cs-fixer.cache
