# Commandes de développement de Touche pas au klaxon.
# Toutes les commandes PHP s'exécutent dans le conteneur « app ».

DC      := docker compose
EXEC    := $(DC) exec -T -u www-data app
COMPOSE_ENV := UID=$(shell id -u) GID=$(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help install up down restart ps logs sh composer-install db-reset db-shell lint lint-fix stan test test-unit test-integration coverage verify npm-install css css-watch docs

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

.env:
	cp .env.example .env
	@echo ".env créé à partir de .env.example : personnaliser les mots de passe si nécessaire."

install: .env up composer-install npm-install css ## Installe et démarre l'environnement complet

up: .env ## Construit et démarre les conteneurs
	$(COMPOSE_ENV) $(DC) up -d --build --wait

down: ## Arrête les conteneurs (les données sont conservées)
	$(DC) down

restart: down up ## Redémarre les conteneurs

ps: ## État des conteneurs
	$(DC) ps

logs: ## Journaux des conteneurs
	$(DC) logs -f --tail=100

sh: ## Ouvre un terminal dans le conteneur applicatif
	$(DC) exec -u www-data app bash

composer-install: ## Installe les dépendances PHP
	$(EXEC) composer install --no-interaction

db-reset: ## Recrée le schéma et recharge le jeu d'essais (efface les données)
	@read -p "Toutes les données seront remplacées par le jeu d'essais. Continuer ? [o/N] " answer; [ "$$answer" = "o" ]
	$(DC) exec -T db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD"' < database/01_schema.sql
	$(DC) exec -T db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD"' < database/02_seed.sql
	@echo "Base réinitialisée."

db-shell: ## Console MySQL (compte root)
	$(DC) exec db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$DB_NAME"'

lint: ## Vérifie la norme PSR-12
	$(EXEC) composer lint

lint-fix: ## Corrige automatiquement les écarts PSR-12
	$(EXEC) composer lint:fix

stan: ## Analyse statique PHPStan
	$(EXEC) composer stan

test: ## Exécute tous les tests PHPUnit
	$(EXEC) composer test

test-unit: ## Exécute les tests unitaires
	$(EXEC) composer test:unit

test-integration: ## Exécute les tests d'intégration (base de test)
	$(EXEC) composer test:integration

coverage: ## Rapport de couverture (texte et HTML dans var/coverage)
	$(EXEC) vendor/bin/phpunit --coverage-text --coverage-html var/coverage

verify: lint stan test ## Tous les contrôles qualité

npm-install: ## Installe les dépendances front (Bootstrap, Sass)
	$(COMPOSE_ENV) $(DC) run --rm assets npm ci --no-audit --no-fund

css: ## Compile le Sass et copie les ressources front dans public/assets
	$(COMPOSE_ENV) $(DC) run --rm assets npm run build

css-watch: ## Recompile le Sass à chaque modification
	$(COMPOSE_ENV) $(DC) run --rm assets npm run watch:css

docs: ## Génère la documentation technique à partir des DocBlock (var/docs/index.html)
	docker run --rm -u $(shell id -u):$(shell id -g) -v "$(CURDIR):/data" phpdoc/phpdoc:3 run -q
	@echo "Documentation générée : var/docs/index.html"
