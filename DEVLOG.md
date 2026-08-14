# 📓 Journal de Développement (DEVLOG)
**Nom & Prénom** : Youssou SALL  
**Projet** : StoreManager Pro (ERP PHP/POO)  

---

## 1. Suivi Chronologique des Phases

### 🌃 [Vendredi - Phase 1] : Conception & BDD Fallback
- **Heure de réalisation** : 19h00 - 20h30 (Step 1.1 Conception UML)
- **Ce qui a été fait** : 
  - **Analyse du domaine & matrice des rôles** : Définition précise des 4 profils utilisateurs (*Admin Boutique*, *Chargé de Vente*, *Chargé de Stock*, *Inventaire*) et cartographie de leurs prérogatives sur les modules POS, Dettes, Approvisionnements et Catalogue.
  - **Diagramme de Cas d'Utilisation (Use Cases)** : Modélisation sous format PlantUML natif (`docs/use_cases.puml`) des interactions acteurs/système, structuration par packages métier, identification des relations `<<include>>` (gestion panier, décrémentation stock, mise à jour solde dette) et `<<extend>>` (contrôle du plafond de crédit pour la vente à crédit).
  - **Diagramme de Classes** : Modélisation complète sous format PlantUML natif (`docs/diagramme_classes.puml`) en classes pures (sans `enum`, avec modélisation des classes référentielles `Role`, `StatutDette`, `ModePaiement`, `StatutAppro`, `Categorie`), de toutes les entités métier (`User`, `Produit`, `Client`, `Fournisseur`, `Vente`, `LigneVente`, `Dette`, `Paiement`, `Approvisionnement`, `LigneApprovisionnement`), de leurs attributs typés PHP 8, de leurs méthodes métier encapsulées (`peutPrendreCredit()`, `retirerStock()`, `ajouterStock()`, `calculerMarge()`, `estSoldee()`), et des relations de composition fortes.
  - **Dossier d'architecture** : Rédaction de `docs/README.md` exposant l'architecture en couches (*Clean Layered / MVC*) et la matrice des permissions RBAC.
- **Difficultés / Obstacles** : 
  - *Choix d'une modélisation 100% Classes POO* : Remplacement des énumérations par des classes d'entités/référentiels pour assurer une parfaite cohérence avec le futur schéma relationnel SQL.
