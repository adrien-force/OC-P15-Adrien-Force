# 🤝 Guide de Contribution

Nous vous remercions de votre intérêt pour contribuer au site web d'Ina Zaoui ! Ce document vous guidera dans le processus de contribution au projet.

---

## 📋 Table des matières

- [Code de conduite](#-code-de-conduite)
- [Comment contribuer](#-comment-contribuer)
- [Signaler des problèmes](#-signaler-des-problèmes)
- [Proposer des fonctionnalités](#-proposer-des-fonctionnalités)
- [Contribuer au code](#-contribuer-au-code)
- [Contribuer aux tests](#-contribuer-aux-tests)
- [Contribuer à la documentation](#-contribuer-à-la-documentation)
- [Standards de code](#-standards-de-code)
- [Processus de révision](#-processus-de-révision)

---

## 🌟 Code de conduite

En participant à ce projet, vous acceptez de respecter notre code de conduite. Nous nous engageons à maintenir un environnement ouvert et accueillant pour tous les contributeurs.

### Nos engagements

- Utiliser un langage inclusif et respectueux
- Respecter les différents points de vue et expériences
- Accepter les critiques constructives avec grâce
- Se concentrer sur ce qui est le mieux pour la communauté

---

## 🚀 Comment contribuer

Il existe plusieurs façons de contribuer au projet :

1. **Signaler des bugs** 🐛
2. **Proposer des améliorations** ✨
3. **Corriger des problèmes existants** 🔧
4. **Améliorer la documentation** 📚
5. **Ajouter des tests** 🧪
6. **Réviser le code** 👀

---

## 🐛 Signaler des problèmes

Avant de signaler un problème, veuillez :

### 1. Vérifier les problèmes existants
- Consultez les [issues ouvertes](../../issues) pour éviter les doublons
- Recherchez dans les [issues fermées](../../issues?q=is%3Aissue+is%3Aclosed) au cas où le problème aurait déjà été résolu

### 2. Créer un rapport de bug détaillé

Utilisez le modèle suivant :

```markdown
## Description du problème
[Description claire et concise du problème]

## Étapes pour reproduire
1. Aller à '...'
2. Cliquer sur '....'
3. Défiler vers '....'
4. Constater l'erreur

## Comportement attendu
[Description de ce qui devrait se passer]

## Comportement actuel
[Description de ce qui se passe réellement]

## Captures d'écran
[Si applicable, ajoutez des captures d'écran]

## Environnement
- OS : [ex. macOS 12.0]
- Navigateur : [ex. Chrome 95]
- Version PHP : [ex. 8.2]
- Version Symfony : [ex. 7.3]

## Informations supplémentaires
[Toute autre information pertinente]
```

### 3. Labels appropriés

Utilisez les labels suivants selon le contexte :
- `bug` : Pour les dysfonctionnements
- `enhancement` : Pour les améliorations
- `documentation` : Pour les problèmes de documentation
- `security` : Pour les problèmes de sécurité (⚠️ à traiter en privé)

---

## ✨ Proposer des fonctionnalités

### 1. Discussion préalable

Avant de commencer le développement :
- Ouvrez une issue avec le label `enhancement`
- Décrivez clairement la fonctionnalité proposée
- Expliquez le problème que cela résout
- Proposez une solution ou une approche

### 2. Modèle de proposition de fonctionnalité

```markdown
## Problème à résoudre
[Décrivez le problème ou le besoin]

## Solution proposée
[Décrivez votre solution]

## Alternatives considérées
[Autres approches envisagées]

## Impact
- Sur les utilisateurs : [...]
- Sur la performance : [...]
- Sur la sécurité : [...]

## Tâches à réaliser
- [ ] Analyse technique
- [ ] Développement
- [ ] Tests
- [ ] Documentation
```

---

## 💻 Contribuer au code

### 1. Préparer votre environnement

```bash
# Forker le repository sur GitHub
# Cloner votre fork
git clone https://github.com/votre-username/876-p15-inazaoui.git
cd 876-p15-inazaoui

# Ajouter le repository original comme remote
git remote add upstream https://github.com/original-repo/876-p15-inazaoui.git

# Installer les dépendances
composer install

# Configurer l'environnement de développement
cp .env .env.local
```

### 2. Workflow de développement

```bash
# Créer une branche pour votre fonctionnalité
git checkout -b feature/ma-nouvelle-fonctionnalite

# ou pour un bug fix
git checkout -b bugfix/correction-du-probleme

# Effectuer vos modifications
# ...

# Ajouter et commiter vos changements
git add .
git commit -m "feat: ajouter la gestion des commentaires"

# Pousser vers votre fork
git push origin feature/ma-nouvelle-fonctionnalite
```

### 3. Conventions de nommage des branches

- `feature/` : Nouvelles fonctionnalités
- `bugfix/` : Corrections de bugs
- `hotfix/` : Corrections urgentes
- `docs/` : Modifications de documentation
- `refactor/` : Refactorisation de code

### 4. Messages de commit

Suivez la convention [Conventional Commits](https://www.conventionalcommits.org/fr/) :

```
type(scope): description courte

Corps du message (optionnel)

Footer (optionnel)
```

**Types recommandés :**
- `feat` : Nouvelle fonctionnalité
- `fix` : Correction de bug
- `docs` : Documentation
- `style` : Formatage, points-virgules manquants, etc.
- `refactor` : Refactorisation de code
- `test` : Ajout ou modification de tests
- `chore` : Tâches de maintenance

**Exemples :**
```
feat(auth): ajouter l'authentification OAuth
fix(upload): corriger la validation des fichiers images
docs: mettre à jour le guide d'installation
```

---

## 🧪 Contribuer aux tests

### Types de tests acceptés

1. **Tests unitaires** (`tests/Unit/`)
   - Testent des classes isolées
   - Utilisent des mocks pour les dépendances

2. **Tests fonctionnels** (`tests/Fonctionnal/`)
   - Testent les parcours utilisateur
   - Interagissent avec la base de données de test

### Exigences pour les tests

```bash
# Exécuter les tests avant de soumettre
php bin/phpunit

# Vérifier la couverture de code
php bin/phpunit --coverage-html var/coverage

# Les nouvelles fonctionnalités doivent avoir :
# - Au moins 80% de couverture de code
# - Tests unitaires pour la logique métier
# - Tests fonctionnels pour les endpoints
```

### Exemple de test unitaire

```php
<?php

namespace App\Tests\Unit\Service;

use App\Service\ImageCompressionService;
use PHPUnit\Framework\TestCase;

class ImageCompressionServiceTest extends TestCase
{
    public function testCompressImage(): void
    {
        $service = new ImageCompressionService();
        
        $result = $service->compress('/path/to/image.jpg');
        
        $this->assertTrue($result);
    }
}
```

---

## 📚 Contribuer à la documentation

### Types de documentation

1. **Code documentation**
   - Commentaires PHPDoc
   - Commentaires explicatifs dans le code complexe

2. **Documentation utilisateur**
   - README.md
   - Guides d'utilisation
   - FAQ

3. **Documentation technique**
   - Architecture du projet
   - API documentation
   - Guides de déploiement

### Standards de documentation

```php
/**
 * Compresse une image et la convertit au format WebP.
 *
 * @param string $imagePath Le chemin vers l'image source
 * @param int $quality La qualité de compression (0-100)
 * 
 * @return bool True si la compression s'est bien déroulée
 * 
 * @throws InvalidArgumentException Si le fichier n'existe pas
 * @throws RuntimeException Si la compression échoue
 */
public function compressImage(string $imagePath, int $quality = 85): bool
{
    // ...
}
```

---

## 🎨 Standards de code

### Respect des standards PHP

Le projet suit les standards **PSR-12** pour le style de code.

```bash
# Vérifier le style de code
vendor/bin/php-cs-fixer fix --dry-run

# Corriger automatiquement le style
vendor/bin/php-cs-fixer fix
```

### Analyse statique avec PHPStan

Le projet utilise **PHPStan niveau 6** (maximum).

```bash
# Analyser le code
vendor/bin/phpstan analyse src

# Toutes les erreurs PHPStan doivent être corrigées
```

### Règles de codage

1. **Nommage**
   - Classes : `PascalCase`
   - Méthodes : `camelCase`
   - Variables : `camelCase`
   - Constantes : `SNAKE_CASE`
   - Enums : `PascalCase`

2. **Structure**
   - Longueur maximale des lignes : 120 caractères
   - Indentation : 4 espaces (pas de tabs)
   - Accolades : style PSR-12

3. **Documentation**
   - Toutes les méthodes publiques doivent avoir des commentaires PHPDoc
   - Les classes complexes doivent avoir une description

### Sécurité

- ⚠️ **Jamais de secrets dans le code**
- Utiliser les variables d'environnement pour les configurations
- Valider et échapper tous les inputs utilisateur
- Suivre les bonnes pratiques Symfony pour la sécurité

---

## 🔍 Processus de révision

### 1. Soumettre une Pull Request

Une fois votre développement terminé :

1. **Créer la PR** depuis votre fork vers la branche `main`
2. **Utiliser le modèle de PR** fourni
3. **Ajouter une description détaillée**
4. **Lier les issues** concernées (fixes #123)
5. **Assigner des reviewers** si vous en connaissez

### 2. Modèle de Pull Request

```markdown
## Description
[Décrivez vos changements]

## Type de changement
- [ ] Bug fix (changement qui corrige un problème)
- [ ] Nouvelle fonctionnalité (changement qui ajoute une fonctionnalité)
- [ ] Breaking change (correction ou fonctionnalité qui causerait un dysfonctionnement des fonctionnalités existantes)
- [ ] Changement de documentation

## Tests
- [ ] J'ai ajouté des tests qui prouvent que ma correction est efficace ou que ma fonctionnalité fonctionne
- [ ] Les tests nouveaux et existants passent avec mes changements

## Checklist
- [ ] Mon code suit les guidelines de style de ce projet
- [ ] J'ai effectué une auto-révision de mon code
- [ ] J'ai commenté mon code, particulièrement dans les zones difficiles à comprendre
- [ ] J'ai apporté les modifications correspondantes à la documentation
- [ ] Mes changements ne génèrent aucun nouvel avertissement
```

### 3. Critères d'acceptation

Pour qu'une PR soit mergée :

- ✅ **Tests passants** : Tous les tests doivent passer
- ✅ **Couverture de code** : Maintenir au moins 70% de couverture
- ✅ **PHPStan niveau 10** : Aucune erreur d'analyse statique
- ✅ **Code style** : Respecter PSR-12
- ✅ **Révision approuvée** : Au moins une approbation d'un mainteneur
- ✅ **Pas de conflits** : La branche doit être à jour avec main
- ✅ **Documentation** : Mise à jour si nécessaire

### 4. Processus de révision

1. **Révision automatique** : Les CI/CD checks doivent passer
2. **Révision manuelle** : Un mainteneur révise le code
3. **Demandes de modification** : Si des changements sont requis
4. **Approbation** : Une fois tous les critères remplis
5. **Merge** : Fusion dans la branche principale

---

## 📞 Aide et support

### Besoin d'aide ?

- **Discord/Slack** : [Lien vers le channel de développement]
- **Email** : [email de contact]
- **Issues** : Pour les questions techniques, ouvrez une issue avec le label `question`

### Ressources utiles

- [Documentation Symfony](https://symfony.com/doc)
- [PHPStan Documentation](https://phpstan.org/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

## 🎉 Reconnaissance

Tous les contributeurs seront :

- **Mentionnés** dans les notes de version
- **Ajoutés** au fichier CONTRIBUTORS.md
- **Reconnus** pour leur travail dans la communauté

---

**Merci pour votre contribution au projet Ina Zaoui Photography ! 📸**

<p align="center">
  <i>Ensemble, nous créons quelque chose de magnifique !</i>
</p>