# 📸 Site Web d'Ina Zaoui

<p align="center">
  <img src="public/images/ina.png" alt="Ina Zaoui Photography" width="200"/>
</p>

<p align="center">
  <i>Site portfolio d'une photographe spécialisée dans les paysages du monde entier</i>
</p>

---

## 🎯 Table des matières

- [À propos du projet](#-à-propos-du-projet)
- [Prérequis](#-prérequis)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Utilisation](#-utilisation)
- [Tests](#-tests)
- [Développement](#-développement)
- [Déploiement](#-déploiement)

---

## 📖 À propos du projet

Ce site web présente le travail d'Ina Zaoui, une photographe reconnue spécialisée dans les paysages du monde entier. Connue pour son approche écologique des déplacements (à dos d'animal, à pied, en vélo, bateau à voile ou montgolfière), elle partage ses créations à travers cette plateforme.

### ✨ Fonctionnalités principales

- **Portfolio photographique** : Galerie d'images organisée par albums
- **Gestion des invités** : Système permettant de promouvoir de jeunes photographes
- **Interface d'administration** : Gestion complète du contenu par l'administrateur
- **Compression d'images** : Optimisation automatique des images uploadées
- **Responsive design** : Interface adaptée à tous les écrans

### 🛠️ Technologies utilisées

- **Framework** : Symfony 7.3
- **Base de données** : PostgreSQL
- **PHP** : Version 8.2+
- **Frontend** : Twig, CSS, JavaScript
- **Tests** : PHPUnit
- **Analyse statique** : PHPStan
- **Conteneurisation** : Docker

---

## 🔧 Prérequis

Avant de commencer, assurez-vous d'avoir installé :

- **PHP** >= 8.2
- **Composer** (gestionnaire de dépendances PHP)
- **PostgreSQL** ou **MySQL**
- **Git**
- **Symfony CLI** (recommandé)
- **Docker & Docker Compose** (optionnel, mais recommandé)
---

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/adrien-force/OC-P15-Adrien-Force
cd 876-p15-inazaoui
```

### 2. Installation des dépendances

```bash
make composer
```

### 3. Configuration de l'environnement

```bash
# Copier le fichier d'environnement
cp .env .env.local

# Modifier les variables selon votre configuration
nano .env.local
```

### 4. Installation complète avec Docker

```bash
# Démarrer les services Docker
make docker

# Installer les dépendances
make composer

# Configurer et restaurer la base de données
make db
```

### 5. Installation alternative (sans Docker)

```bash
# Installation complète avec base de données locale
make reinstall
```

---

## ⚙️ Configuration

### Variables d'environnement

Modifiez le fichier `.env.local` avec vos paramètres :

```env
# Base de données
DATABASE_URL="postgresql://username:password@127.0.0.1:5432/ina_zaoui?serverVersion=15&charset=utf8"

# Configuration email (optionnel)
MAILER_DSN=smtp://localhost:1025

# Environnement
APP_ENV=dev
APP_SECRET=your_secret_key
```

### Configuration Docker

Si vous utilisez Docker (`make docker`), les services suivants seront disponibles :

- **PostgreSQL** : localhost:5432
- **Base de données** : `ina_zaoui`
- **Utilisateur** : `postgres`

### Commandes de base de données

```bash
# Réinitialiser complètement la base
make reset-db

# Restaurer depuis les fichiers SQL
make restore-db

# Configuration complète (reset + schema + restore)
make db
```

---

## 🎮 Utilisation

### Démarrer le serveur de développement

```bash
# Avec Symfony CLI (recommandé)
symfony serve

# Ou avec PHP built-in server
php -S localhost:8000 -t public/
```

Le site sera accessible à l'adresse : http://localhost:8000

### Compte administrateur

Pour accéder à l'interface d'administration :

- **Identifiant** : `ina`
- **Mot de passe** : `password`
- **URL** : http://localhost:8000/admin

### Fonctionnalités disponibles

1. **Page d'accueil** : Présentation du travail d'Ina
2. **Portfolio** : Galerie d'images organisée par albums
3. **Invités** : Section dédiée aux jeunes photographes
4. **Administration** : Gestion complète du contenu (albums, médias, invités)

---

## 🧪 Tests

### Exécuter tous les tests

```bash
# Tests unitaires et fonctionnels
make test

# Avec couverture de code (ouvre automatiquement dans le navigateur)
make coverage
```

### Tests par catégorie

```bash
# Tests unitaires uniquement
./bin/phpunit tests/Unit

# Tests fonctionnels uniquement
./bin/phpunit tests/Fonctionnal
```

### Charger les fixtures de test

```bash
make fixture
```

---

## 💻 Développement

### Commandes Make disponibles

```bash
# Analyse statique du code
make phpstan

# Formater le code automatiquement
make lint

# Refactoring automatique (aperçu)
make rector

# Appliquer les refactorings
make rector-fix

# Créer une migration
make migration

# Appliquer les migrations
make migrate

# Réinstaller le projet complet
make reinstall
```

### Autres commandes utiles

```bash
# Vider le cache
symfony console cache:clear

# Générer une entité
symfony console make:entity

# Mettre à jour le schéma de base
make update-schema
```

### Structure du projet

```
├── src/
│   ├── Controller/     # Contrôleurs
│   ├── Entity/         # Entités Doctrine
│   ├── Form/           # Types de formulaires
│   ├── Repository/     # Repositories
│   ├── Service/        # Services métier
│   └── Security/       # Composants de sécurité
├── templates/          # Templates Twig
├── public/            # Fichiers publics
├── tests/             # Tests
├── migrations/        # Migrations de base de données
└── config/            # Configuration
```

### Compression d'images

Le projet inclut un service de compression automatique des images :

```bash
# Compresser toutes les images du dossier uploads
php bin/console app:compress-images
```

### Gestion de la base de données

```bash
# Réinitialiser complètement la base
make reset-db

# Restaurer depuis les fichiers SQL
make restore-db

# Configuration complète (reset + schema + restore)
make db
```

---

## 🚀 Déploiement

### Préparation pour la production

```bash
# Installer les dépendances pour la production
composer install --no-dev --optimize-autoloader

# Vider et chauffer le cache
APP_ENV=prod php bin/console cache:clear
APP_ENV=prod php bin/console cache:warmup

# Exécuter les migrations
APP_ENV=prod php bin/console doctrine:migrations:migrate
```

### Variables d'environnement pour la production

```env
APP_ENV=prod
APP_DEBUG=false
DATABASE_URL="postgresql://user:pass@host:port/db_name"
```

### Serveur web

Configurez votre serveur web pour pointer vers le dossier `public/` et assurez-vous que :

- PHP >= 8.2 est installé
- Les extensions requises sont activées
- Les permissions sont correctement configurées
- HTTPS est configuré

---

## 📝 Notes techniques

### Sauvegarde et restauration

Un fichier `backup.zip` contient :
- Un dump SQL anonymisé de la base de données
- Toutes les images du dossier `public/uploads`

⚠️ **Note** : Le fichier de sauvegarde est volumineux (>1Go). Une solution d'optimisation est recommandée pour la production.

### Optimisations implémentées

- Compression automatique des images WebP
- Cache HTTP pour les assets statiques
- Optimisation des requêtes Doctrine
- Pagination pour les grandes listes

---

## 📞 Support

Pour toute question ou problème :

1. Consultez la documentation Symfony officielle
2. Vérifiez les logs dans `var/log/`
3. Consultez les issues du projet

---

<p align="center">
  <i>Développé avec ❤️ pour Ina Zaoui Photography</i>
</p>
