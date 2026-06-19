.DEFAULT_GOAL := help

PHP      ?= php
COMPOSER ?= composer

# Line-coverage floor enforced by `make coverage` and the CI coverage job.
MIN_LINE_COVERAGE := 100

# Mutation Score Indicator floors enforced by `make infection` (needs a coverage
# driver). Measured MSI is ~75% (parity with html-min); the floor starts a few
# points below to absorb timeout jitter and ratchets up as the suite hardens.
MIN_MSI         := 70
MIN_COVERED_MSI := 70

.PHONY: help install update outdated test coverage infection phpstan cs cs-check rector rector-check quality ci clean

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

coverage: ## Run phpunit with line coverage and enforce the floor (needs pcov or xdebug)
	$(PHP) -d pcov.enabled=1 vendor/bin/phpunit --coverage-clover build/coverage/clover.xml --coverage-text --only-summary-for-coverage-text
	$(PHP) bin/coverage-check.php build/coverage/clover.xml $(MIN_LINE_COVERAGE)

infection: ## Run mutation testing and enforce the MSI floor (needs pcov or xdebug)
	$(PHP) -d pcov.enabled=1 vendor/bin/infection --threads=max --no-progress --min-msi=$(MIN_MSI) --min-covered-msi=$(MIN_COVERED_MSI)

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
	rm -rf vendor build .phpstan.cache .phpunit.cache .php-cs-fixer.cache
