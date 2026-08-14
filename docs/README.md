# 📚 Dossier de Conception UML & Architecture — StoreManager Pro

Bienvenue dans le dossier de conception technique et modélisation orientée objet de **StoreManager Pro**, application ERP/Point de Vente (POS) développée en **PHP 8+ POO From Scratch** sans framework.

---

## 🎯 Périmètre & Objectifs du Projet

L'application **StoreManager Pro** centralise et fiabilise les opérations quotidiennes d'un commerce de détail à travers plusieurs piliers fonctionnels :

1. **Caisse Tactile & Point de Vente (POS)** : Vente rapide au comptoir ou sur compte client, recherche code-barres, gestion des remises, encaissements multi-modes (Espèces, Wave, Orange Money, Carte Bancaire, Vente à crédit).
2. **Gestion des Créances & Dettes Clients** : Contrôle strict du plafond de crédit (`limite_credit`), suivi des échéances, encaissement de remboursements partiels/totaux avec mise à jour transactionnelle.
3. **Approvisionnements & Gestion des Stocks** : Réception de marchandises sous Bon de Livraison (BL), valorisation des stocks, incrémentation/décrémentation temps réel et alertes de stock critique.
4. **Catalogue & Répertoires Tiers** : Référentiel des produits, catégories, clients et fournisseurs.
5. **Authentification & Contrôle d'Accès par Rôles (RBAC)** : Ségrégation des prérogatives selon 4 profils métiers (*Admin Boutique*, *Chargé de Vente*, *Chargé de Stock*, *Inventaire*).
6. **Architecture Robuste & Résiliente** : Architecture en couches (Model-View-Controller & Layered Services/Repositories) avec connecteur PDO Singleton et fallback automatique PostgreSQL $\rightarrow$ SQLite.

---

## 📑 Sommaire des Livrables de Conception (PlantUML)

| Document | Source PlantUML | Description |
| :--- | :---: | :--- |
| **Diagramme de Cas d'Utilisation** | [use_cases.puml](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/docs/use_cases.puml) | Modélisation des interactions des 4 profils métiers (*Admin*, *Vente*, *Stock*, *Inventaire*), packages fonctionnels, inclusions (`<<include>>`) et extensions (`<<extend>>`). |
| **Diagramme de Classes 100% POO** | [diagramme_classes.puml](file:///home/ichigo/Bureau/ODC-PROJETS/PHP/POO/storeManager/docs/diagramme_classes.puml) | Modélisation complète en classes pures des entités métier (`Produit`, `Client`, `Fournisseur`, `Vente`, `Dette`, `Paiement`, `Approvisionnement`, `User`), classes de référence (`Role`, `StatutDette`, `ModePaiement`, `StatutAppro`, `Categorie`), méthodes métier et associations. |

---

## 🏛️ Cartographie de l'Architecture Logicielle (Clean Layered)

```
┌────────────────────────────────────────────────────────┐
│                   VUES (HTML5 / CSS / JS)              │
│       views/pos/ | views/dettes/ | views/supplies/      │
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

---

## 👥 Matrice des Rôles & Permissions

| Rôle | Caisse POS & Vente | Gestion Dettes | Approvisionnements BL | Catalogue & Tiers | Dashboard & Audit |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **👑 Admin Boutique** | ✅ Lecture / Écriture | ✅ Lecture / Écriture | ✅ Lecture / Écriture | ✅ Lecture / Écriture | ✅ Accès Total |
| **🛒 Chargé de Vente** | ✅ Caisse & Ventes | ✅ Encaissement dettes | ❌ Pas d'accès | 👁️ Lecture Clients | ❌ Pas d'accès |
| **📦 Chargé de Stock** | ❌ Pas d'accès | ❌ Pas d'accès | ✅ Réception BL | ✅ Produits / Fournisseurs | ❌ Pas d'accès |
| **📋 Inventaire** | ❌ Pas d'accès | ❌ Pas d'accès | ❌ Pas d'accès | ✅ Lecture / Écriture | ❌ Pas d'accès |
