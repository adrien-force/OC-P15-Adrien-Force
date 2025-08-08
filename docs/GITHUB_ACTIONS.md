# 🚀 Configuration GitHub Actions - Pipeline CI/CD

## Vue d'ensemble

Cette pipeline CI/CD automatise la validation de la qualité du code et des tests à chaque push ou pull request. Elle utilise les commandes du Makefile pour garantir la cohérence avec l'environnement de développement local.

---

## ⚙️ Configuration de la pipeline

### Déclencheurs

La pipeline se déclenche automatiquement sur :
- **Push** sur les branches `main` et `develop`
- **Pull Request** vers la branche `main`

### Environnement d'exécution

- **OS** : Ubuntu Latest
- **PHP** : 8.2
- **PostgreSQL** : 15
- **Extensions PHP** : ctype, iconv, json, pdo, pdo_pgsql, intl, gd, zip

---

## 📋 Étapes de la pipeline

### 1. **Préparation de l'environnement**
- Checkout du code source
- Installation de PHP 8.2 avec les extensions requises
- Configuration du cache Composer pour optimiser les temps de build

### 2. **Installation des dépendances**
```bash
make composer
```
Utilise la commande Makefile pour installer les dépendances via Composer

### 3. **Configuration de la base de données de test**
- Création automatique de la base PostgreSQL de test
- Application des migrations Doctrine
- Chargement des fixtures pour les tests

### 4. **Validation de la qualité du code**

#### a) **Linting (PHP-CS-Fixer)**
```bash
make lint
```
- Vérification du respect des standards PSR-12
- Formatage automatique du code selon les règles définies

#### b) **Analyse statique (PHPStan)**
```bash
make phpstan
```
- Analyse statique niveau 10 (maximum)
- Détection des erreurs potentielles et des types incorrects
- Limite mémoire : 1GB

#### c) **Tests unitaires et fonctionnels (PHPUnit)**
```bash
make test
```
- Exécution de tous les tests du projet
- Limite mémoire : 3GB
- Tests unitaires (`tests/Unit/`)
- Tests fonctionnels (`tests/Fonctionnal/`)

### 5. **Gestion des artefacts**
En cas d'échec, upload automatique des logs et du cache de test pour debugging

---

## 🛠️ Configuration GitHub (À faire sur GitHub)

### 1. **Secrets requis**
Aucun secret supplémentaire requis pour cette configuration de base.

### 2. **Protection des branches**
Recommandé d'activer dans les paramètres du repository :

```
Settings > Branches > Add rule
```

**Pour la branche `main` :**
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging  
- ✅ Status checks requis :
  - `Continuous Integration`
- ✅ Require linear history (optionnel)
- ✅ Include administrators

### 3. **Notifications**
Configuration recommandée dans `Settings > Notifications` :
- ✅ Actions : Send notifications for failed workflows only

---

## 📊 Monitoring et debugging

### Logs disponibles
- **PHP-CS-Fixer** : Erreurs de formatage
- **PHPStan** : Erreurs d'analyse statique  
- **PHPUnit** : Résultats des tests avec détails des échecs
- **Composer** : Problèmes de dépendances

### Artefacts en cas d'échec
Téléchargement automatique des fichiers de debug :
- `var/log/` : Logs de l'application
- `var/cache/test/` : Cache de test pour inspection

### Optimisations incluses
- **Cache Composer** : Réutilisation des dépendances entre builds
- **Services PostgreSQL** : Base de données en conteneur pour les tests
- **Pas de couverture de code** : Focus sur la vitesse d'exécution

---

## 🔧 Maintenance et évolution

### Mise à jour des versions
Pour mettre à jour les versions dans `.github/workflows/ci.yml` :

```yaml
env:
  PHP_VERSION: '8.3'        # Nouvelle version PHP
  POSTGRES_VERSION: '16'    # Nouvelle version PostgreSQL
```

### Ajout de nouvelles vérifications
Pour ajouter de nouveaux outils de qualité :

1. **Ajouter la commande dans le Makefile** :
```makefile
security-check:
	vendor/bin/security-checker security:check
```

2. **Intégrer dans la pipeline** :
```yaml
- name: Run security check
  run: make security-check
```

### Optimisations possibles

#### Cache PHP
```yaml
- name: Cache PHP extensions
  uses: actions/cache@v3
  with:
    path: /tmp/php-ext-cache
    key: ${{ runner.os }}-php-ext-${{ env.PHP_VERSION }}
```

#### Tests en parallèle
```yaml
strategy:
  matrix:
    php-version: ['8.2', '8.3']
    test-suite: ['Unit', 'Fonctionnal']
```

#### Déploiement automatique
```yaml
deploy:
  needs: ci
  if: github.ref == 'refs/heads/main'
  runs-on: ubuntu-latest
  steps:
    - name: Deploy to production
      run: echo "Deploy logic here"
```

---

## ⚠️ Points d'attention

### Gestion des échecs
- **PHP-CS-Fixer** : Peut échouer si le code n'est pas formaté
- **PHPStan** : Échec sur erreurs de typage ou logique
- **PHPUnit** : Échec si des tests ne passent pas

### Performance
- **Durée moyenne** : 3-5 minutes selon la taille du cache
- **Limite de mémoire** : 3GB pour les tests (configurable)
- **Timeout** : 10 minutes par défaut GitHub Actions

### Dépendances externes
- **PostgreSQL** : Service conteneurisé, pas de dépendance externe
- **Composer packages** : Mis en cache pour optimiser
- **PHP extensions** : Installées automatiquement

---

## 🎯 Résultats attendus

### ✅ Pipeline réussie
- Code formaté selon PSR-12
- Aucune erreur PHPStan niveau 10
- Tous les tests PHPUnit passent
- Base de données de test correctement configurée

### ❌ Pipeline échouée
- Détails des erreurs dans les logs GitHub Actions
- Artefacts téléchargeables pour debugging local
- Blocage des merges sur la branche protégée

### 📈 Métriques de qualité
La pipeline garantit :
- **100% de conformité** aux standards de code
- **Analyse statique** complète sans erreur
- **Couverture de tests** selon les tests définis
- **Cohérence** avec l'environnement de développement local

---

*Cette pipeline CI/CD assure une qualité constante du code et facilite la collaboration en équipe en automatisant les vérifications essentielles.*