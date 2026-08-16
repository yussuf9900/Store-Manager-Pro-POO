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

  2. **Interface Tactile Ergonomique de Caisse (`src/views/pos/index.php`)** :
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
     - Éclatement chirurgical du prototype monolithique en composants PHP réutilisables et vues d'écrans par acteur dans le répertoire `src/views/` :
       - **Layouts Communs** :
         - `src/views/layout/header.php` : Doctype, balises meta, police Google Font *Plus Jakarta Sans*, variables CSS et styles globaux.
         - `src/views/layout/navbar.php` : Barre de navigation supérieure avec onglets de permissions selon le rôle actif de l'utilisateur.
         - `src/views/layout/toast.php` : Composant de notifications flash dynamiques (Succès / Erreurs).
         - `src/views/layout/footer.php` : Scripts JS utilitaires globaux (pagination, formatage, toasts) et balises fermantes.
       - **Vues Métiers Dédiées** :
         - `src/views/auth/login.php` : Écran d'authentification split-screen avec sélecteur direct de rôles de démonstration.
         - `src/views/dashboard/index.php` : Tableau de bord administratif avec KPIs globaux, graphiques SVG et alertes de stock.
         - `src/views/pos/index.php` : Console de caisse tactile connectée au panier et aux clients.
         - `src/views/dettes/index.php` : Registre des créances actives avec modale de versement partiel et solde complet.
         - `src/views/approvisionnements/index.php` : Registre des réceptions fournisseurs avec modale de saisie de Bon de Livraison (BL).
         - `src/views/catalogue/index.php` : Gestion des répertoires produits, clients (avec plafonds de crédit) et fournisseurs.

  6. **Suite de Tests de Non-Regréssion des Vues (`tests/test_views.php`)** :
     - Vérification de l'instanciation et du rendu HTML de chaque vue découpée sans notice ni warning PHP.
     - **Résultat : 6/6 tests de vues validés**.

  7. **Refactorisation & Encapsulation du Routage HTTP (`src/Core/Router.php`)** :
     - Ajout de la méthode `$router->match(array $methods, string $path, string|callable $controller, ?string $action = null)` permettant d'enregistrer des routes multi-verbes (`GET` et `POST`) sans duplication de code.
     - Centralisation de l'enregistrement des routes dans `Router::registerDefaultRoutes()`.

---

### [Dimanche - Phase 3] : Dettes, Approvisionnements, Rôles & Clôture

---

#### Step 3.1 (09h00 - 11h30) : Gestion des Dettes & Remboursements

- **Heure de réalisation** : 09h00 - 11h30
- **Ce qui a été fait** :

  1. **Couche d'Accès aux Données Dette & Versements (src/Model/Repository/DetteRepository.php)** :
     - Implémentation complète de DetteRepository héritant de AbstractRepository :
       - findById(int $id) : Chargement complet d'une dette avec jointures clients, statuts_dette, ventes, ainsi que les collections de règlements paiements[] et les articles vendus lignes_vente[].
       - findAll() et findDettesActives() : Récupération des dettes en cours (montant_restant > 0 et statut_id != 2).
       - findByClient(int $clientId) et findByStatut(int $statutId).
       - findEnRetard() : Détection des créances échues non réglées.
       - save(Dette $dette) et updateMontantRestantEtStatut(int $detteId, float $montantRestant, int $statutId).
       - savePaiement(Paiement $paiement) et findPaiementsByDetteId(int $detteId) : Enregistrement et historisation des règlements.
       - findLignesVenteByVenteId(int $venteId) : Récupération des articles d'origine de la vente à crédit.
       - Agrégats financiers : getTotalEncours(), getTotalRecouvrements(), getTotalCreancesInitiales(), countActives(), countSoldees().

  2. **Service Métier Transactionnel de Recouvrement (src/Service/DebtService.php)** :
     - Contrôles de validité stricts : rejet des montants nuls ou négatifs, rejet des versements sur une dette déjà soldée, blocage si le versement excède le reste dû.
     - Transaction PDO atomique (beginTransaction / commit / rollBack) :
       - Insertion du règlement dans la table paiements.
       - Calcul du nouveau reste dû et bascule automatique en statut SOLDEE (ID 2) dès que nouveauReste <= 0.0.
       - Décrémentation atomique de l'encours client dans clients via ClientRepository::diminuerDette() pour libérer immédiatement son plafond de crédit disponible.
       - Méthode de règlement total soldeTotalDette() et calcul des statistiques de recouvrement getStatistiquesDettes().

  3. **Contrôleur Web Synchrone (src/Controller/DetteController.php)** :
     - Fonctionnement 100% MVC synchrone :
       - index() : Récupération des dettes et des statistiques financières pour injection dans la vue.
       - rembourser() : Traitement des données POST, exécution du service de remboursement, enregistrement des messages flash de notification dans SessionManager, et redirection HTTP standard (Location: /dettes).

  4. **Enregistrement des Routes (src/Core/Router.php)** :
     - Enregistrement des routes GET /dettes et POST /dettes/rembourser dans Router::registerDefaultRoutes().

  5. **Interface Utilisateur Conforme au Prototype (src/views/dettes/index.php)** :
     - Reprise exacte du template issu de storemanager_pro_app.html (lignes 1475 à 1897) purement dynamisé :
       - 3 cartes de statistiques : Créances Actives, Clients Débiteurs, Total Recouvrements.
       - Registre des dettes avec filtrage de recherche instantané.
       - 3 tiroirs d'actions activables par ligne :
         - Articles : Affiche le tableau des produits de la vente à crédit (Produit, Qté, P.U., Sous-total).
         - Paiements : Affiche l'historique des règlements enregistrés (Date, Versement, Mode).
         - Rembourser : Formulaire de remboursement POST /dettes/rembourser avec boutons raccourcis (Tout solder, 50%), champ montant pré-rempli et sélecteur de canal de paiement (Cash, Wave, OM, Carte).

  6. **Suite de Tests Automatisés (tests/test_debt_service.php)** :
     - Batterie de tests couvrant 42 assertions validées avec 100% de succès.

- **Difficultés / Obstacles & Solutions d'Architecture** :
  - Atomicité multi-tables lors du remboursement : Un versement impacte simultanément les tables paiements, dettes et clients. L'encadrement dans une transaction PDO unifiée garantit l'intégrité absolue sans incohérence financière.
  - Rendu modulaire et ergonomie sans AJAX : L'utilisation de tiroirs locaux en CSS/JS (toggleDetails) combinée au pattern Post-Redirect-Get offre une expérience utilisateur fluide tout en respectant l'architecture MVC synchrone standard.

---

#### Step 3.2 (11h30 - 13h30) : Approvisionnements & Réception BL

- **Heure de réalisation** : 11h30 - 13h30
- **Ce qui a été fait** :

  1. **Couche d'Accès aux Données Approvisionnements (`src/Model/Repository/ApprovisionnementRepository.php`)** :
     - Implémentation complète de `ApprovisionnementRepository` héritant de `AbstractRepository` et implémentant `RepositoryInterface` :
       - `findById(int $id)` et `findByNumeroBL(string $numeroBL)` avec jointures `fournisseurs`, `statuts_appro`, `utilisateurs` et chargement de la collection `LigneApprovisionnement[]` avec leurs entités `Produit` associées.
       - `findAll()`, `findByFournisseur(int $fournisseurId)`, `findByStatut(int $statutId)`, `findEnAttente()`, `findRecus()`.
       - `save(Approvisionnement $appro)` (Insert/Update) et `saveLigne(LigneApprovisionnement $ligne)`.
       - `updateStatut(int $approId, int $statutId)` et `updateMontantTotal(int $approId, float $montantTotal)`.
       - `getTotalCoutEntrees(): float` : Agrégation SQL du montant global des achats de marchandises entrées en stock.
       - `count()`, `countRecus()`, `countEnAttente()`, `delete(int $id)`.

  2. **Service Métier Transactionnel d'Approvisionnement (`src/Service/SupplyService.php`)** :
     - `creerApprovisionnement(int $fournisseurId, array $articles, int $userId = 1, ?string $numeroBL = null, bool $receptionnerImmediatement = false)` :
       - Contrôles d'intégrité : vérification de l'existence du fournisseur, contrôle des articles (quantités > 0, prix d'achat >= 0).
       - Génération automatique du numéro de Bon de Livraison (format `BL-{FOURNISSEUR}-{NUM}`) sous transaction PDO.
       - Enregistrement de l'en-tête et des lignes associées.
     - `receptionnerBL(int $approvisionnementId, ?array $quantitesLivrees = null, int $userId = 1): array` :
       - Contrôles d'invariance : rejet formel si le BL est déjà réceptionné (`statut_id = 2`) ou annulé (`statut_id = 3`), rejet des quantités négatives.
       - Transaction PDO atomique (`beginTransaction` / `commit` / `rollBack`) :
         - Parcours des lignes de produits et prise en compte des éventuels ajustements de quantités réelles reçues.
         - **Incrémentation automatique et atomique du stock physique** des produits en magasin via `ProduitRepository::incrementStock(produitId, quantite)`.
         - Recalcul du montant total du lot et bascule automatique du statut en `RECU` (`statut_id = 2`).
         - Sécurisation sous `try / catch (\Throwable)` avec `rollBack()` systématique en cas d'erreur.
     - Consultation et statistiques : `getApprovisionnement()`, `getApprovisionnementByBL()`, `getAllApprovisionnements()`, `getStatistiquesAppro()`.

  3. **Contrôleur Web & Routage MVC (`src/Controller/SupplyController.php` & `src/Core/Router.php`)** :
     - `index()` : Récupère les approvisionnements, statistiques, fournisseurs et produits pour injection dans la vue.
     - `receptionner()` : Traite la validation de réception transmise en POST, délègue à `SupplyService::receptionnerBL()`, génère les notifications Flash dans `SessionManager` et redirige selon le pattern PRG (Post-Redirect-Get) vers `/supplies`.
     - `creer()` : Permet la création de nouveaux BL.
     - Enregistrement des routes `/supplies`, `/approvisionnements`, `/supplies/receptionner`, `/approvisionnements/receptionner`, `/supplies/creer` dans `Router.php`.

  4. **Interface Utilisateur Conforme au Prototype (`src/views/approvisionnements/index.php`)** :
     - Dynamisation complète du template issu de `storemanager_pro_app.html` (lignes 1900 à 2220) :
       - 3 cartes de statistiques : Coût Total des Entrées, Bons de Réception (BL), Fournisseurs Actifs.
       - Tableau principal des Bordereaux de Livraison avec statuts dynamiques (`badge-success REÇU` vs `badge-warning EN COURS`).
       - Tiroir 1 (`supply-details-{id}`) : Liste détaillée des lignes d'articles, quantités livrées, coûts unitaires et totaux.
       - Tiroir 2 (`supply-receive-drawer-{id}`) : Formulaire interactif de réception de stock permettant d'ajuster les quantités reçues par article et de valider en direct l'entrée en magasin.

  5. **Suite de Tests Automatisés (`tests/test_supply_service.php`)** :
     - Batterie de tests couvrant 34 assertions : consultation repository, création de BL en attente, réception avec incrémentation de stock vérifiée en BDD, gestion des erreurs (rejet double réception, BL inexistant, panier vide), réception avec ajustement de quantités et rendu complet de la vue.
     - **Résultat : 34/34 tests validés avec 100% de succès**.

- **Difficultés / Obstacles & Solutions d'Architecture** :
  - *Atomicité de la réception et incrémentation de stock* : La réception d'un Bon de Livraison doit garantir que la mise à jour des statuts du BL et l'incrémentation des stocks de chaque référence s'effectuent de façon indivisible sous transaction PDO. En cas d'incident sur un seul article, le `rollBack()` restaure l'état exact sans modification fantôme de stock.
  - *Gestion des écarts de livraison* : Le service supporte la réception partielle ou ajustée via le tableau `$quantitesLivrees`, permettant au gestionnaire de stock de saisir la quantité physique réellement constatée lors du déchargement.

---

#### Step 3.3 (14h30 - 16h30) : AuthManager & Contrôle des Rôles

- **Commit Git associé** : `git commit -m "feat(auth): implementation de l'authentification multi-profils et restriction des acces par role"`
- **Heure de réalisation** : 14h30 - 16h30
- **Ce qui a été fait** :

  1. **Couche d'Accès aux Données Utilisateurs (`src/Model/Repository/UserRepository.php`)** :
     - Implémentation de `UserRepository` héritant de `AbstractRepository` avec requêtes préparées PDO et jointure sur la table `roles` :
       - `findById(int $id)` et `findByEmail(string $email)` avec gestion de la casse et résolution croisée des domaines de démonstration (`@storemanager.sn` et `@storemanager.pro`).
       - `findByRole(int $roleId)` et `findByRoleCode(string $code)` avec filtrage automatique sur les comptes actifs (`u.actif = true`).
       - `save(User $user)` (Insertion / Mise à jour avec hachage sécurisé et gestion des types booléens multi-SGBD), `delete(int $id)`, `count()`.
       - Hydratation complète de l'entité `User` avec son entité `Role` associée (`id`, `code`, `libelle`, `description`).

  2. **Service Centralisé d'Authentification & Contrôle d'Accès RBAC (`src/Service/AuthManager.php`)** :
     - `authenticate(string $email, string $password): ?User` :
       - Validation stricte des entrées (email et mot de passe non vides).
       - Recherche utilisateur et contrôle du statut actif (`isActif()`).
       - Vérification cryptographique du mot de passe avec `password_verify($password, $hash)`.
       - Enregistrement en session via `SessionManager::setUser()` et stockage des variables de session (`user_id`, `user_nom`, `user_prenom`, `user_email`, `user_role`, `user_role_id`).
       - Régénération sécurisée de l'ID de session (`SessionManager::regenerateId(true)`) pour contrer les attaques par fixation de session.
     - `authenticateQuickProfile(string $roleCode): ?User` :
       - Connexion instantanée sans mot de passe pour les 4 profils de démonstration du prototype (*Admin Boutique*, *Chargé de Vente*, *Chargé de Stock*, *Inventaire*).
     - `logout(): void` :
       - Déconnexion et destruction complète de la session.
     - Contrôle des Habilitations RBAC (Role-Based Access Control) :
       - `hasRole(string|array $roles): bool` : Vérifie les habilitations avec accès super-utilisateur accordé au profil `ADMIN`.
       - `requireRole(string|array $roles): User` : Guard / filtre d'interception bloquant les accès non autorisés et effectuant les redirections avec messages flash d'erreur.
       - `getDefaultRouteForUser(?User $user = null): string` : Détermine l'URL d'atterrissage adaptée à chaque métier (`ADMIN` $\rightarrow$ `/dashboard`, `VENTE` $\rightarrow$ `/pos`, `STOCK` $\rightarrow$ `/supplies`, `INVENTAIRE` $\rightarrow$ `/catalog`).

  3. **Contrôleur Web & Routage d'Authentification (`src/Controller/AuthController.php` & `src/Core/Router.php`)** :
     - `login()` : Traitement GET (affichage de la vue de connexion) et POST (authentification email/mot de passe ou profil rapide), génération des messages flash (`SessionManager::setFlash`) et redirection HTTP.
     - `logout()` : Déconnexion de l'utilisateur et redirection vers `/login`.
     - Déclaration des routes `GET /login`, `POST /login`, `GET /logout`, `POST /logout` dans `Router.php`.

  4. **Adaptation Dynamique de la Barre de Navigation (`src/views/layout/navbar.php`)** :
     - Filtrage conditionnel des onglets selon les droits du profil connecté :
       - Profil `ADMIN` : Accès à l'intégralité des onglets (*Tableau de Bord*, *Ventes / POS*, *Gestion Dettes*, *Approvisionnements*, *Produits & Tiers*).
       - Profil `VENTE` : Accès limité aux onglets *Ventes / POS* et *Gestion Dettes*.
       - Profil `STOCK` : Accès limité aux onglets *Approvisionnements* et *Produits & Tiers*.
       - Profil `INVENTAIRE` : Accès limité à l'onglet *Produits & Tiers*.
     - Affichage du rôle et du nom d'utilisateur actif avec bouton fonctionnel `Déconnexion 🚪` vers `/logout`.

  5. **Écran de Connexion Split-Screen Dynamique (`src/views/auth/login.php`)** :
     - Préservation stricte de la structure visuelle haut de gamme issue de `storemanager_pro_app.html` (lignes 380 à 480).
     - Prise en charge des 4 cartes de profils rapides cliquables (`AB`, `CV`, `CS`, `IV`), du formulaire classique et de l'affichage des notifications flash d'erreur/succès.

  6. **Suite de Tests Automatisés (`tests/test_auth.php`)** :
     - Batterie de tests couvrant 48 assertions validées à 100% : consultation et hydratation `UserRepository`, authentification valide/invalide `AuthManager`, 4 profils de démonstration, vérifications RBAC `hasRole` et `requireRole`, instanciation et actions `AuthController`, rendu dynamique de la barre de navigation selon chaque rôle.
     - **Résultat : 48/48 tests validés avec succès (0 échec)**.

- **Difficultés / Obstacles & Solutions d'Architecture** :
  - *Gestion multi-SGBD du type booléen (`actif`)* : En PostgreSQL, la colonne `actif` est typée `BOOLEAN` alors qu'en SQLite elle est représentée par `INTEGER (0/1)`. Les requêtes préparées ont été adaptées avec `bindValue(':actif', ..., PDO::PARAM_BOOL)` et `u.actif = true` afin d'assurer une compatibilité native sur les deux moteurs.
  - *Sécurité et étanchéité des sessions* : La méthode `AuthManager::loginUser()` appelle systématiquement `SessionManager::regenerateId(true)` dès qu'une authentification réussit, empêchant le vol de session (Session Fixation).
  - *Fluidité multi-profils* : Le système de profils rapides permet de basculer instantanément entre les 4 profils métiers lors des démonstrations et des évaluations tout en respectant l'authentification formelle sous-jacente.

---

#### Step 3.4 (16h30 - 18h00) : Rédaction de l'Autopsie des 3 Méthodes Clés & Finalisation

- **Commit Git associé** : `git commit -m "docs(devlog): finalisation du journal de bord DEVLOG.md et autopsie des 3 methodes cles"`
- **Heure de réalisation** : 16h30 - 18h00
- **Ce qui a été fait** :

  1. **Consolidation Globale & Revue d'Architecture** :
     - Vérification de l'étanchéité des couches architecturales (*Core*, *Model/Entity*, *Model/Repository*, *Service*, *Controller*, *Views*).
     - Contrôle strict du principe de responsabilité unique (SRP) : les entités contiennent la logique métier pure et les invariants, les repositories gèrent la persistance PDO préparée, les services orchestrent les transactions atomiques, les contrôleurs traitent les requêtes HTTP/Session/PRG et les vues assurent le rendu ergonomique.
     - Validation de la chaîne de routage avec `src/Core/Router.php` et allègement du Front Controller `public/index.php`.

  2. **Exécution Complète de la Suite de Tests Automatisés (294 assertions)** :
     - Lancement séquentiel des 8 scripts de tests automatisés :
       - `tests/test_entities.php` : 38 assertions (POO pure, encapsulation, calculs métiers, invariants).
       - `tests/test_repositories.php` : 45 assertions (CRUD, requêtes préparées, décrémentation/incrémentation atomique).
       - `tests/test_vente_service.php` : 57 assertions (validation vente, transactions PDO, contrôle plafond crédit, rollback).
       - `tests/test_pos_controller.php` : 24 assertions (actions panier en session, calculs dynamiques, flux caisse).
       - `tests/test_views.php` : 6 assertions (rendu HTML des vues sans notices/warnings PHP).
       - `tests/test_debt_service.php` : 42 assertions (remboursements partiels/totaux, mise à jour encours, bascule `SOLDEE`).
       - `tests/test_supply_service.php` : 34 assertions (création BL, réception, incrémentation automatique de stock).
       - `tests/test_auth.php` : 48 assertions (authentification, 4 profils de démo, gardes RBAC, navbar dynamique).
     - **Résultat global : 294/294 assertions réussies avec un taux de succès de 100% (0 échec)**.

  3. **Rédaction Approfondie de la Section 2 : Autopsie de 3 Méthodes Clés** :
     - Analyse chirurgicale ligne par ligne des 3 méthodes fondamentales pour l'épreuve orale :
       - `Database::getInstance()` (`src/Core/Database.php`)
       - `VenteService::validerVente()` (`src/Service/VenteService.php`)
       - `DebtService::enregistrerRemboursement()` (`src/Service/DebtService.php`)
     - Intégration de fiches de révision structurées avec questions pièges, justifications architecturales et réponses types pour la soutenance.

  4. **Finalisation du Dossier de Conception & Documentation Globale** :
     - Rédaction du `README.md` principal à la racine avec présentation détaillée de l'ERP, matrice des profils, guide de déploiement et commandes de tests.
     - Alignement rigoureux avec `charte_projet_etudiants.md` et `planning_weekend_etudiants.md`.

---

## 2. Autopsie de 3 Méthodes Clés (Indispensable pour l'oral)

Ce volet constitue le **guide d'analyse technique et de révision pour la soutenance individuelle orale**. Chaque méthode y est décortiquée dans son rôle métier, son implémentation technique ligne par ligne, ses choix d'architecture et les questions d'évaluation typiques du formateur.

---

### 🔍 Méthode 1 : `Database::getInstance()` (Design Pattern Singleton & Fallback Résilient)

- **Fichier** : `src/Core/Database.php` (Lignes 30 à 37, couplée à `initConnection()` lignes 73 à 133)
- **Rôle Métier** : Garantir un accès unique, universel et hautement disponible à la base de données de l'ERP pour toute l'application, en absorbant de manière totalement transparente les coupures ou indisponibilités de PostgreSQL grâce à un basculement immédiat et autonome vers une base locale SQLite persistante.
- **Rôle Technique** :
  - Implémentation stricte du **Design Pattern Singleton** : constructeur privé, clonage privé désactivé, désérialisation bloquée (`__wakeup()`).
  - **Mécanisme de Fallback à 2 Niveaux** sous blocs `try / catch (\PDOException)` : tentative de connexion PostgreSQL prioritaire, capture de l'erreur réseau/SGBD, puis bascule automatique sur SQLite `database/erp.db`.
  - **Self-Healing (Auto-initialisation)** : si le fichier SQLite n'existe pas ou pèse 0 octet, la méthode crée le dossier `database/`, charge le script DDL `database/schema_sqlite.sql` et initialise la base complète avec ses données d'amorçage.
  - **Sécurisation PDO** : forçage de `ERRMODE_EXCEPTION`, `FETCH_ASSOC` et désactivation de l'émulation des requêtes préparées (`EMULATE_PREPARES => false`).
  - **Activation des Clés Étrangères SQLite** : exécution impérative de `PRAGMA foreign_keys = ON;`.

#### 📜 Extrait de Code Réel :

```php
// Extrait de src/Core/Database.php

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private string $driver = 'unknown';
    private ?string $connectionMessage = null;

    private function __construct()
    {
        $this->initConnection();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Impossible de désérialiser une instance Singleton de " . __CLASS__);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function getPDO(): PDO
    {
        return self::getInstance()->getConnection();
    }

    private function initConnection(): void
    {
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pgHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
            $pgPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5433');
            $pgDb   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'storemanager_db');
            $pgUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'ichigo');
            $pgPass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? 'password');

            $dsnPgsql = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDb};";

            $this->connection = new PDO($dsnPgsql, $pgUser, $pgPass, $pdoOptions);
            $this->driver = 'pgsql';
            $this->connectionMessage = "Connexion PostgreSQL établie ({$pgHost}:{$pgPort}/{$pgDb}).";
            return;
        } catch (PDOException $pgException) {
            $pgError = $pgException->getMessage();
        }

        try {
            $baseDir = dirname(__DIR__, 2);
            $sqliteFile = getenv('DB_SQLITE_PATH') ?: ($_ENV['DB_SQLITE_PATH'] ?? $baseDir . '/database/erp.db');

            $sqliteDir = dirname($sqliteFile);
            if (!is_dir($sqliteDir)) {
                mkdir($sqliteDir, 0755, true);
            }

            $isNewDatabase = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;

            $dsnSqlite = "sqlite:" . $sqliteFile;
            $this->connection = new PDO($dsnSqlite, null, null, $pdoOptions);
            $this->driver = 'sqlite';

            $this->connection->exec("PRAGMA foreign_keys = ON;");

            if ($isNewDatabase) {
                $schemaFile = $baseDir . '/database/schema_sqlite.sql';
                if (!file_exists($schemaFile)) {
                    $schemaFile = $baseDir . '/schema_sqlite.sql';
                }

                if (file_exists($schemaFile)) {
                    $schemaSql = file_get_contents($schemaFile);
                    $this->connection->exec($schemaSql);
                }
            }

            $this->connectionMessage = "Bascule sur SQLite réussie ({$sqliteFile}).";
        } catch (PDOException $sqliteException) {
            throw new Exception(
                "Erreur connexion BDD : Échec PostgreSQL ({$pgError}) et échec SQLite ({$sqliteException->getMessage()})"
            );
        }
    }
}
```

#### 🔬 Explication Ligne par Ligne :

- **Ligne 11 : `private static ?Database $instance = null;`**  
  Déclare la propriété statique privée qui conservera l'unique référence de l'objet `Database` en mémoire durant tout le cycle de vie du script PHP.
- **Ligne 16-19 : `private function __construct() { $this->initConnection(); }`**  
  Le constructeur est déclaré avec la visibilité `private`. Cela interdit formellement à tout code externe d'instancier un nouvel objet via l'opérateur `new Database()`, forçant le passage par `getInstance()`. À l'instanciation interne, il appelle `initConnection()`.
- **Ligne 21-23 : `private function __clone() {}`**  
  Rend la méthode magique `__clone` inaccessible depuis l'extérieur, interdisant le clonage d'instance (`clone $db`).
- **Ligne 25-28 : `public function __wakeup() { throw new Exception(...); }`**  
  Empêche la réinstanciation de l'objet par désérialisation binaire (`unserialize()`), fermant la dernière faille possible contre le principe d'instance unique.
- **Ligne 30-37 : `public static function getInstance(): Database`**  
  Point d'accès global statique (*Lazy Loading*). Si `self::$instance` est encore `null` (premier appel), elle instancie l'unique objet `new self()`. Les appels ultérieurs retournent directement l'instance déjà présente en mémoire sans ré-exécuter le processus d'initialisation.
- **Ligne 39-42 : `public static function getPDO(): PDO`**  
  Helper statique de commodité permettant aux repositories d'obtenir l'objet `PDO` natif en une seule ligne : `Database::getPDO()`.
- **Lignes 75-79 : `$pdoOptions = [...]`**  
  Définit les options globales de sécurité et d'intégrité PDO :
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` : Oblige PDO à lever des exceptions `PDOException` pour toute erreur SQL (violation de contrainte, faute de syntaxe, panne).
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` : Les résultats des requêtes sont automatiquement convertis en tableaux associatifs indexés par les noms de colonnes SQL.
  - `PDO::ATTR_EMULATE_PREPARES => false` : Désactive l'émulation logicielle PHP des requêtes préparées. Les requêtes sont transmises au moteur SGBD pour une véritable préparation et une immunité absolue contre les injections SQL.
- **Lignes 81-93 : `try { ... PostgreSQL ... }`**  
  Bloc prioritaire de tentative de connexion PostgreSQL. Récupère les variables d'environnement (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`), compose la chaîne DSN `pgsql:host=...` et instancie l'objet `PDO`. Si la connexion réussit, `$this->driver` passe à `'pgsql'` et la fonction se termine immédiatement par `return;`.
- **Lignes 94-96 : `catch (PDOException $pgException) { $pgError = ...; }`**  
  Intercepte l'exception si le serveur PostgreSQL est éteint, injoignable ou mal configuré. La variable `$pgError` stocke le message de diagnostic, et l'exécution continue sans planter pour déclencher le fallback.
- **Lignes 98-128 : `try { ... SQLite Fallback ... }`**  
  Bloc de secours. Détermine le chemin absolu du fichier `database/erp.db`. Si le dossier parent n'existe pas, il le crée via `mkdir($sqliteDir, 0755, true)`.
- **Ligne 107 : `$isNewDatabase = !file_exists($sqliteFile) || filesize($sqliteFile) === 0;`**  
  Détecte si la base SQLite locale est vierge (fichier inexistant ou vide à 0 octet).
- **Ligne 110 : `$this->connection = new PDO("sqlite:" . $sqliteFile, ...);`**  
  Crée la connexion PDO SQLite locale sur le fichier.
- **Ligne 113 : `$this->connection->exec("PRAGMA foreign_keys = ON;");`**  
  **Instruction vitale** : active la vérification des clés étrangères dans SQLite (désactivée par défaut par le moteur SQLite), garantissant le respect strict des contraintes d'intégrité référentielle.
- **Lignes 115-125 : `if ($isNewDatabase) { ... exec($schemaSql); }`**  
  Si la base vient d'être créée, le script lit `database/schema_sqlite.sql` et exécute l'intégralité du DDL et des insertions de seeding (rôles, utilisateurs, catégories, produits, dettes d'essai).
- **Lignes 129-132 : `catch (PDOException $sqliteException)`**  
  Si PostgreSQL ET SQLite échouent tous les deux, une `Exception` claire est levée détaillant les causes des deux échecs consécutifs.
---

### 🔍 Méthode 2 : `VenteService::validerVente()` (Cœur Transactionnel de Caisse & Contrôle de Risque)

- **Fichier** : `src/Service/VenteService.php` (Lignes 108 à 307)
- **Rôle Métier** : Valider l'encaissement d'une vente en caisse tactile POS. Elle vérifie la solvabilité du client en cas de vente à crédit (contrôle du plafond `limite_credit`), vérifie et décrémente en temps réel le stock physique des produits, enregistre la facture et les lignes de vente, crée automatiquement la créance dans le registre des dettes si un reste à payer subsiste, et historise les éventuels acomptes.
- **Rôle Technique** :
  - Orchestration sous **Transaction PDO unifiée** (`beginTransaction()`, `commit()`, `rollBack()`).
  - **Requêtes préparées PDO strictes** contre les injections SQL.
  - **Sécurisation contre les accès concurrents (Race Conditions)** : décrémentation SQL conditionnelle `UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte` et vérification de `rowCount() === 0`.
  - **Règle d'invariance financière client** : `(total_dettes_actuelles + montantRestant) <= limite_credit` vérifiée via la méthode métier `Client::peutPrendreCredit()`.
  - **Rollback automatique et complet** sous bloc `catch (Throwable $e)` : en cas de rupture de stock concurrente ou d'erreur SQL, aucune table n'est corrompue et l'état initial est restauré.

#### 📜 Extrait de Code Réel :

```php
// Extrait de src/Service/VenteService.php

public function validerVente(
    int $userId,
    ?int $clientId = null,
    int $modePaiementId = 1,
    float $montantPaye = 0.0,
    array $articles = [],
    ?DateTime $dateEcheance = null
): Vente {
    // 1. Contrôle préalable du panier
    if (empty($articles)) {
        throw new InvalidArgumentException("Impossible de valider la transaction : le panier de vente est vide.");
    }

    $lignesPreparees = [];
    $montantTotalCalcule = 0.0;

    // 2. Vérification unitaire des articles et du stock physique
    foreach ($articles as $item) {
        $produitId = (int)($item['produit_id'] ?? $item['id'] ?? 0);
        $quantite = (int)($item['quantite'] ?? 1);
        $remise = max(0.0, (float)($item['remise'] ?? 0.0));

        if ($produitId <= 0 || $quantite <= 0) {
            throw new InvalidArgumentException("Données d'article invalides.");
        }

        $produit = $this->produitRepository->findById($produitId);
        if (!$produit) {
            throw new InvalidArgumentException("L'article ID #{$produitId} n'existe pas.");
        }

        if ($produit->getQteStock() < $quantite) {
            throw new RuntimeException(
                sprintf("Stock insuffisant pour l'article '%s' : Demandé = %d, Disponible = %d.",
                    $produit->getLibelle(), $quantite, $produit->getQteStock())
            );
        }

        $prixUnitaire = isset($item['prix_unitaire']) && (float)$item['prix_unitaire'] > 0
            ? (float)$item['prix_unitaire']
            : $produit->getPrixVente();

        $sousTotal = max(0.0, ($prixUnitaire * $quantite) - $remise);
        $montantTotalCalcule += $sousTotal;

        $lignesPreparees[] = [
            'produit' => $produit,
            'produit_id' => $produitId,
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'remise' => $remise,
            'sous_total' => $sousTotal
        ];
    }

    // 3. Calculs financiers & analyse du crédit
    $isVenteDette = ($modePaiementId === 5);
    $montantPaye = max(0.0, $montantPaye);
    if (!$isVenteDette && $montantPaye <= 0.0) {
        $montantPaye = $montantTotalCalcule;
    }

    $montantRestant = max(0.0, $montantTotalCalcule - $montantPaye);
    $estACredit = ($montantRestant > 0 || $isVenteDette);

    // 4. Contrôle de solvabilité client
    $client = null;
    if ($estACredit) {
        if ($clientId === null || $clientId <= 0) {
            throw new InvalidArgumentException("Un client nominatif est obligatoire pour toute vente à crédit.");
        }

        $client = $this->clientRepository->findById($clientId);
        if (!$client) {
            throw new InvalidArgumentException("Le compte client sélectionné est introuvable.");
        }

        if (!$client->peutPrendreCredit($montantRestant)) {
            throw new RuntimeException(
                sprintf("Plafond de crédit dépassé pour le client '%s' : Limite = %.2f FCFA, Encours = %.2f FCFA, Disponible = %.2f FCFA.",
                    $client->getNomComplet(), $client->getLimiteCredit(), $client->getTotalDettesActuelles(), $client->getCreditDisponible())
            );
        }
    } elseif ($clientId !== null && $clientId > 0) {
        $client = $this->clientRepository->findById($clientId);
    }

    // 5. Exécution transactionnelle atomique sous PDO
    $pdo = Database::getPDO();
    $pdo->beginTransaction();

    try {
        $numeroFacture = $this->genererNumeroFacture();
        $dateVenteStr = (new DateTime())->format('Y-m-d H:i:s');

        // A. Insertion de l'en-tête de vente
        $stmtVente = $pdo->prepare(
            "INSERT INTO ventes (numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id)
             VALUES (:numero_facture, :date_vente, :montant_total, :montant_paye, :montant_restant, :mode_paiement_id, :statut, :client_id, :user_id)"
        );
        $stmtVente->bindValue(':numero_facture', $numeroFacture, PDO::PARAM_STR);
        $stmtVente->bindValue(':date_vente', $dateVenteStr, PDO::PARAM_STR);
        $stmtVente->bindValue(':montant_total', $montantTotalCalcule);
        $stmtVente->bindValue(':montant_paye', $montantPaye);
        $stmtVente->bindValue(':montant_restant', $montantRestant);
        $stmtVente->bindValue(':mode_paiement_id', $modePaiementId, PDO::PARAM_INT);
        $stmtVente->bindValue(':statut', 'VALIDEE', PDO::PARAM_STR);
        $stmtVente->bindValue(':client_id', $clientId ?: null, $clientId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmtVente->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmtVente->execute();

        $venteId = (int)$pdo->lastInsertId();

        // B. Insertion des lignes et décrémentation atomique de stock
        $stmtLigne = $pdo->prepare(
            "INSERT INTO lignes_vente (vente_id, produit_id, quantite, prix_unitaire, remise, sous_total)
             VALUES (:vente_id, :produit_id, :quantite, :prix_unitaire, :remise, :sous_total)"
        );

        $stmtDecStock = $pdo->prepare(
            "UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte"
        );

        foreach ($lignesPreparees as $ligne) {
            $stmtLigne->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
            $stmtLigne->bindValue(':produit_id', $ligne['produit_id'], PDO::PARAM_INT);
            $stmtLigne->bindValue(':quantite', $ligne['quantite'], PDO::PARAM_INT);
            $stmtLigne->bindValue(':prix_unitaire', $ligne['prix_unitaire']);
            $stmtLigne->bindValue(':remise', $ligne['remise']);
            $stmtLigne->bindValue(':sous_total', $ligne['sous_total']);
            $stmtLigne->execute();

            $stmtDecStock->bindValue(':qte', $ligne['quantite'], PDO::PARAM_INT);
            $stmtDecStock->bindValue(':id', $ligne['produit_id'], PDO::PARAM_INT);
            $stmtDecStock->execute();

            if ($stmtDecStock->rowCount() === 0) {
                throw new RuntimeException(
                    "Rupture de stock concurrente survenue lors de la validation de l'article '{$ligne['produit']->getLibelle()}'."
                );
            }
        }

        // C. Traitement de la dette et mise à jour de l'encours client
        if ($estACredit && $montantRestant > 0 && $clientId !== null) {
            $echeance = $dateEcheance ?? (new DateTime())->modify('+30 days');

            $stmtDette = $pdo->prepare(
                "INSERT INTO dettes (vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id)
                 VALUES (:vente_id, :client_id, :montant_total, :montant_restant, :date_creation, :date_echeance, :statut_id)"
            );
            $stmtDette->bindValue(':vente_id', $venteId, PDO::PARAM_INT);
            $stmtDette->bindValue(':client_id', $clientId, PDO::PARAM_INT);
            $stmtDette->bindValue(':montant_total', $montantRestant);
            $stmtDette->bindValue(':montant_restant', $montantRestant);
            $stmtDette->bindValue(':date_creation', $dateVenteStr, PDO::PARAM_STR);
            $stmtDette->bindValue(':date_echeance', $echeance->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmtDette->bindValue(':statut_id', 1, PDO::PARAM_INT);
            $stmtDette->execute();

            $detteId = (int)$pdo->lastInsertId();

            $stmtClient = $pdo->prepare(
                "UPDATE clients SET total_dettes_actuelles = total_dettes_actuelles + :montant WHERE id = :id"
            );
            $stmtClient->bindValue(':montant', $montantRestant);
            $stmtClient->bindValue(':id', $clientId, PDO::PARAM_INT);
            $stmtClient->execute();

            // D. Historisation de l'acompte initial si présent
            if ($montantPaye > 0) {
                $stmtPaiement = $pdo->prepare(
                    "INSERT INTO paiements (dette_id, montant, date_paiement, mode_paiement_id, reference_paiement, user_id)
                     VALUES (:dette_id, :montant, :date_paiement, :mode_paiement_id, :ref, :user_id)"
                );
                $stmtPaiement->bindValue(':dette_id', $detteId, PDO::PARAM_INT);
                $stmtPaiement->bindValue(':montant', $montantPaye);
                $stmtPaiement->bindValue(':date_paiement', $dateVenteStr, PDO::PARAM_STR);
                $stmtPaiement->bindValue(':mode_paiement_id', $modePaiementId, PDO::PARAM_INT);
                $stmtPaiement->bindValue(':ref', 'ACOMPTE-' . $numeroFacture, PDO::PARAM_STR);
                $stmtPaiement->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmtPaiement->execute();
            }
        }

        // 6. Validation définitive
        $pdo->commit();

        return $this->getVente($venteId);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
```

#### 🔬 Explication Ligne par Ligne :

- **Lignes 116-118 : `if (empty($articles)) throw new InvalidArgumentException(...)`**  
  Garde-fou d'entrée rejetant immédiatement la transaction si le panier soumis est vide.
- **Lignes 123-164 : Boucle `foreach ($articles as $item)`**  
  Parcours de chaque ligne d'article pour validation :
  - Extraction de l'ID produit, quantité et remise.
  - Recherche du produit en base via `$this->produitRepository->findById($produitId)`.
  - Vérification préalable du stock : `if ($produit->getQteStock() < $quantite) throw new RuntimeException(...)`.
  - Calcul du sous-total ligne : `max(0.0, ($prixUnitaire * $quantite) - $remise)` et cumul dans `$montantTotalCalcule`.
  - Stockage dans le tableau intermédiaire `$lignesPreparees`.
- **Lignes 166-174 : Calculs des montants et détection du crédit**  
  - Identifie si le mode de paiement est `DETTE` (ID 5).
  - Si vente au comptant sans montant saisi, le montant payé est automatiquement égal au total net.
  - `$montantRestant = max(0.0, $montantTotalCalcule - $montantPaye)`.
  - `$estACredit = ($montantRestant > 0 || $isVenteDette)`.
- **Lignes 176-199 : Contrôle de Solvabilité Client & Plafond de Risque**  
  Si la vente génère un reste à payer :
  - Vérifie la présence d'un client nominatif : `if ($clientId === null) throw new InvalidArgumentException(...)`.
  - Récupère l'entité `Client`.
  - **Appel de la méthode métier de l'entité POO** : `if (!$client->peutPrendreCredit($montantRestant))`. Si la somme `totalDettesActuelles + montantRestant > limiteCredit`, une exception bloquante est levée avec le détail des soldes.
- **Lignes 204-205 : `$pdo = Database::getPDO(); $pdo->beginTransaction();`**  
  Ouverture de la transaction SQL atomique. Dès cette instruction, toutes les modifications SQL suivantes sont isolées en mémoire tampon jusqu'au `commit()`.
- **Lignes 208-225 : Insertion de l'en-tête de vente**  
  Génère le numéro unique de facture (`FACT-YYYYMMDD-XXXXXX`) et exécute la requête préparée `INSERT INTO ventes (...)`. Récupère l'identifiant généré via `$pdo->lastInsertId()`.
- **Lignes 237-255 : Insertion des lignes et décrémentation atomique**  
  Pour chaque article :
  - Insère le détail dans `lignes_vente`.
  - Exécute `UPDATE produits SET qte_stock = qte_stock - :qte WHERE id = :id AND qte_stock >= :qte`.
  - **Garde-fou anti-concurrence** : `if ($stmtDecStock->rowCount() === 0)` $\rightarrow$ si aucune ligne n'a été modifiée (parce qu'une autre caisse a acheté le stock entre-temps), une exception est levée pour annuler l'ensemble de la transaction.
- **Lignes 257-295 : Traitement de la Créance Client**  
  Si vente à crédit :
  - Calcule l'échéance à +30 jours par défaut.
  - Insère la créance dans `dettes` avec le statut initial `NON_SOLDEE` (`statut_id = 1`).
  - Incrémente l'encours du client dans `clients` : `UPDATE clients SET total_dettes_actuelles = total_dettes_actuelles + :montant WHERE id = :id`.
  - Si le client a versé un acompte initial partiel, insère une ligne de reçu dans `paiements` (`ACOMPTE-FACT-...`).
- **Ligne 297 : `$pdo->commit();`**  
  Validation définitive et écriture sur disque de toutes les opérations dans les tables `ventes`, `lignes_vente`, `produits`, `dettes`, `clients`, `paiements`.
- **Lignes 301-306 : `catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }`**  
  Filet de sécurité absolu : capture toute erreur ou exception, annule intégralement la transaction via `rollBack()` (aucun stock débité, aucune vente fantôme, aucun encours altéré) et propage l'exception vers le contrôleur pour affichage à l'utilisateur.

---

### 🔍 Méthode 3 : `DebtService::enregistrerRemboursement()` (Recouvrement & Cycle de Vie des Dettes)

- **Fichier** : `src/Service/DebtService.php` (Lignes 32 à 114)
- **Rôle Métier** : Traiter l'encaissement d'un remboursement (partiel ou total) sur une créance client. Elle met à jour le solde restant de la dette, recalcule son statut (commutation automatique en `SOLDEE` dès que le reste dû atteint 0 FCFA), historise le reçu de versement dans la table des paiements, et décrémente instantanément l'encours global du client pour restaurer son plafond de crédit disponible.
- **Rôle Technique** :
  - Contrôles de validation des entrées (montant strictement positif, rejet si dette déjà soldée, rejet si montant supérieur au reste dû).
  - Encadrement sous **Transaction PDO atomique** (`beginTransaction()`, `commit()`, `rollBack()`).
  - Persistance de l'entité `Paiement` avec horodatage, canal d'encaissement et agent encaisseur.
  - Calcul arithmétique précis avec `round(..., 2)` pour éviter les erreurs d'arrondis en virgule flottante.
  - Mise à jour synchronisée des tables `paiements`, `dettes` et `clients`.
  - Renvoi d'un tableau de rapport d'exécution structuré pour le contrôleur et la vue.

#### 📜 Extrait de Code Réel :

```php
// Extrait de src/Service/DebtService.php

public function enregistrerRemboursement(
    int $detteId,
    float $montant,
    int $modePaiementId,
    int $userId = 1,
    ?string $reference = null
): array {
    // 1. Contrôles préalables stricts
    if ($montant <= 0) {
        throw new InvalidArgumentException("Le montant du remboursement doit être strictement supérieur à zéro.");
    }

    $dette = $this->detteRepository->findById($detteId);
    if (!$dette) {
        throw new InvalidArgumentException("La dette #DT-{$detteId} est introuvable.");
    }

    if ($dette->estSoldee() || $dette->getMontantRestant() <= 0) {
        throw new RuntimeException("Cette dette est déjà intégralement soldée.");
    }

    if ($montant > $dette->getMontantRestant()) {
        throw new InvalidArgumentException(
            sprintf(
                "Le montant du versement (%s FCFA) ne peut pas excéder le reste dû (%s FCFA).",
                number_format($montant, 0, ',', ' '),
                number_format($dette->getMontantRestant(), 0, ',', ' ')
            )
        );
    }

    // 2. Démarrage de la transaction PDO
    $this->pdo->beginTransaction();

    try {
        // A. Enregistrement du reçu de paiement
        $paiement = new Paiement(
            detteId: $detteId,
            montant: $montant,
            datePaiement: new DateTime(),
            modePaiementId: $modePaiementId,
            referencePaiement: $reference,
            userId: $userId
        );

        $this->detteRepository->savePaiement($paiement);

        // B. Recalcul arithmétique du solde et transition d'état
        $nouveauReste = max(0.0, round($dette->getMontantRestant() - $montant, 2));

        if ($nouveauReste <= 0.0) {
            $statutId = 2; // SOLDEE
        } elseif ($dette->estEnRetard()) {
            $statutId = 3; // EN_RETARD
        } else {
            $statutId = 1; // NON_SOLDEE
        }

        $this->detteRepository->updateMontantRestantEtStatut($detteId, $nouveauReste, $statutId);

        // C. Décrémentation atomique de l'encours global du client
        $this->clientRepository->diminuerDette($dette->getClientId(), $montant);

        // D. Validation transactionnelle
        $this->pdo->commit();

        $estSoldee = ($nouveauReste <= 0.0);
        $message = $estSoldee
            ? "Dette #DT-{$detteId} intégralement soldée avec succès !"
            : "Versement de " . number_format($montant, 0, ',', ' ') . " FCFA enregistré. Reste dû : " . number_format($nouveauReste, 0, ',', ' ') . " FCFA.";

        return [
            'success' => true,
            'dette_id' => $detteId,
            'paiement_id' => $paiement->getId(),
            'montant_verse' => $montant,
            'nouveau_reste' => $nouveauReste,
            'est_soldee' => $estSoldee,
            'statut_id' => $statutId,
            'client_id' => $dette->getClientId(),
            'message' => $message
        ];
    } catch (Throwable $e) {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        throw $e;
    }
}
```

#### 🔬 Explication Ligne par Ligne :

- **Lignes 39-41 : `if ($montant <= 0) throw new InvalidArgumentException(...)`**  
  Rejette immédiatement les montants nuls ou négatifs, empêchant les opérations financières frauduleuses ou erronées.
- **Lignes 43-46 : `$dette = $this->detteRepository->findById($detteId);`**  
  Recherche la créance en base avec ses jointures (client, statut, vente d'origine). Lève une exception si l'identifiant n'existe pas.
- **Lignes 48-50 : `if ($dette->estSoldee() || $dette->getMontantRestant() <= 0)`**  
  Garde-fou métier interdisant tout versement superflu sur une dette déjà éteinte.
- **Lignes 52-60 : `if ($montant > $dette->getMontantRestant())`**  
  Vérifie que l'acompte ne dépasse pas le solde exact restant dû. Formate un message d'erreur clair avec séparateurs de milliers.
- **Ligne 62 : `$this->pdo->beginTransaction();`**  
  Ouvre la transaction PDO pour garantir que l'historisation du versement, la mise à jour de la dette et la diminution du risque client s'exécutent de façon 100% indivisible.
- **Lignes 65-74 : Création et persistance du `Paiement`**  
  Instancie l'entité POO `Paiement` avec le constructeur typé PHP 8 (arguments nommés), horodatée à la date/heure actuelle (`new DateTime()`), et persiste en base via `$this->detteRepository->savePaiement($paiement)`.
- **Ligne 76 : `$nouveauReste = max(0.0, round($dette->getMontantRestant() - $montant, 2));`**  
  Calcule la différence et applique `round(..., 2)` pour se prémunir des imprécisions IEEE 754 de calcul flottant. `max(0.0, ...)` garantit qu'un solde ne devienne jamais négatif.
- **Lignes 78-84 : Machine à états (Transition de statut)**  
  - Si `nouveauReste <= 0.0` : le statut bascule automatiquement vers `SOLDEE` (ID 2).
  - Sinon, si la date d'échéance est dépassée (`$dette->estEnRetard()`), le statut passe à `EN_RETARD` (ID 3).
  - Sinon, le statut reste `NON_SOLDEE` (ID 1).
- **Ligne 86 : `$this->detteRepository->updateMontantRestantEtStatut(...)`**  
  Met à jour la ligne SQL dans la table `dettes` via requête préparée.
- **Ligne 88 : `$this->clientRepository->diminuerDette($dette->getClientId(), $montant);`**  
  **Instruction clé d'intégrité financière** : décrémente `total_dettes_actuelles` dans la table `clients`. Cela augmente immédiatement le crédit disponible calculé (`limite_credit - total_dettes_actuelles`) pour les futurs achats du client.
- **Ligne 90 : `$this->pdo->commit();`**  
  Valide définitivement la transaction SQL.
- **Lignes 92-107 : Construction du rapport de résultat**  
  Formate un tableau associatif complet avec les métriques mises à jour, utilisé par le contrôleur (`DetteController`) pour générer les notifications Flash et actualiser l'affichage.
- **Lignes 108-113 : Gestion des erreurs & Rollback**  
  Capture toute exception `Throwable`, annule la transaction si active et propage l'erreur sans laisser de données partiellement écrites.

