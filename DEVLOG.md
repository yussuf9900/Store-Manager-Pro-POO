# 📓 Journal de Développement (DEVLOG)
**Nom & Prénom** : Youssou SALL  
**Projet** : StoreManager Pro (ERP PHP/POO)  

---

## 1. Suivi Chronologique des Phases

### 🌃 [Vendredi - Phase 1] : Conception & BDD Fallback
- **Heure de réalisation** : 19h00 - 22h00 (Step 1.1 Conception UML & Step 1.2 Schéma BDD)
- **Ce qui a été fait** : 
  - **Conception UML (Step 1.1)** :
    - **Analyse du domaine & matrice des rôles** : Définition précise des 4 profils utilisateurs (*Admin Boutique*, *Chargé de Vente*, *Chargé de Stock*, *Inventaire*) et cartographie de leurs prérogatives sur les modules POS, Dettes, Approvisionnements et Catalogue.
    - **Diagramme de Cas d'Utilisation (Use Cases)** : Modélisation sous format PlantUML natif (`docs/use_cases.puml`) des interactions acteurs/système, structuration par packages métier, identification des relations `<<include>>` (gestion panier, décrémentation stock, mise à jour solde dette) et `<<extend>>` (contrôle du plafond de crédit pour la vente à crédit).
    - **Diagramme de Classes 100% POO Pure** : Modélisation complète sous format PlantUML natif (`docs/diagramme_classes.puml`) en classes pures (sans `enum`, avec modélisation des classes référentielles `Role`, `StatutDette`, `ModePaiement`, `StatutAppro`, `Categorie`), de toutes les entités métier (`User`, `Produit`, `Client`, `Fournisseur`, `Vente`, `LigneVente`, `Dette`, `Paiement`, `Approvisionnement`, `LigneApprovisionnement`), de leurs attributs typés PHP 8, de leurs méthodes métier encapsulées (`peutPrendreCredit()`, `retirerStock()`, `ajouterStock()`, `calculerMarge()`, `estSoldee()`), et des relations de composition fortes.
    - **Dossier d'architecture** : Rédaction de `docs/README.md` exposant l'architecture en couches (*Clean Layered / MVC*) et la matrice des permissions RBAC.
  - **Schéma SQL PostgreSQL & SQLite (Step 1.2)** :
    - **Script PostgreSQL (`database/schema.sql` et `schema.sql`)** : Modélisation normalisée en 3FN de 15 tables relationnelles (`roles`, `utilisateurs`, `categories`, `produits`, `clients`, `fournisseurs`, `modes_paiement`, `statuts_dette`, `statuts_appro`, `ventes`, `lignes_vente`, `dettes`, `paiements`, `approvisionnements`, `lignes_approvisionnement`).
    - **Script SQLite (`database/schema_sqlite.sql` et `schema_sqlite.sql`)** : Adaptation rigoureuse pour SQLite (`INTEGER PRIMARY KEY AUTOINCREMENT`, activation `PRAGMA foreign_keys = ON;`, types `REAL` et dates ISO standard).
    - **Contraintes d'intégrité & Règles métier BDD** : Mise en place de contraintes `CHECK` strictes (`qte_stock >= 0`, `prix_vente >= prix_achat`, `limite_credit >= 0`, `montant_restant <= montant_total`, `quantite > 0`, `remise >= 0`).
    - **Indexation** : Création d'index de performance sur les clés étrangères et colonnes de recherche fréquentes (`code`, `role_id`, `client_id`, `date_vente`, etc.).
    - **Jeu de données initial (Seeding)** : Insertion des rôles, statuts, modes de règlement, 4 utilisateurs par défaut avec mot de passe hashé (`password_hash('password123', PASSWORD_BCRYPT)`), catalogue de produits, fournisseurs, clients avec plafonds de crédit et créances initiales pour les tests de caisse et de recouvrement.
- **Difficultés / Obstacles** : 
  - *Choix d'une modélisation 100% Classes POO* : Remplacement des énumérations par des classes d'entités/référentiels pour assurer une parfaite cohérence avec le schéma relationnel SQL (tables de lookup avec clés étrangères).
  - *Différences de dialectes entre PostgreSQL et SQLite* : Gestion des types auto-incrémentés (`SERIAL` vs `INTEGER PRIMARY KEY AUTOINCREMENT`), des types booléens (`BOOLEAN` vs `INTEGER CHECK (actif IN (0, 1))`) et des fonctions temporelles (`CURRENT_TIMESTAMP - INTERVAL '3 days'` vs `DATETIME('now', '-3 days')`).
  - *Garantie de l'intégrité référentielle en cascade* : Configuration des `ON DELETE CASCADE` pour les compositions fortes (`lignes_vente`, `lignes_approvisionnement`, `paiements`) et `ON DELETE RESTRICT` pour protéger les entités maîtresses (`produits`, `clients`, `fournisseurs`, `roles`).
