# Journal de Développement (DEVLOG)
**Nom & Prénom** : Youssou SALL  
**Projet** : StoreManager Pro (ERP PHP/POO)  

---

## 1. Suivi Chronologique des Phases

### [Vendredi - Phase 1] : Conception & BDD Fallback

---

#### Step 1.1 (19h00 - 20h30) : Conception & Modélisation UML (Use Cases & Diagramme de Classes POO)

- **Heure de réalisation** : 19h00 - 20h30
- **Ce qui a été fait** :

  1. **Analyse Approfondie du Domaine Métier & Cahier des Charges** :
     - Étude détaillée du prototype d'interface (`storemanager_pro_app.html`) et de la charte de projet.
     - Identification des problématiques clés de gestion commerciale : gestion de caisse tactile en temps réel, support de règlements multiples (Cash, Wave, Orange Money, Carte Bancaire, Crédit), traçabilité stricte des dettes clients avec gestion du plafond de crédit (`limite_credit`), chaîne d'approvisionnement logistique via Bons de Livraison (BL) fournisseurs, et valorisation continue des stocks avec seuils d'alerte critique.

  2. **Matrice de Ségrégation des Responsabilités & Rôles (RBAC)** :
     - Formalisation de 4 profils utilisateurs distincts aux périmètres fonctionnels hermétiques :
       - **Admin Boutique (`ADMIN`)** : Contrôle absolu sur l'ensemble de l'ERP (visualisation des KPIs financiers globaux sur le Dashboard, gestion des comptes utilisateurs et attribution des rôles, paramétrage général du catalogue, des clients, des fournisseurs et clôture comptable).
       - **Chargé de Vente (`VENTE`)** : Opérateur dédié au point de vente (caisse POS tactile). Il recherche les articles (par code-barres ou libellé), gère le panier, applique les remises, valide les encaissements et dispose du droit exclusif de consulter le registre des créances pour enregistrer des remboursements de dettes.
       - **Chargé de Stock (`STOCK`)** : Responsable logistique amont. Il réceptionne les marchandises fournisseurs sous Bon de Livraison (BL), saisit les quantités reçues et prix d'achat, incrémente le stock physique et gère le catalogue produits et fournisseurs. Il n'a aucun accès aux fonctions d'encaissement de caisse ni aux créances clients.
       - **Inventaire (`INVENTAIRE`)** : Rôle d'audit et de contrôle périodique. Il accède en mode consultation et comptage physique aux répertoires des articles et des tiers, sans possibilité d'altérer les données financières, de modifier les prix ou d'effectuer des ventes.

  3. **Modélisation du Diagramme de Cas d'Utilisation (`docs/use_cases.puml`)** :
     - Structuration modulaire en 6 packages fonctionnels : *Authentification & Profils*, *Caisse POS & Ventes*, *Gestion des Dettes & Règlements*, *Approvisionnements & Réception BL*, *Catalogue & Répertoires Tiers*, *Dashboard & Pilotage*.
     - **Formalisation rigoureuse des relations d'inclusion (`<<include>>`)** :
       - `UC_ValiderVente` $\xrightarrow{<<include>>}$ `UC_ManageCart` : Une vente ne peut être finalisée sans la constitution préalable d'un panier chiffré.
       - `UC_ValiderVente` $\xrightarrow{<<include>>}$ `UC_DecStock` : La validation transactionnelle d'une vente décrémente obligatoirement et atomiquement le stock des produits vendus.
       - `UC_ValiderVente` $\xrightarrow{<<include>>}$ `UC_PrintTicket` : Chaque vente génère obligatoirement une facture/ticket de caisse horodaté.
       - `UC_PayDebt` $\xrightarrow{<<include>>}$ `UC_UpdateDebtStatus` : L'enregistrement d'un versement recalcule le solde restant et bascule automatiquement le statut en `SOLDEE` dès que `montant_restant = 0`.
       - `UC_CreateBL` $\xrightarrow{<<include>>}$ `UC_IncStock` & `UC_SelectSupplier` : L'enregistrement d'un Bon de Livraison augmente automatiquement les stocks des produits réceptionnés et associe obligatoirement le fournisseur concerné.
     - **Formalisation rigoureuse des relations d'extension (`<<extend>>`)** :
       - `UC_ValiderVente` $\xleftarrow{<<extend>>}$ `UC_CheckCredit` : L'extension de contrôle de solvabilité ne s'exécute **que sous la condition** où le mode de paiement choisi est *Dette / À crédit*. Le système vérifie la règle d'invariance : `(Dettes actuelles du client + Montant du panier) <= Limite de crédit autorisée`.
       - `UC_ValiderVente` $\xleftarrow{<<extend>>}$ `UC_SelectClient` : Conditionnelle si la vente est rattachée à un compte client nominatif (obligatoire en cas de vente à crédit, facultatif pour une vente au comptoir passager).

  4. **Modélisation du Diagramme de Classes 100% POO Pure (`docs/diagramme_classes.puml`)** :
     - **Choix d'Architecture 100% Classes** : Remplacement délibéré des énumérations (`enum`) par des classes pures (`Role`, `StatutDette`, `ModePaiement`, `StatutAppro`, `Categorie`) dotées d'identifiants, de codes et de libellés. Ce choix garantit un découplage optimal, facilite l'hydratation objet depuis PDO et correspond parfaitement au schéma relationnel sous-jacent (tables de référence avec clés étrangères).
     - **Encapsulation & Logique Métier par Entité** :
       - **`Client`** : Encapsule la gestion du risque client avec les propriétés privées `limiteCredit` et `totalDettesActuelles`. Méthodes métiers clés :
         - `getCreditDisponible(): float` : Retourne `max(0, limiteCredit - totalDettesActuelles)`.
         - `peutPrendreCredit(float $montant): bool` : Vérifie si `(totalDettesActuelles + montant) <= limiteCredit`.
         - `ajouterDette(float $montant): void` : Augmente l'encours de dette client lors d'une vente à crédit.
         - `diminuerDette(float $montant): void` : Réduit l'encours de dette lors d'un remboursement.
       - **`Produit`** : Encapsule la tenue de stock et les calculs de rentabilité :
         - `estEnAlerte(): bool` : Détecte si `qteStock <= seuilAlerte`.
         - `calculerMarge(): float` : Retourne `prixVente - prixAchat`.
         - `calculerTauxMarge(): float` : Calcule le ratio de marge commerciale en pourcentage.
         - `retirerStock(int $quantite): bool` : Décrémente le stock de façon sécurisée en vérifiant la disponibilité (lève une exception si stock insuffisant).
         - `ajouterStock(int $quantite): void` : Incrémente le stock lors de la réception d'un approvisionnement.
       - **`Vente` & `LigneVente` (Composition Forte `*--`)** :
         - La classe `Vente` gère l'en-tête de transaction (`numeroFacture`, `dateVente`, `montantTotal`, `montantPaye`, `montantRestant`) et contient une collection d'objets `LigneVente[]`.
         - `calculerTotal(): float` : Parcourt l'ensemble des lignes pour recalculer la somme exacte des sous-totaux `(prixUnitaire * quantite - remise)`.
         - `estACredit(): bool` : Détermine si la vente a généré une créance non réglée.
       - **`Dette` & `Paiement` (Cycle de Vie de la Créance)** :
         - L'entité `Dette` trace la créance générée par une vente (`montantTotal`, `montantRestant`, `dateEcheance`, `statut`).
         - `enregistrerPaiement(Paiement $paiement): void` : Déduit le montant encaissé de `montantRestant`, ajoute le paiement à la collection `paiements[]`, et bascule l'état vers `SOLDEE` si `montantRestant <= 0`.
         - `estEnRetard(): bool` : Compare la date du jour à la `dateEcheance` pour les dettes non soldées.
       - **`Approvisionnement` & `LigneApprovisionnement` (Composition Forte `*--`)** :
         - Trace les réceptions de marchandises avec numéro de Bon de Livraison (BL), fournisseur, date et opérateur de stock.
       - **`User`** :
         - Sécurisation du mot de passe via hachage cryptographique (`password_hash` / `PASSWORD_BCRYPT`).
         - `hasRole(string $codeRole): bool` : Vérifie l'habilitation de l'utilisateur pour le filtrage des routes et contrôleurs.

- **Difficultés / Obstacles & Arbitrages de Conception** :
  - *Découplage Vente / Dette* : Une vente à crédit ne doit pas écraser les données de facturation d'origine. La création d'une entité `Dette` dédiée, rattachée à la fois à `Vente` et à `Client`, permet de gérer sereinement un échéancier et des versements fractionnés multiples (`Paiement[]`) sans dénaturer la facture initiale.
  - *Frontière Modèle / Service* : Veiller scrupuleusement à ce que les entités POO contiennent toute la logique métier pure (calculs, validations, invariants), tandis que les futurs services (`VenteService`, `DebtService`, `SupplyService`) prendront en charge l'orchestration technique (transactions PDO, commits, rollbacks, persistance).

---

#### Step 1.2 (20h30 - 22h00) : Schéma SQL Relationnel (PostgreSQL & SQLite)

- **Heure de réalisation** : 20h30 - 22h00
- **Ce qui a été fait** :

  1. **Conception du Schéma Relationnel Normalisé en 3FN** :
     - Modélisation de **15 tables relationnelles** dans les scripts `database/schema.sql` (PostgreSQL) et `database/schema_sqlite.sql` (SQLite) :
       - Référentiels : `roles`, `modes_paiement`, `statuts_dette`, `statuts_appro`, `categories`.
       - Acteurs & Tiers : `utilisateurs`, `clients`, `fournisseurs`.
       - Catalogue & Stock : `produits`.
       - Ventes Caisse : `ventes`, `lignes_vente`.
       - Créances & Règlements : `dettes`, `paiements`.
       - Approvisionnements : `approvisionnements`, `lignes_approvisionnement`.

  2. **Mise en Place de l'Intégrité Référentielle & Règles de Suppression (`ON DELETE`)** :
     - `ON DELETE CASCADE` appliqué aux entités dépendantes fortes (compositions) : la suppression d'une vente entraîne la suppression de ses `lignes_vente` ; la suppression d'une dette supprime ses `paiements` associés ; la suppression d'un approvisionnement supprime ses `lignes_approvisionnement`.
     - `ON DELETE RESTRICT` appliqué aux entités maîtresses pour interdire toute suppression accidentelle : un produit lié à des lignes de vente ou d'approvisionnement ne peut être supprimé ; un rôle attribué à des utilisateurs est protégé ; un client ayant un historique de dettes est protégé.
     - `ON DELETE SET NULL` appliqué aux associations facultatives (ex: catégorie d'un produit, client rattaché à une vente comptoir).

  3. **Sécurisation par Contraintes d'Intégrité Métier (`CHECK`)** :
     - `produits` :
       - `CHECK (prix_achat >= 0)` & `CHECK (prix_vente >= 0)`
       - `CHECK (prix_vente >= prix_achat)` : Interdiction formelle de vendre à perte au niveau du schéma.
       - `CHECK (qte_stock >= 0)` : Protection absolue contre les stocks physiques négatifs.
       - `CHECK (seuil_alerte >= 0)`
     - `clients` :
       - `CHECK (limite_credit >= 0)` & `CHECK (total_dettes_actuelles >= 0)`
     - `ventes` :
       - `CHECK (montant_total >= 0)`, `CHECK (montant_paye >= 0)`, `CHECK (montant_restant >= 0)`
     - `lignes_vente` :
       - `CHECK (quantite > 0)`, `CHECK (prix_unitaire >= 0)`, `CHECK (remise >= 0)`, `CHECK (sous_total >= 0)`
     - `dettes` :
       - `CHECK (montant_total >= 0)`, `CHECK (montant_restant >= 0)`
       - `CHECK (montant_restant <= montant_total)` : Cohérence arithmétique garantissant que le reste dû ne peut dépasser la créance d'origine.
     - `paiements` :
       - `CHECK (montant > 0)` : Interdiction d'enregistrer des versements nuls ou négatifs.
     - `lignes_approvisionnement` :
       - `CHECK (quantite > 0)`, `CHECK (prix_achat_unitaire >= 0)`

  4. **Optimisation des Performances par Indexation Ciblée** :
     - Index B-Tree créés sur toutes les clés étrangères (`idx_utilisateurs_role`, `idx_ventes_client`, `idx_ventes_user`, `idx_lignes_vente_vente`, `idx_lignes_vente_produit`, `idx_dettes_client`, `idx_paiements_dette`, `idx_appro_fournisseur`).
     - Index composites sur les requêtes fréquentes : `idx_produits_stock_alerte (qte_stock, seuil_alerte)` pour l'affichage instantané des alertes de rupture sur le Dashboard ; `idx_ventes_date (date_vente)` pour les clôtures de caisse journalières.

  5. **Stratégie de Données d'Amorçage (Seeding Réaliste)** :
     - Insertion des 4 rôles et de leurs descriptions.
     - Insertion des 5 modes de paiement (`ESPECES`, `WAVE`, `ORANGE_MONEY`, `CARTE_BANCAIRE`, `DETTE`).
     - Insertion des 3 statuts de dettes (`NON_SOLDEE`, `SOLDEE`, `EN_RETARD`) et des 3 statuts d'approvisionnement (`EN_ATTENTE`, `RECU`, `ANNULE`).
     - 4 utilisateurs prêts à l'emploi avec mots de passe hashés par `password_hash('password123', PASSWORD_BCRYPT)` :
       - Admin : `admin@storemanager.pro`
       - Vente : `vente@storemanager.pro`
       - Stock : `stock@storemanager.pro`
       - Inventaire : `inventaire@storemanager.pro`
     - 4 catégories d'articles, 3 fournisseurs dakarois, 4 clients avec historique de crédit (ex: Diop Amadou avec 45 000 FCFA d'encours, Babacar Faye avec 85 000 FCFA d'encours sur 100 000 FCFA autorisés).
     - 8 produits de test complets avec prix d'achat, prix de vente et stocks (Riz 25kg, Huile 5L, Sucre, Eau minérale, Soda, Savons, Javel, Lampes LED).
     - Ventes d'essai avec lignes de vente et dettes associées pour tester immédiatement les fonctionnalités de caisse et de recouvrement.

  6. **Validation & Tests Automatisés du Schéma SQLite** :
     - Test d'exécution directe via l'utilitaire CLI `sqlite3` sur une base locale temporaire.
     - Vérification de l'activation des contraintes avec `PRAGMA foreign_key_check;` : **0 erreur / intégrité 100% validée**.
     - Validation du comptage des enregistrements seedés (4 utilisateurs, 8 produits, 2 dettes actives).

- **Difficultés / Obstacles & Solutions Multi-SGBD** :
  - *Gestion des dialectes entre PostgreSQL et SQLite* :
    - Clés primaires auto-incrémentées : `SERIAL PRIMARY KEY` sous PostgreSQL vs `INTEGER PRIMARY KEY AUTOINCREMENT` sous SQLite.
    - Types booléens : type natif `BOOLEAN` sous PostgreSQL vs `INTEGER DEFAULT 1 CHECK (actif IN (0, 1))` sous SQLite.
    - Fonctions de manipulation de dates : `CURRENT_TIMESTAMP - INTERVAL '3 days'` sous PostgreSQL vs `DATETIME('now', '-3 days')` sous SQLite.
    - Précision monétaire : `NUMERIC(12, 2)` sous PostgreSQL vs `REAL` sous SQLite.
  - *Activation des clés étrangères sous SQLite* : Par défaut, SQLite désactive la vérification des clés étrangères. L'ajout impératif de la directive `PRAGMA foreign_keys = ON;` en en-tête du script garantit le respect strict des contraintes d'intégrité même en mode fallback local.

---

#### Step 1.3 (22h00 - 23h00) : Singleton Database & Fallback Automatique

- **Heure de réalisation** : 22h00 - 23h00
- **Ce qui a été fait** :

  1. **Implémentation du Design Pattern Singleton (`src/Core/Database.php`)** :
     - **Instance unique** stockée dans une variable statique privée `private static ?Database $instance = null`.
     - **Verrouillage de l'instanciation directe** : Constructeur privé `private function __construct()` pour interdire tout `new Database()`.
     - **Verrouillage de la duplication** : Méthode magique `private function __clone()` pour empêcher la copie d'objet.
     - **Verrouillage de la désérialisation** : Méthode `public function __wakeup()` levant explicitement une `Exception` pour empêcher la création d'instances fantômes par `unserialize()`.
     - **Point d'accès universel** : Méthode publique statique `public static function getInstance(): Database` et helper statique `public static function getPDO(): PDO`.

  2. **Mécanisme de Résilience & Fallback Automatique (`try / catch`)** :
     - **Étape 1 (Tentative PostgreSQL prioritaire)** : Récupération des paramètres de connexion (variables d'environnement `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` ou valeurs de production par défaut). Construction du DSN `pgsql:host=...;port=...;dbname=...;` et instanciation PDO sous bloc `try`.
     - **Étape 2 (Capture & Bascule SQLite)** : Si PostgreSQL est indisponible (serveur arrêté, mauvais identifiants ou réseau coupé), l'exception `\PDOException` est interceptée dans le bloc `catch`. Le système enregistre l'erreur et déclenche immédiatement la connexion locale SQLite sur `database/erp.db`.
     - **Étape 3 (Auto-initialisation / Self-healing)** : Si le fichier de base SQLite `database/erp.db` n'existe pas ou est vide (0 octet), la classe charge et exécute automatiquement le script `database/schema_sqlite.sql`. L'application est ainsi immédiatement opérationnelle sans intervention manuelle.
     - **Étape 4 (Activation des contraintes SQLite)** : Exécution immédiate de `PRAGMA foreign_keys = ON;` pour garantir l'intégrité référentielle en mode local.

  3. **Configuration Strictement Sécurisée de PDO** :
     - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` : Toutes les erreurs SQL (violations de contraintes `CHECK`, `FOREIGN KEY`, syntaxe) lèvent obligatoirement des exceptions `PDOException`, permettant une gestion propre dans les couches Services.
     - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` : Toutes les sélections retournent nativement des tableaux associatifs indexés par nom de colonne.
     - `PDO::ATTR_EMULATE_PREPARES => false` : Désactivation de l'émulation des requêtes préparées. Les requêtes sont préparées directement par le moteur du SGBD, offrant une protection native absolue contre les injections SQL.

  4. **Méthodes Utilitaires & Gestion Transactionnelle** :
     - Méthodes passerelles `beginTransaction()`, `commit()`, `rollBack()`, `inTransaction()` et `lastInsertId()` directement accessibles depuis l'instance `Database`, facilitant l'écriture de transactions atomiques dans les services métiers.
     - Méthodes d'inspection du driver actif : `getDriver(): string`, `isPgsql(): bool`, `isSqlite(): bool`, et `getConnectionMessage(): ?string`.

- **Difficultés / Obstacles & Solutions** :
  - *Gestion du double échec* : Si le serveur PostgreSQL ET le pilote SQLite échouent simultanément, la classe lève une `Exception` explicative détaillée combinant les deux messages d'erreur pour un diagnostic immédiat.
  - *Autonomie de la base SQLite* : Le mécanisme d'auto-création du répertoire `database/` et d'exécution automatique du fichier `schema_sqlite.sql` garantit le déploiement zéro-configuration sur tout nouvel environnement de développement.

---

### [Samedi - Phase 2] : POO, Repositories & Ventes POS

---

#### Step 2.1 (09h00 - 11h00) : Entités POO Pures & Logique Métier

- **Heure de réalisation** : 09h00 - 11h00
- **Ce qui a été fait** :

  1. **Configuration du Standard d'Autoloading PSR-4 (via Composer & Autoloader Natif de Secours)** :
     - Création du descripteur `composer.json` configurant le mapping PSR-4 `"App\\": "src/"` sans aucun framework ni ORM externe.
     - Implémentation complémentaire de `src/Core/Autoloader.php` pour assurer une portabilité totale dans tous les contextes d'exécution.

  2. **Création des 15 Classes d'Entités POO avec Encapsulation Stricte (`src/Model/Entity/`)** :
     - **Entités Référentielles** :
       - `Role` : Constantes de profils (`ADMIN`, `VENTE`, `STOCK`, `INVENTAIRE`), encapsulation des libellés et descriptions.
       - `StatutDette` : États des créances (`NON_SOLDEE`, `SOLDEE`, `EN_RETARD`), méthodes booléennes `isSoldee()`, `isEnRetard()`.
       - `ModePaiement` : Canaux d'encaissement (`ESPECES`, `WAVE`, `ORANGE_MONEY`, `CARTE_BANCAIRE`, `DETTE`), statut d'activation `isEstActif()`.
       - `StatutAppro` : États des bons de livraison (`EN_ATTENTE`, `RECU`, `ANNULE`), méthodes booléennes `isRecu()`, `isEnAttente()`.
       - `Categorie` : Classification du catalogue produits.
     - **Entités Acteurs & Sécurité** :
       - `User` : Gestion des profils et utilisateurs avec hachage cryptographique automatique (`password_hash` BCRYPT dans `setMotDePasse`), vérification via `verifierMotDePasse()`, et contrôle d'habilitation avec `hasRole()`.
       - `Fournisseur` : Coordonnées des grossistes et contacts directs.
       - `Client` : Gestion du risque client avec méthodes métiers `getCreditDisponible()`, `peutPrendreCredit(float $montant)`, `ajouterDette(float $montant)` avec levée d'exception en cas de dépassement du plafond, et `diminuerDette(float $montant)`.
     - **Entités Catalogue & Ventes (Composition Forte)** :
       - `Produit` : Calcul de rentabilité avec `calculerMarge()` et `calculerTauxMarge()`, détection des alertes stock avec `estEnAlerte()`, et sécurisation des mouvements de stock (`ajouterStock()`, `retirerStock()` avec contrôle d'invariance et exception si stock insuffisant).
       - `LigneVente` : Calcul automatique du sous-total ligne avec prise en compte des remises `calculerSousTotal()`.
       - `Vente` : Gestion du panier et des en-têtes de facturation, agrégation multi-lignes `calculerTotal()`, calcul du reste à payer, comptage physique `getNombreArticles()` et détection des ventes à crédit `estACredit()`.
       - `Commande` : Spécialisation et alias de `Vente` pour répondre aux spécifications fonctionnelles.
     - **Entités Créances & Versements (Cycle de Vie de Dette)** :
       - `Paiement` : Enregistrement des acomptes et règlements partiels avec référence de transaction et agent encaisseur.
       - `Dette` : Gestion du cycle de vie des créances clients. Méthode `enregistrerPaiement(Paiement $paiement)` qui déduit le solde restant, historise le versement et commute automatiquement le statut vers `SOLDEE` dès que `montantRestant <= 0`. Vérification d'échéance avec `estEnRetard()`.
     - **Entités Approvisionnements (Logique Réception BL)** :
       - `LigneApprovisionnement` : Sous-total par ligne de produit réceptionné `calculerSousTotal()`.
       - `Approvisionnement` : Traçabilité des bons de livraison (BL), totalisation automatique `calculerTotal()` et comptage des volumes `getNombreArticles()`.

  3. **Suite de Tests Unitaires & Validation Métier (`tests/test_entities.php`)** :
     - Écriture d'une batterie de tests automatisés couvrant 38 assertions critiques :
       - Encapsulation et typage strict PHP 8.3.
       - Hachage de mot de passe et habilitation RBAC.
       - Règle de solvabilité client (blocage et exception en cas de crédit supérieur au plafond autorisé).
       - Alerte de stock et décrémentation sécurisée.
       - Calculs de remises et sous-totaux panier.
       - Règlements fractionnés et transition automatique de statut `NON_SOLDEE` -> `SOLDEE`.
     - **Résultat : 38/38 tests validés avec succès (0 échec)**.

- **Difficultés / Obstacles & Solutions** :
  - *Gestion bidirectionnelle des relations et cohérence des IDs* : Lors de l'affectation d'un objet relationnel (ex: `$produit->setCategorie($categorie)` ou `$vente->setClient($client)`), l'entité synchronise automatiquement l'identifiant scalaire étranger (`categorieId`, `clientId`) afin de simplifier l'hydratation et la persistance en base de données.
  - *Immutabilité des invariants financiers* : Les méthodes `ajouterDette()` et `retirerStock()` interdisent formellement les états invalides (stock négatif, crédit supérieur au plafond autorisé) en levant explicitement des `InvalidArgumentException`.

---

#### Step 2.2 (11h00 - 13h00) : Repositories, Router, SessionManager & SQL Sécurisé PDO

- **Heure de réalisation** : 11h00 - 13h00
- **Ce qui a été fait** :

  1. **Architecture de la Couche d'Accès aux Données (Repository Pattern & Abstraction)** :
     - Création de `src/Model/Repository/RepositoryInterface.php` définissant le contrat générique de persistance (`findById`, `findAll`, `delete`, `count`).
     - Création de `src/Model/Repository/AbstractRepository.php` encapsulant la connexion PDO via `Database::getPDO()` et imposant l'implémentation de la méthode d'hydratation objet `hydrate(array $row): object`.
     - Découplage strict entre la représentation relationnelle SQL (tuples) et les objets du domaine métier POO (`Produit`, `Client`, `Fournisseur`, `Categorie`).

  2. **Implémentation des Classes Repository avec Requêtes Préparées PDO Strictes** :
     - **`ProduitRepository`** (`src/Model/Repository/ProduitRepository.php`) :
       - `findById(int $id): ?Produit` et `findByCode(string $code): ?Produit` avec jointure `LEFT JOIN categories` pour hydrater directement l'objet `Categorie` associé.
       - `findAll(): array` et `findByCategorie(int $categorieId): array`.
       - `findEnAlerteStock(): array` : Requête de détection proactive des articles sous seuil d'alerte (`qte_stock <= seuil_alerte`).
       - `search(string $term): array` : Recherche plein texte multi-colonnes insensible à la casse (`code`, `libelle`, `description`).
       - `save(Produit $produit): bool` : Gestion automatique de l'insertion (`INSERT` + récupération de `lastInsertId`) ou de la mise à jour (`UPDATE`).
       - Opérations atomiques sécurisées :
         - `decrementStock(int $id, int $quantite): bool` : Décrémentation atomique sous condition `qte_stock >= :qte` (sécurité absolue contre les surventes et les stocks négatifs).
         - `incrementStock(int $id, int $quantite): bool` : Incrémentation atomique lors des réceptions de marchandises.
         - `updateStock(int $id, int $nouvelleQte): bool` et `delete(int $id): bool`.
     - **`ClientRepository`** (`src/Model/Repository/ClientRepository.php`) :
       - `findById(int $id): ?Client` et `findByTelephone(string $telephone): ?Client`.
       - `findAll(): array` et `search(string $term): array`.
       - `findClientsAvecDettes(): array` : Récupération des clients ayant un encours de dette actif (`total_dettes_actuelles > 0`) triés par risque décroissant.
       - `findSolvablesPourCredit(float $montant): array` : Filtrage SQL des comptes éligibles à un nouveau crédit (`(limite_credit - total_dettes_actuelles) >= :montant`).
       - `save(Client $client): bool` (Insert / Update avec hydratation de l'identifiant généré).
       - `ajouterDette(int $id, float $montant): bool` : Incrémentation atomique de la dette avec vérification du plafond (`(total_dettes_actuelles + :montant) <= limite_credit`).
       - `diminuerDette(int $id, float $montant): bool` : Décrémentation atomique de la dette lors d'un versement.
       - `getTotalCreances(): float` : Agrégation SQL (`SUM`) du montant global des dettes en circulation.
     - **`FournisseurRepository`** (`src/Model/Repository/FournisseurRepository.php`) :
       - `findById(int $id): ?Fournisseur`, `findByTelephone(string $telephone): ?Fournisseur`, `findAll(): array`, `search(string $term): array`, `save(Fournisseur $fournisseur): bool`, `delete(int $id): bool`.
     - **`CategorieRepository`** (`src/Model/Repository/CategorieRepository.php`) :
       - Gestion du référentiel des catégories d'articles (`findById`, `findByCode`, `findAll`, `save`, `delete`).

  3. **Composants Core d'Infrastructure (`Router.php` & `SessionManager.php`)** :
     - **`Router`** (`src/Core/Router.php`) :
       - Moteur de routage HTTP POO fluide supportant les méthodes `GET` et `POST`.
       - Prise en charge des signatures `$router->get('/path', 'MonController', 'action')`, `$router->get('/path', [Controller::class, 'action'])` et `$router->get('/path', fn() => ...)`.
       - Résolution et extraction automatique des paramètres dynamiques d'URL via expressions régulières (ex: `/articles/{id}`).
       - Résolution automatique des contrôleurs dans l'espace de noms `App\Controller\`.
       - Gestionnaire 404 configurable et méthodes utilitaires de réponse `Router::redirect()` et `Router::json()`.
     - **`SessionManager`** (`src/Core/SessionManager.php`) :
       - Démarrage sécurisé (`session_start` avec cookies `httponly`, `samesite=Lax`, `use_strict_mode`).
       - Encapsulation des lectures/écritures de session (`get`, `set`, `has`, `remove`, `clear`, `destroy`).
       - Protection contre la fixation de session via `regenerateId()`.
       - Système de notifications éphémères (Flash Messages : `setFlash`, `getFlash`, `hasFlash`, `getFlashes`).
       - Gestion de l'état utilisateur authentifié (`setUser`, `getUser`, `isLoggedIn`, `logout`).

  4. **Front Controller Minimaliste & Respect de la Chaîne MVC (`public/index.php`)** :
     - Rôle unique et ciblé de `public/index.php` comme point d'entrée reliant l'environnement au composant `Router` :
       - Autoloading PSR-4 (`vendor/autoload.php`) et imports de namespaces (`use App\Core\Router;`, `use App\Core\SessionManager;`).
       - Démarrage de session (`SessionManager::start()`).
       - Instanciation et exécution du routeur (`$router = new Router(); $router->dispatch();`).
     - Respect strict de la chaîne de responsabilité MVC : `Index -> Router -> Controller -> Models/Repositories & Views`.

  5. **Suite de Tests Automatisés (`tests/test_repositories.php`)** :
     - Conception et exécution d'une batterie de tests couvrant 45 assertions :
       - Tests SessionManager (persistance, suppression, flash messages, logout).
       - Tests Router (routes statiques, placeholders dynamiques, 404).
       - Tests CategorieRepository (CRUD, recherche par code).
       - Tests ProduitRepository (CRUD, recherche multi-mots, filtres alertes, décrémentation/incrémentation atomique).
       - Tests ClientRepository (CRUD, recherche, calcul des créances, blocage si dépassement de plafond de crédit, remboursement).
       - Tests FournisseurRepository (CRUD, recherche téléphone/nom).
     - **Résultat : 45/45 tests validés avec succès (0 échec)**.

- **Difficultés / Obstacles & Solutions** :
  - *Portabilité et atomicité des décrémentations de stock* : Pour éviter les problèmes d'accès concurrents (Race Conditions) lors de ventes simultanées, la décrémentation est exécutée directement en une seule requête SQL atomique avec clause conditionnelle `WHERE id = :id AND qte_stock >= :qte`. Si le stock est insuffisant, `rowCount()` retourne 0 sans altérer la base.
  - *Cohérence des jointures et typage strict* : L'hydratation des produits instancie simultanément l'objet `Categorie` parent via le résultat du `LEFT JOIN`, assurant la complétude du graphe d'objets en mémoire sans requête N+1 supplémentaire.
  - *Autoloading PSR-4 et Namespaces propres* : L'utilisation exclusive de l'autoloading PSR-4 et des déclarations `use` dans `public/index.php` supprime les longues listes de `require_once`, assurant une structure propre, modulaire et maintenable.
  - *Architecture épurée du Router* : Le `Router.php` a été condensé à moins de 50 lignes lisibles, tout en supportant les contrôleurs avec méthodes, les closures, les paramètres dynamiques `{id}` et la gestion 404.

---

#### Step 2.3 (14h00 - 17h00) : Service Métier Vente POS & Transaction SQL

- **Heure de réalisation** : 14h00 - 17h00
- **Ce qui a été fait** :

  1. **Calculs & Préparation Métier du Panier** :
     - Implémentation des opérations de calcul dans `src/Service/VenteService.php` :
       - `calculerTotauxPanier(array $articles): array` : Calcule le total brut, le total des remises, le montant net à payer, le nombre total d'unités physiques et le nombre de références distinctes de manière purement agnostique de la session HTTP.
       - `preparerLigneArticle(int|string $produitIdOuCode, int $quantite = 1, float $remise = 0.0): array` : Recherche l'article par identifiant ou code, vérifie la disponibilité du stock physique et calcule le sous-total ligne avec remise.

  2. **Couche de Consultation & Statistiques Financières Intégrée** :
     - Méthodes `getVente(int $id): ?Vente` et `getVenteByFacture(string $num): ?Vente` avec requêtes préparées et hydratation complète (`LigneVente[]`, `Client`, `User`, `ModePaiement`).
     - Méthodes `getVentesDuJour(?DateTime $date)` et `getVentesClient(int $clientId)`.
     - Méthode `getStatistiquesDuJour()` calculant le CA du jour, le total encaissé cash/mobile money, le montant des crédits accordés et le panier moyen journalier.

  3. **Validation Transactionnelle Atomique sous PDO (`VenteService::validerVente()`)** :
     - **Contrôles préalables stricts** :
       - Vérification que le panier n'est pas vide (`InvalidArgumentException`).
       - Vérification de l'existence et du stock de chaque produit (`RuntimeException`).
       - Calcul du montant total net, du montant réglé et du solde restant dû (`montantRestant = max(0, total - montantPaye)`).
     - **Contrôle du risque client & invariance de solvabilité** :
       - Si la vente est à crédit (`montantRestant > 0` ou `modePaiementId = 5 DETTE`), vérification obligatoire de la présence d'un client.
       - Vérification de la règle d'invariance financière : `(total_dettes_actuelles + montantRestant) <= limite_credit`.
       - En cas de dépassement : levée immédiate d'une exception avec message détaillé indiquant la limite, l'encours et le disponible restant.
     - **Exécution transactionnelle atomique (`beginTransaction` / `commit` / `rollBack`)** :
       - Génération d'un numéro de facture unique au format `FACT-YYYYMMDD-XXXXXX`.
       - Insertion de l'en-tête de vente dans `ventes` via requête préparée PDO.
       - Pour chaque ligne : insertion dans `lignes_vente` et décrémentation atomique de stock sous condition SQL `UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte`. Si `rowCount === 0`, levée d'une exception de rupture concurrente pour annuler la transaction.
       - Si vente à crédit : insertion de la créance dans `dettes` (`statut_id = 1 NON_SOLDEE`), mise à jour atomique de l'encours client dans `clients` (`total_dettes_actuelles = total_dettes_actuelles + :reste`), et historisation d'un éventuel acompte initial dans `paiements`.
       - Validation finale par `commit()`.
       - Sécurisation absolue sous bloc `try / catch (\Throwable $e)` : exécution systématique de `rollBack()` si une transaction est active, garantissant zéro corruption de stock ou de données financières.

  4. **Suite de Tests Automatisés Complète (`tests/test_vente_service.php`)** :
     - Conception et exécution d'une batterie de tests couvrant **57 assertions critiques** :
       - Tests des calculs et préparations d'articles (ID/Code, remises, sous-totaux, détection de stock insuffisant).
       - Tests de vente au comptant (numéro de facture, totaux, décrémentation stock en BDD).
       - Tests des garde-fous (panier vide, client obligatoire pour vente à crédit).
       - Tests de vente à crédit autorisée (création dette `NON_SOLDEE`, augmentation de l'encours client, décrémentation stock).
       - Tests de blocage strict lors d'un dépassement de plafond de crédit et **vérification du ROLLBACK PDO** (aucun stock débité, aucun encours altéré, aucune vente fantôme insérée).
       - Tests de vente à crédit avec acompte partiel (historisation de l'acompte dans la table `paiements`).
       - Tests des méthodes de consultation et des indicateurs statistiques journaliers (CA, encaissé, crédit, panier moyen).
     - **Résultat : 57/57 tests validés avec succès (0 échec)**.

- **Difficultés / Obstacles & Solutions d'Architecture** :
  - *Purification du Service Métier* : Suppression complète de toute dépendance à la Session HTTP dans le Service. Le Service traite des données pures, laissant au contrôleur (`POSController`) la responsabilité de la session utilisateur.
  - *Protection contre les Race Conditions sur les stocks* : L'utilisation conjointe de la clause `WHERE qte_stock >= :qte` et du contrôle de `rowCount() > 0` au sein d'une transaction PDO garantit une étanchéité totale contre les surventes même en cas d'accès concurrents simultanés.
  - *Atomicité multitable & Intégrité financière* : L'encadrement dans une transaction PDO unifiée garantit que l'ensemble des opérations (`ventes`, `lignes_vente`, `produits`, `dettes`, `clients`, `paiements`) réussit de manière indivisible ou est intégralement annulé en cas d'incident (`rollBack()`).

---

#### Step 2.4 (17h00 - 20h00) : Controller POS & Vue Caisse

- **Heure de réalisation** : 17h00 - 20h00
- **Ce qui a été fait** :

  1. **Contrôleur Web POS (`src/Controller/POSController.php`)** :
     - Implémentation complète de la couche contrôleur selon le pattern MVC :
       - `index()` : Récupère le panier en session (`SessionManager`), les catégories, produits, clients avec encours, modes de paiement actifs et statistiques de vente journalières pour les injecter dans la vue.
       - `ajouterArticle()` (`POST /pos/ajouter`) : Valide les entrées, appelle `VenteService::preparerLigneArticle()` pour contrôler le stock en temps réel, stocke dans la session `pos_cart` et renvoie une réponse adaptée (redirection HTTP ou JSON asynchrone AJAX).
       - `modifierQuantite()` (`POST /pos/quantite`) : Ajuste la quantité d'un article ou le supprime si $\le 0$.
       - `supprimerArticle()` (`POST /pos/supprimer`) et `viderPanier()` (`POST /pos/vider`).
       - `validerVente()` (`POST /pos/valider`) : Traite la soumission de commande, extrait les données de paiement, coordonne `VenteService::validerVente()`, purge le panier de session, mémorise la facture pour impression et gère les messages flash de confirmation / erreur.
       - `facture(int $id)` (`GET /pos/facture/{id}`) : Rendu du ticket de caisse thermique autonome.

  2. **Interface Tactile Ergonomique de Caisse (`views/pos/index.php`)** :
     - Conception d'une interface POS split-screen haut de gamme aux teintes Dark Navy / Teal (`#0b0f19`, `#161e31`, `#2dd4bf`) :
       - **En-tête & KPIs en direct** : CA du jour, total encaissé, créances générées et nombre de tickets émis.
       - **Catalogue Produits Tactile & Live Search** : Barre de recherche instantanée par nom ou code-barres (raccourci `[F2]`), filtres par catégories avec compteur d'articles, cartes de produits avec badges de stock en temps réel (vert > seuil, jaune $\le$ alerte, rouge épuisé).
       - **Jauge Dynamique de Solvabilité Client** : Sélecteur de client acheteur affichant en temps réel le plafond autorisé, le cumul des dettes actuelles, le crédit disponible restant et une barre de progression de risque couleur.
       - **Panier Interactif & Clavier Numérique** : Table des lignes d'articles avec boutons d'ajustement `+` / `-`, sous-totaux dynamiques, calcul automatique des remises et écran digital à fort contraste du total net à payer en FCFA.
       - **Console d'Encaissement Multi-Modes** : Boutons tactiles pour Espèces, Wave, Orange Money, Carte Bancaire et Dette. Calcul automatique de la monnaie à rendre ou de l'encours de dette, billets rapides (+5 000, +10 000, +20 000, +50 000 F).
       - **Modal de Ticket / Facture Thermique** : Affichage d'un ticket de caisse conforme 80mm prêt pour impression directe (`window.print()`).

  3. **Routage & Intégration dans le Point d'Entrée (`public/index.php`)** :
     - Enregistrement des routes `/`, `/pos`, `/pos/ajouter`, `/pos/quantite`, `/pos/supprimer`, `/pos/vider`, `/pos/valider`, et `/pos/facture/{id}`.

  4. **Suite de Tests Automatisés du Contrôleur (`tests/test_pos_controller.php`)** :
     - Batterie de tests couvrant **24 assertions** : routage, gestion du panier en session (ajout, incrémentation, modification de quantité, suppression, purge), validation de vente comptant et rendu complet des vues sans erreurs de syntaxe.
     - **Résultat : 24/24 tests validés avec succès (0 échec)**.

  5. **Découpage Modulaire Structuré du Prototype (`storemanager_pro_app.html`)** :
     - Éclatement chirurgical du prototype monolithique en composants PHP réutilisables et vues d'écrans par acteur dans le répertoire `views/` :
       - **Layouts Communs** :
         - `views/layout/header.php` : Doctype, balises meta, police Google Font *Plus Jakarta Sans*, variables CSS et styles globaux.
         - `views/layout/navbar.php` : Barre de navigation supérieure avec onglets de permissions selon le rôle actif de l'utilisateur.
         - `views/layout/toast.php` : Composant de notifications flash dynamiques (Succès / Erreurs).
         - `views/layout/footer.php` : Scripts JS utilitaires globaux (pagination, formatage, toasts) et balises fermantes.
       - **Vues Métiers Dédiées** :
         - `views/auth/login.php` : Écran d'authentification split-screen avec sélecteur direct de rôles de démonstration.
         - `views/dashboard/index.php` : Tableau de bord administratif avec KPIs globaux, graphiques SVG et alertes de stock.
         - `views/pos/index.php` : Console de caisse tactile connectée au panier et aux clients.
         - `views/dettes/index.php` : Registre des créances actives avec modale de versement partiel et solde complet.
         - `views/approvisionnements/index.php` : Registre des réceptions fournisseurs avec modale de saisie de Bon de Livraison (BL).
         - `views/catalogue/index.php` : Gestion des répertoires produits, clients (avec plafonds de crédit) et fournisseurs.

  6. **Suite de Tests de Non-Régression des Vues (`tests/test_views.php`)** :
     - Vérification de l'instanciation et du rendu HTML de chaque vue découpée sans notice ni warning PHP.
     - **Résultat : 6/6 tests de vues validés**.

  7. **Refactorisation & Encapsulation du Routage HTTP (`src/Core/Router.php`)** :
     - Ajout de la méthode `$router->match(array $methods, string $path, string|callable $controller, ?string $action = null)` permettant d'enregistrer des routes multi-verbes (`GET` et `POST`) sans duplication de code.
     - Ajout de `Router::registerDefaultRoutes()` qui centralise l'intégralité des routes de l'application (`/`, `/pos`, `/pos/ajouter`, `/pos/quantite`, `/pos/supprimer`, `/pos/vider`, `/pos/valider`, `/pos/facture/{id}`).
     - Allègement maximal de `public/index.php` réduit à 11 lignes limpides.

- **Difficultés / Obstacles & Solutions d'Architecture** :
  - *Découplage Web/Métier rigoureux* : `POSController` s'occupe exclusivement du protocole HTTP, de la session (`$_SESSION` via `SessionManager`) et des formats de sortie, tandis que `VenteService` conserve l'intégralité de la logique métier et transactionnelle PDO.
  - *Compatibilité SQL Multi-SGBD* : Utilisation de la syntaxe SQL `WHERE est_actif = TRUE` pour assurer une compatibilité absolue à la fois sur PostgreSQL et SQLite.

---