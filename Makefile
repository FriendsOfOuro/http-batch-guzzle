PHP=docker compose exec php

.PHONY: up
up:
	docker compose up -d --build

.PHONY: down
down:
	docker compose down

.PHONY: logs
logs:
	docker compose logs -f

.PHONY: install
install:
	$(PHP) composer install --no-interaction

.PHONY: cs-fixer-ci
cs-fixer-ci:
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run -v --diff

.PHONY: cs-fixer
cs-fixer:
	$(PHP) vendor/bin/php-cs-fixer fix -v

.PHONY: test-coverage
test-coverage:
	$(PHP) vendor/bin/phpunit --coverage-text --testdox

.PHONY: test
test:
	$(PHP) vendor/bin/phpunit --testdox

.PHONY: phpstan
phpstan:
	$(PHP) vendor/bin/phpstan analyse -v

.PHONY: rector
rector:
	$(PHP) vendor/bin/rector process

.PHONY: bash
bash:
	@$(PHP) bash
