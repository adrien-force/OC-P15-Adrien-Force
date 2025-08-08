# 🏗️ Documentation Architecture - Site Ina Zaoui

> **Documentation technique** à destination des développeurs souhaitant contribuer au projet

---

## 🎯 Vue d'ensemble

Ce projet Symfony 7.3 est une application de gestion de portfolio photographique avec système d'invités et fonctionnalités avancées d'administration. L'architecture suit les bonnes pratiques Symfony avec une séparation claire des responsabilités.

### Fonctionnalités principales
- Portfolio photographique avec albums organisés
- Système de gestion d'invités (jeunes photographes)
- Interface d'administration complète
- Compression automatique d'images en WebP
- Système de reporting et monitoring des performances
- Contrôle d'accès granulaire avec Voters

---

## 📁 Structure des classes par catégorie

### 🎮 Controllers - Interface utilisateur

#### **HomeController** `src/Controller/HomeController.php`
**Rôle :** Contrôleur principal gérant toutes les pages publiques du site

**Points d'accès :**
- `GET /` → `index()` : Page d'accueil avec présentation d'Ina
- `GET /guests` → `guests()` : Liste paginée des invités avec recherche
- `GET /guest/{id}` → `guest()` : Profil détaillé d'un invité spécifique
- `GET /portfolio/{id?}` → `portfolio()` : Portfolio avec albums et médias paginés
- `GET /about` → `about()` : Page À propos statique

**Méthodes importantes :**
- `guests(Request $request, UserRepository $userRepository)` : Gestion pagination et recherche des invités
- `portfolio(?int $albumId, Request $request, AlbumRepository $albumRepository, MediaRepository $mediaRepository)` : Affichage des médias par album avec système de pagination

**Dépendances :** `EntityManagerInterface`, `UserRepository`

---

#### **Admin/AlbumController** `src/Controller/Admin/AlbumController.php`
**Rôle :** CRUD complet des albums en zone d'administration

**Restrictions d'accès :** `#[IsGranted('ROLE_ADMIN')]`

**Points d'accès :**
- `GET /admin/album` → `index()` : Liste paginée des albums avec statistiques
- `GET /admin/album/add` → `add()` : Formulaire de création d'album
- `POST /admin/album/update/{id}` → `update()` : Modification d'un album existant
- `POST /admin/album/delete/{id}` → `delete()` : Suppression avec gestion des médias orphelins

**Méthodes importantes :**
- `delete(Album $album, EntityManagerInterface $entityManager, MediaRepository $mediaRepository)` : Gère la suppression sécurisée en détachant les médias avant suppression de l'album

**Dépendances :** `EntityManagerInterface`, `AlbumRepository`, `MediaRepository`

---

#### **Admin/GuestController** `src/Controller/Admin/GuestController.php`
**Rôle :** Gestion complète des utilisateurs invités (promotion/rétrogradation des rôles)

**Restrictions d'accès :** `#[IsGranted(User::ADMIN_ROLE)]`

**Points d'accès :**
- `GET /admin/guest` → `index()` : Liste des invités avec recherche
- `GET /admin/guest/manage` → `manage()` : Interface de gestion des rôles utilisateurs
- `POST /admin/guest/add-role/{id}` → `addRole()` : Promotion utilisateur → invité
- `POST /admin/guest/remove-role/{id}` → `removeRole()` : Rétrogradation invité → utilisateur
- `GET /admin/guest/update/{id}` → `update()` : Modification du profil d'un invité
- `POST /admin/guest/delete/{id}` → `delete()` : Suppression avec nettoyage des médias associés

**Méthodes importantes :**
- `addRole(User $user, EntityManagerInterface $entityManager)` : Ajoute le rôle ROLE_GUEST à un utilisateur
- `removeRole(User $user, EntityManagerInterface $entityManager)` : Retire le rôle ROLE_GUEST d'un utilisateur
- `delete(User $user, UserRepository $userRepository, EntityManagerInterface $entityManager, MediaRepository $mediaRepository)` : Suppression sécurisée avec nettoyage des médias

**Dépendances :** `UserRepository`, `EntityManagerInterface`, `MediaRepository`

---

#### **Admin/MediaController** `src/Controller/Admin/MediaController.php`
**Rôle :** Gestion des médias avec compression automatique des images

**Points d'accès :**
- `GET /admin/media` → `index()` : Liste paginée avec filtrage automatique par utilisateur
- `GET /admin/media/add` → `add()` : Upload de médias avec compression WebP automatique
- `POST /admin/media/delete/{id}` → `delete()` : Suppression avec nettoyage du fichier physique

**Méthodes importantes :**
- `add(Request $request, EntityManagerInterface $entityManager, ImageCompressionService $imageCompressionService)` : Upload avec compression automatique via le service d'images
- `index(Request $request, MediaRepository $mediaRepository, Security $security)` : Filtrage automatique par utilisateur (sauf pour les admins)

**Particularités :** Intégration du service de compression d'images pour optimiser automatiquement tous les uploads

**Dépendances :** `EntityManagerInterface`, `MediaRepository`, `ImageCompressionService`

---

#### **Admin/SecurityController** `src/Controller/Admin/SecurityController.php`
**Rôle :** Gestion de l'authentification des utilisateurs

**Points d'accès :**
- `GET /login` → `login()` : Page de connexion
- `GET /logout` → Déconnexion (gérée automatiquement par Symfony Security)

**Méthodes importantes :**
- `login(AuthenticationUtils $authenticationUtils)` : Gère l'affichage du formulaire et les erreurs d'authentification, avec redirection automatique si l'utilisateur est déjà connecté

**Dépendances :** `AuthenticationUtils`

---

#### **Admin/RegistrationController** `src/Controller/Admin/RegistrationController.php`
**Rôle :** Inscription de nouveaux utilisateurs dans le système

**Points d'accès :**
- `GET|POST /admin/register` → `register()` : Formulaire d'inscription et traitement

**Méthodes importantes :**
- `register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager)` : Hachage automatique du mot de passe, attribution du rôle ROLE_USER par défaut

**Dépendances :** `UserPasswordHasherInterface`, `EntityManagerInterface`

---

### 🔧 Services - Logique métier

#### **ImageCompressionService** `src/Service/ImageCompressionService.php`
**Rôle :** Service de compression et conversion automatique d'images en format WebP optimisé

**Méthodes publiques :**
- `compressUploadedFile(UploadedFile $uploadedFile, string $targetDir)` : Compression d'un fichier fraîchement uploadé
- `compressExistingFile(string $filePath, bool $deleteOriginal = false)` : Compression d'un fichier existant avec option de suppression de l'original
- `processImage(string $inputPath, string $outputPath)` : Redimensionnement (max 1920x1080) + conversion WebP
- `convertToWebP(string $filename)` : Conversion automatique de l'extension vers .webp

**Configuration par défaut :**
- Qualité de compression : 85%
- Dimensions maximales : 1920x1080 pixels
- Format de sortie : WebP exclusivement

**Méthodes d'accès :** Service injecté dans les contrôleurs et commandes, disponible via l'autowiring Symfony

**Dépendances :** `Intervention\Image\ImageManager` (driver GD)

---

### 📋 Forms - Types de formulaires

#### **AlbumType** `src/Form/AlbumType.php`
**Rôle :** Formulaire simple pour la création et modification d'albums

**Champs configurés :**
- `name` (TextType) : Nom de l'album avec libellé français

**Usage :** Utilisé dans `AlbumController` pour les opérations CRUD

---

#### **GuestType** `src/Form/GuestType.php`
**Rôle :** Formulaire complet pour la gestion des profils d'invités

**Champs configurés :**
- `name` (TextType, obligatoire) : Nom complet de l'invité
- `email` (EmailType, obligatoire) : Adresse email avec validation
- `description` (TextareaType, optionnel) : Présentation de l'invité

**Configuration avancée :** Support de l'option `require_password` pour l'inclusion conditionnelle du champ mot de passe

**Usage :** Utilisé dans `GuestController` pour la modification des profils d'invités

---

#### **MediaType** `src/Form/MediaType.php`
**Rôle :** Formulaire d'upload de médias avec validation stricte des fichiers

**Champs configurés :**
- `file` (FileType) : Upload de fichier avec contraintes de sécurité
- `title` (TextType, obligatoire) : Titre descriptif du média
- `user` et `album` (EntityType) : Relations, visibles uniquement pour les administrateurs

**Validation des fichiers :**
- Taille maximale : 2048KB (2MB)
- Extensions autorisées : jpg, jpeg, png, webp, bmp, tiff, heic
- Messages d'erreur personnalisés en français

**Configuration dynamique :** Les champs `user` et `album` ne s'affichent que si l'option `is_admin` est activée

**Usage :** Utilisé dans `MediaController` avec adaptation automatique selon les privilèges utilisateur

---

#### **Security/RegistrationType** `src/Form/Security/RegistrationType.php`
**Rôle :** Formulaire d'inscription utilisateur avec confirmation de mot de passe

**Champs configurés :**
- `name` (TextType) : Nom complet de l'utilisateur
- `email` (TextType) : Adresse email (validation côté entité)
- `password` (RepeatedType) : Mot de passe avec confirmation et validation de correspondance

**Usage :** Utilisé dans `RegistrationController` pour l'inscription de nouveaux utilisateurs

---

### 🗄️ Repositories - Accès aux données optimisé

#### **AlbumRepository** `src/Repository/AlbumRepository.php`
**Rôle :** Requêtes spécialisées et optimisées pour la gestion des albums

**Méthodes importantes :**
- `findAllPaginated(int $page, int $limit, array $criteria = [], array $orderBy = [])` : Pagination avec critères de filtrage et tri personnalisables
- `countWithCriteria(array $criteria = [])` : Comptage total avec filtres pour le calcul de pagination

**Optimisations :** Requêtes DQL optimisées pour éviter les problèmes N+1

**Méthodes d'accès :** Injection automatique via Symfony, disponible dans tous les contrôleurs

---

#### **MediaRepository** `src/Repository/MediaRepository.php`
**Rôle :** Requêtes complexes pour les médias avec jointures optimisées

**Méthodes importantes :**
- `findAllMediaPaginatedWithAlbumAndUser(int $page, int $limit, array $criteria = [])` : Pagination avec eager loading des relations album et user (évite N+1)
- `findByAlbumPaginated(?Album $album, int $page, int $limit)` : Médias d'un album spécifique avec pagination
- `countByAlbum(?Album $album)` : Comptage des médias par album
- `countWithCriteria(array $criteria = [])` : Comptage avec filtres multiples pour pagination

**Optimisations spéciales :** Eager loading systématique des relations album/user dans toutes les requêtes listant des médias

**Usage :** Repository central pour toutes les opérations liées aux médias dans les contrôleurs

---

#### **UserRepository** `src/Repository/UserRepository.php`
**Rôle :** Requêtes utilisateurs avec gestion avancée des rôles et recherche

**Méthodes importantes :**
- `findAllGuestUsersPaginated(int $page, int $limit, ?string $search = null)` : Liste des invités avec recherche nom/email
- `findAllNonGuestUsersPaginated(int $page, int $limit)` : Utilisateurs non-invités avec pagination
- `countWithCriteria(array $criteria = [])` / `countNonGuestUsersWithCriteria()` : Comptages spécialisés pour pagination
- `findByRole(string $role)` / `findWithoutRole(string $role)` : Filtrage par rôles avec requêtes JSON
- `upgradePassword(UserInterface $user, string $newHashedPassword)` : Interface PasswordUpgraderInterface pour mise à jour automatique

**Spécificités techniques :** 
- Requêtes JSONB optimisées pour PostgreSQL sur les rôles
- Recherche full-text sur nom et email
- Support des critères de recherche complexes

**Usage :** Repository central pour toute la gestion utilisateur et les systèmes de rôles

---

### 🔒 Security - Contrôle d'accès granulaire

#### **Voter/AlbumVoter** `src/Security/Voter/AlbumVoter.php`
**Rôle :** Contrôle d'accès granulaire sur les opérations liées aux albums

**Permissions gérées :**
- `view` : Visualisation d'un album
- `edit` : Modification d'un album  
- `delete` : Suppression d'un album

**Logique d'autorisation :**
- `view` : Accès libre pour tous les utilisateurs (albums publics)
- `edit/delete` : Réservé exclusivement aux administrateurs

**Méthodes d'accès :** Utilisé automatiquement par Symfony Security via les annotations `#[IsGranted()]` dans les contrôleurs

**Dépendances :** `AccessDecisionManagerInterface` pour la vérification des rôles

---

#### **Voter/MediaVoter** `src/Security/Voter/MediaVoter.php`
**Rôle :** Contrôle d'accès complexe sur les opérations de médias avec gestion de propriété

**Permissions gérées :**
- `view` : Visualisation d'un média
- `edit` : Modification d'un média
- `delete` : Suppression d'un média
- `add` : Création de nouveaux médias

**Logique d'autorisation avancée :**
- `view` : Accès libre pour tous (médias publics)
- `edit/delete` : Propriétaire du média OU administrateur
- `add` : Invités (ROLE_GUEST) OU administrateurs uniquement

**Méthodes importantes :**
- `isAuthorOrAdmin(User $user, Media $media)` : Vérification de propriété ou de privilèges administrateur

**Usage :** Contrôle fin des permissions dans `MediaController` et templates Twig

**Dépendances :** Aucune (voter autonome)

---

### ⚡ Commands - Tâches en ligne de commande

#### **CompressImagesCommand** `src/Command/CompressImagesCommand.php`
**Commande :** `php bin/console app:compress-images`

**Rôle :** Compression en masse des images existantes dans le dossier d'upload

**Options disponibles :**
- `--quality` : Qualité de compression (1-100, défaut 85)
- `--dry-run` : Mode simulation sans modification des fichiers

**Fonctionnalités avancées :**
- Scan automatique du dossier `public/uploads/`
- Support de toutes les extensions d'images standard
- Barre de progression avec statistiques en temps réel
- Calcul des économies d'espace disque
- Gestion d'erreurs individuelle par fichier (continue en cas d'échec)

**Méthodes d'accès :** Ligne de commande ou intégration dans des tâches automatisées (cron)

**Dépendances :** `ImageCompressionService`, `Symfony\Component\Console\Helper\ProgressBar`

---

#### **ProfilerReportingCommand** `src/Command/ProfilerReportingCommand.php`
**Commande :** `php bin/console app:profiler:reporting`

**Rôle :** Génération automatisée de rapports de performance détaillés

**Options complètes :**
- `-n` : Nombre de tokens profiler à analyser (défaut 10)
- `--run-test` : Exécution d'Apache Benchmark avant collecte des données
- `--requests/-r` : Nombre de requêtes AB (défaut 100)
- `--concurrency/-c` : Niveau de concurrence AB (défaut 1)
- `--cookie/-k` : Cookie d'authentification pour les pages protégées
- `--base-url/-u` : URL de base pour les tests (défaut http://127.0.0.1:8000)

**Analyse automatique des routes critiques :**
- `/guests` : Performance de la liste d'invités
- `/portfolio` : Performance du portfolio
- `/admin/*` : Performance de l'interface d'administration

**Métriques collectées :**
- Temps de requêtes base de données (millisecondes)
- Temps de rendu total des templates (millisecondes)
- Nombre de requêtes SQL par page
- Nombre d'entités Doctrine gérées en mémoire
- Calculs statistiques : moyennes, écarts-types, min/max

**Exports générés :**
- Fichiers CSV individuels par route analysée
- Rapport Excel global consolidé avec graphiques et statistiques
- Intégration optionnelle des résultats Apache Benchmark

**Méthodes d'accès :** Ligne de commande pour analyse ponctuelle ou automatisation via scripts

**Dépendances :** 
- `Symfony\Component\HttpKernel\Profiler\Profiler`
- `PhpOffice\PhpSpreadsheet` (génération Excel)
- Apache Benchmark (ab) via processus système

---

## 🔗 Dépendances techniques majeures

### Framework et ORM
- **Symfony 7.3** : Framework PHP principal avec composants Security, Forms, Console
- **Doctrine ORM 3.x** : Mapping objet-relationnel avec PostgreSQL
- **Doctrine DBAL** : Abstraction base de données avec support JSONB

### Traitement d'images
- **Intervention Image 3.x** : Manipulation d'images avec driver GD
- Support WebP natif pour optimisation moderne

### Reporting et analyse
- **PhpSpreadsheet** : Génération de rapports Excel avec graphiques
- **Symfony Profiler** : Collecte de métriques de performance
- **Apache Benchmark** : Tests de charge via processus système

### Sécurité et formulaires
- **Symfony Security Bundle** : Authentification, autorisation, hashage mots de passe
- **Symfony Validator** : Validation de données avec contraintes personnalisées
- **Symfony Forms** : Génération et traitement de formulaires complexes

---

## 📊 Points d'architecture remarquables

### 1. **Séparation claire des responsabilités**
- Contrôleurs légers focalisés sur le flux HTTP
- Logique métier encapsulée dans les Services
- Accès données optimisé via Repositories spécialisés

### 2. **Sécurité multi-niveaux**
- Annotations `#[IsGranted()]` pour contrôle d'accès de base
- Voters personnalisés pour logique d'autorisation complexe
- Validation stricte des uploads avec contraintes de sécurité

### 3. **Optimisations de performance**
- Eager loading systématique pour éviter N+1
- Système de pagination intégré dans tous les repositories
- Compression automatique des images avec WebP
- Monitoring intégré via Profiler pour détection des goulots

### 4. **Monitoring et observabilité**
- Collecte automatique de métriques de performance
- Rapports détaillés avec statistiques avancées
- Intégration tests de charge pour validation performance
- Logs structurés pour debugging et monitoring

### 5. **Gestion d'erreurs robuste**
- Try/catch dans toutes les opérations critiques (uploads, DB)
- Validations multi-niveaux (formulaires, entités, contraintes custom)
- Messages d'erreur utilisateur localisés en français
- Fallbacks gracieux en cas d'échec des opérations non-critiques

### 6. **Configuration flexible**
- Options multiples dans les formulaires selon contexte utilisateur
- Commandes CLI configurables avec options étendues
- Adaptation automatique des interfaces selon les privilèges
- Support environnements multiples (dev/prod/test)

---

## 🚀 Guide de développement

### Pour ajouter une nouvelle fonctionnalité :

1. **Créer l'entité** (si nécessaire) avec `make:entity`
2. **Développer le Repository** avec méthodes optimisées
3. **Implémenter le Service** pour la logique métier
4. **Créer le FormType** avec validations appropriées
5. **Développer le Controller** en respectant les annotations de sécurité
6. **Ajouter les Voters** si contrôle d'accès spécifique requis
7. **Créer les templates** Twig avec intégration des permissions
8. **Écrire les tests** unitaires et fonctionnels
9. **Documenter** la nouvelle fonctionnalité

### Bonnes pratiques du projet :

- **Toujours** utiliser l'injection de dépendances Symfony
- **Préférer** les requêtes DQL avec eager loading aux getters en cascade
- **Implémenter** une pagination systématique pour les listes
- **Valider** tous les inputs utilisateur à plusieurs niveaux
- **Gérer** les erreurs avec des messages utilisateur explicites
- **Tester** les performances avec la commande de reporting intégrée

---

*Cette architecture solide offre une base robuste pour le développement de fonctionnalités photographiques avancées tout en maintenant performance et sécurité.*