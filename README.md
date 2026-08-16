# 🛍️ StoreManager Pro — ERP Commercial & Point de Vente (POS)

> Application de gestion commerciale intégrée (ERP/POS) conçue et développée en **PHP 8+ POO Pure From Scratch** (sans aucun framework externe ni ORM), avec connectivité résiliente **PostgreSQL & Fallback SQLite**, architecture en couches **Clean Layered MVC**, et contrôle d'accès basé sur les rôles (**RBAC**).

---

## 📑 Sommaire
1. [Aperçu & Fonctionnalités Clés](#-aperçu--fonctionnalités-clés)
2. [Architecture Logicielle & Clean POO](#-architecture-logicielle--clean-poo)
3. [Matrice des Rôles & Profils Métiers (RBAC)](#-matrice-des-rôles--profils-métiers-rbac)
4. [Résilience & Base de Données (PostgreSQL / SQLite)](#-résilience--base-de-données-postgresql--sqlite)
5. [Installation & Démarrage Rapide](#-installation--démarrage-rapide)
6. [Comptes & Profils de Démonstration](#-comptes--profils-de-démonstration)
7. [Validation & Tests Automatisés (294 Assertions)](#-validation--tests-automatisés-294-assertions)
8. [Documentation & Livrables Complémentaires](#-documentation--livrables-complémentaires)

---

## 🌟 Aperçu & Fonctionnalités Clés

StoreManager Pro centralise l'ensemble de la chaîne opérationnelle d'un commerce de détail :

- 🛒 **Caisse Tactile & Point de Vente (POS)** :
  - Recherche instantanée multi-critères (libellé, code-barres) avec raccourci clavier `[F2]`.
  - Gestion dynamique du panier (ajustement des quantités, remises en direct, sous-totaux).
  - Encaissement multi-canaux : *Espèces*, *Wave*, *Orange Money*, *Carte Bancaire*, et *Vente à Crédit / Dette*.
  - Rendu et impression de tickets de caisse thermiques conformes 80mm.
- 💳 **Gestion des Créances & Recouvrements de Dettes** :
  - Contrôle strict et temps réel du plafond de crédit autorisé (`limite_credit`).
  - Blocage automatique des ventes à crédit si le montant excède le disponible client.
  - Enregistrement des règlements fractionnés ou totaux avec recalcul du reste dû.
  - Bascule d'état automatique vers `SOLDEE` et décrémentation instantanée de l'encours client pour restaurer son pouvoir d'achat.
- 📦 **Approvisionnements & Réception de Marchandises (BL)** :
  - Création de Bons de Livraison (BL) rattachés aux fournisseurs.
  - Volet de réception physique avec ajustement des quantités réelles livrées.
  - Incrémentation automatique et atomique des stocks en magasin sous transaction PDO.
- 👥 **Contrôle d'Accès Sécurisé (RBAC)** :
  - Ségrégation stricte des prérogatives selon 4 profils métiers hermétiques.
  - Navigation dynamique filtrée selon les droits de l'utilisateur connecté.
  - Sélecteur de profils rapides pour les démonstrations et évaluations.

---

## 🏛️ Architecture Logicielle & Clean POO

Le projet respecte une architecture en couches étanches (**Clean Layered Architecture / MVC**) sans dépendance à un framework :

```
┌────────────────────────────────────────────────────────┐
│                   VUES (HTML5 / CSS / JS)              │
│  src/views/pos/ | src/views/dettes/ | src/views/auth/  │
└───────────────────────────▲────────────────────────────┘
                            │ (Requêtes HTTP / Réponses)
┌───────────────────────────┴────────────────────────────┐
│                    CONTROLLERS (Web)                   │
│   AuthController, POSController, DebtController, etc.  │
└───────────────────────────▲────────────────────────────┘
                            │ (Appels de Services Métier)
┌───────────────────────────┴────────────────────────────┐
│                    SERVICES (Métier)                   │
│   VenteService, DebtService, SupplyService, AuthManager│
│        (Transactions SQL, Règles de gestion métier)    │
└───────────────────────────▲────────────────────────────┘
                            │ (Persistance & Requêtes SQL)
┌───────────────────────────┴────────────────────────────┐
│                  REPOSITORIES & CORE                   │
│     ProduitRepository, ClientRepository, Database      │
│          (PDO préparé, Mode Fallback PG -> SQLite)     │
└───────────────────────────▲────────────────────────────┘
                            │ (Hydratation & Manipulation)
┌───────────────────────────┴────────────────────────────┐
│                  ENTITÉS POO (Model)                   │
│  Produit, Client, Fournisseur, Vente, Dette, Paiement  │
└────────────────────────────────────────────────────────┘
```

### Organisation des Répertoires :
- `src/Core/` : Composants d'infrastructure réutilisables ([Database.php](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/src/Core/Database.php) avec Singleton & Fallback, [Router.php](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/src/Core/Router.php), [SessionManager.php](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/src/Core/SessionManager.php), [Autoloader.php](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/src/Core/Autoloader.php)).
- `src/Model/Entity/` : 15 entités POO pures encapsulant les règles et calculs métiers du domaine (`Produit`, `Client`, `Vente`, `Dette`, `Paiement`, `Approvisionnement`, `User`, etc.).
- `src/Model/Repository/` : Couche d'accès aux données avec requêtes préparées PDO strictes (`ProduitRepository`, `ClientRepository`, `DetteRepository`, `UserRepository`, `ApprovisionnementRepository`).
- `src/Service/` : Couche métier orchestrant les flux transactionnels (`VenteService`, `DebtService`, `SupplyService`, `AuthManager`).
- `src/Controller/` : Contrôleurs Web traitant les entrées HTTP, les sessions, les notifications Flash et les redirections PRG (`POSController`, `DetteController`, `SupplyController`, `AuthController`, `DashboardController`, `CatalogueController`).
- `src/views/` : Vues et composants modulaires d'interface utilisateur.
- `public/` : Front Controller minimaliste (`index.php`) et assets statiques.
- `database/` : Scripts de schémas DDL normalisés 3FN (`schema.sql` PostgreSQL et `schema_sqlite.sql` SQLite) et base locale `erp.db`.
- `tests/` : Batterie complète de 8 scripts de tests automatisés.
- `docs/` : Diagrammes de cas d'utilisation et diagrammes de classes UML au format PlantUML.

---

## 👥 Matrice des Rôles & Profils Métiers (RBAC)

| Rôle | Caisse POS & Vente | Gestion Dettes | Approvisionnements BL | Catalogue Produits/Tiers | Dashboard & Audit |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **👑 Admin Boutique (`ADMIN`)** | ✅ Accès Total | ✅ Accès Total | ✅ Accès Total | ✅ Accès Total | ✅ Accès Total |
| **🛒 Chargé de Vente (`VENTE`)** | ✅ Ventes & Caisse | ✅ Encaissement dettes | ❌ Pas d'accès | 👁️ Lecture Clients | ❌ Pas d'accès |
| **📦 Chargé de Stock (`STOCK`)** | ❌ Pas d'accès | ❌ Pas d'accès | ✅ Réception BL | ✅ Produits / Fournisseurs | ❌ Pas d'accès |
| **📋 Inventaire (`INVENTAIRE`)** | ❌ Pas d'accès | ❌ Pas d'accès | ❌ Pas d'accès | ✅ Consultation & Audit | ❌ Pas d'accès |

---

## 🛡️ Résilience & Base de Données (PostgreSQL / SQLite)

Le connecteur `src/Core/Database.php` implémente un système de haute disponibilité à deux niveaux :

1. **Tentative PostgreSQL prioritaire** : Se connecte au serveur PostgreSQL selon les variables d'environnement (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`).
2. **Fallback automatique et transparent vers SQLite** : Si le serveur PostgreSQL est inaccessible, l'exception `PDOException` est capturée et la connexion bascule instantanément vers `database/erp.db`.
3. **Auto-initialisation (Self-Healing)** : Si la base SQLite locale est inexistante ou vierge, elle est automatiquement initialisée et seedée via `database/schema_sqlite.sql`.
4. **Intégrité stricte** : `PRAGMA foreign_keys = ON;` activé systématiquement sur SQLite, et `PDO::ATTR_EMULATE_PREPARES => false` pour bloquer les injections SQL.

---

## 🚀 Installation & Démarrage Rapide

### Prérequis :
- **PHP 8.2 ou supérieur** (extensions `pdo`, `pdo_sqlite`, `pdo_pgsql` recommandées).
- **Composer** (pour l'autoloading PSR-4 standard).

### 1. Cloner le projet :
```bash
git clone https://github.com/yussuf9900/Store-Manager-Pro-POO.git
cd storeManager
```

### 2. Installer les dépendances (Autoloading PSR-4) :
```bash
composer dump-autoload
```

### 3. Lancer le serveur local de développement :
```bash
php -S localhost:8000 -t public
```

Ouvrez votre navigateur sur : **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Comptes & Profils de Démonstration

L'écran de connexion (`/login`) propose des **boutons de connexion instantanée** pour chacun des 4 profils métiers. Vous pouvez également vous connecter avec les identifiants suivants :

| Profil | Identifiant (Email) | Mot de passe | Route d'atterrissage |
| :--- | :--- | :--- | :--- |
| **Admin Boutique** | `admin@storemanager.pro` | `password123` *(ou `demo1234`)* | `/dashboard` |
| **Chargé de Vente** | `vente@storemanager.pro` | `password123` *(ou `demo1234`)* | `/pos` |
| **Chargé de Stock** | `stock@storemanager.pro` | `password123` *(ou `demo1234`)* | `/supplies` |
| **Inventaire** | `inventaire@storemanager.pro` | `password123` *(ou `demo1234`)* | `/catalog` |

---

## 🧪 Validation & Tests Automatisés (294 Assertions)

Le projet dispose d'une suite complète de tests automatisés couvrant 100% des briques logicielles :

```bash
# Lancer l'intégralité des suites de tests
php tests/test_entities.php && \
php tests/test_repositories.php && \
php tests/test_vente_service.php && \
php tests/test_pos_controller.php && \
php tests/test_views.php && \
php tests/test_debt_service.php && \
php tests/test_supply_service.php && \
php tests/test_auth.php
```

### Synthèse des Tests :
- `test_entities.php` : 38 assertions (Encapsulation, typage strict, calculs marges, plafonds crédit, remises, cycle de dette).
- `test_repositories.php` : 45 assertions (CRUD, requêtes préparées, décrémentation/incrémentation atomique).
- `test_vente_service.php` : 57 assertions (Validation vente, calculs totaux, contrôle crédit, rollback transactionnel).
- `test_pos_controller.php` : 24 assertions (Gestion panier en session, actions contrôleur, flux caisse).
- `test_views.php` : 6 assertions (Rendu HTML des vues sans notices/warnings).
- `test_debt_service.php` : 42 assertions (Remboursements partiels/totaux, mise à jour encours, bascule `SOLDEE`).
- `test_supply_service.php` : 34 assertions (Création BL, réception marchandise, incrémentation de stock).
- `test_auth.php` : 48 assertions (Authentification, profils rapides, filtrage RBAC, navbar dynamique).
- **Total : 294/294 assertions validées avec 100% de réussite (0 échec)**.

---

## 📚 Documentation & Livrables Complémentaires

- 📓 **[DEVLOG.md](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/DEVLOG.md)** : Journal de développement complet avec le suivi chronologique heure par heure et **l'autopsie détaillée ligne par ligne des 3 méthodes clés** pour la soutenance orale.
- 📐 **[docs/](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/docs/)** : Dossier de modélisation UML :
  - [use_cases.puml](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/docs/use_cases.puml) : Diagramme de cas d'utilisation (Acteurs, packages, inclusions, extensions).
  - [diagramme_classes.puml](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/docs/diagramme_classes.puml) : Diagramme de classes 100% POO pure.
- 📜 **[charte_projet_etudiants.md](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/charte_projet_etudiants.md)** & **[planning_weekend_etudiants.md](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/planning_weekend_etudiants.md)** : Spécifications et grille d'évaluation de l'épreuve.
