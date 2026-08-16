PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS lignes_approvisionnement;
DROP TABLE IF EXISTS approvisionnements;
DROP TABLE IF EXISTS paiements;
DROP TABLE IF EXISTS dettes;
DROP TABLE IF EXISTS lignes_vente;
DROP TABLE IF EXISTS ventes;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS fournisseurs;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS modes_paiement;
DROP TABLE IF EXISTS statuts_dette;
DROP TABLE IF EXISTS statuts_appro;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL,
    description TEXT
);

CREATE TABLE modes_paiement (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL,
    est_actif INTEGER DEFAULT 1 CHECK (est_actif IN (0, 1))
);

CREATE TABLE statuts_dette (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL
);

CREATE TABLE statuts_appro (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL,
    description TEXT
);

CREATE TABLE utilisateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    mot_de_passe TEXT NOT NULL,
    role_id INTEGER NOT NULL,
    actif INTEGER DEFAULT 1 CHECK (actif IN (0, 1)),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    telephone TEXT UNIQUE NOT NULL,
    email TEXT,
    adresse TEXT,
    limite_credit REAL NOT NULL DEFAULT 0.00 CHECK (limite_credit >= 0),
    total_dettes_actuelles REAL NOT NULL DEFAULT 0.00 CHECK (total_dettes_actuelles >= 0),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fournisseurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    contact_nom TEXT,
    telephone TEXT UNIQUE NOT NULL,
    email TEXT,
    adresse TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    libelle TEXT NOT NULL,
    description TEXT,
    prix_achat REAL NOT NULL DEFAULT 0.00 CHECK (prix_achat >= 0),
    prix_vente REAL NOT NULL DEFAULT 0.00 CHECK (prix_vente >= 0),
    qte_stock INTEGER NOT NULL DEFAULT 0 CHECK (qte_stock >= 0),
    seuil_alerte INTEGER NOT NULL DEFAULT 5 CHECK (seuil_alerte >= 0),
    categorie_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_prix_coherence CHECK (prix_vente >= prix_achat),
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE ventes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_facture TEXT UNIQUE NOT NULL,
    date_vente DATETIME DEFAULT CURRENT_TIMESTAMP,
    montant_total REAL NOT NULL DEFAULT 0.00 CHECK (montant_total >= 0),
    montant_paye REAL NOT NULL DEFAULT 0.00 CHECK (montant_paye >= 0),
    montant_restant REAL NOT NULL DEFAULT 0.00 CHECK (montant_restant >= 0),
    mode_paiement_id INTEGER NOT NULL,
    statut TEXT NOT NULL DEFAULT 'VALIDEE',
    client_id INTEGER,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mode_paiement_id) REFERENCES modes_paiement(id) ON DELETE RESTRICT,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

CREATE TABLE lignes_vente (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vente_id INTEGER NOT NULL,
    produit_id INTEGER NOT NULL,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire REAL NOT NULL CHECK (prix_unitaire >= 0),
    remise REAL NOT NULL DEFAULT 0.00 CHECK (remise >= 0),
    sous_total REAL NOT NULL CHECK (sous_total >= 0),
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT
);

CREATE TABLE dettes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vente_id INTEGER,
    client_id INTEGER NOT NULL,
    montant_total REAL NOT NULL CHECK (montant_total >= 0),
    montant_restant REAL NOT NULL CHECK (montant_restant >= 0),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_echeance DATETIME,
    statut_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_montant_restant_coherent CHECK (montant_restant <= montant_total),
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (statut_id) REFERENCES statuts_dette(id) ON DELETE RESTRICT
);

CREATE TABLE paiements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dette_id INTEGER NOT NULL,
    montant REAL NOT NULL CHECK (montant > 0),
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    mode_paiement_id INTEGER NOT NULL,
    reference_paiement TEXT,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dette_id) REFERENCES dettes(id) ON DELETE CASCADE,
    FOREIGN KEY (mode_paiement_id) REFERENCES modes_paiement(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

CREATE TABLE approvisionnements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_bl TEXT UNIQUE NOT NULL,
    date_appro DATETIME DEFAULT CURRENT_TIMESTAMP,
    montant_total REAL NOT NULL DEFAULT 0.00 CHECK (montant_total >= 0),
    statut_id INTEGER NOT NULL,
    fournisseur_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (statut_id) REFERENCES statuts_appro(id) ON DELETE RESTRICT,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT
);

CREATE TABLE lignes_approvisionnement (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    approvisionnement_id INTEGER NOT NULL,
    produit_id INTEGER NOT NULL,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_achat_unitaire REAL NOT NULL CHECK (prix_achat_unitaire >= 0),
    sous_total REAL NOT NULL CHECK (sous_total >= 0),
    FOREIGN KEY (approvisionnement_id) REFERENCES approvisionnements(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT
);

CREATE INDEX idx_utilisateurs_role ON utilisateurs(role_id);
CREATE INDEX idx_produits_code ON produits(code);
CREATE INDEX idx_produits_categorie ON produits(categorie_id);
CREATE INDEX idx_produits_stock_alerte ON produits(qte_stock, seuil_alerte);
CREATE INDEX idx_ventes_date ON ventes(date_vente);
CREATE INDEX idx_ventes_client ON ventes(client_id);
CREATE INDEX idx_ventes_user ON ventes(user_id);
CREATE INDEX idx_lignes_vente_vente ON lignes_vente(vente_id);
CREATE INDEX idx_lignes_vente_produit ON lignes_vente(produit_id);
CREATE INDEX idx_dettes_client ON dettes(client_id);
CREATE INDEX idx_dettes_statut ON dettes(statut_id);
CREATE INDEX idx_paiements_dette ON paiements(dette_id);
CREATE INDEX idx_appro_fournisseur ON approvisionnements(fournisseur_id);
CREATE INDEX idx_appro_statut ON approvisionnements(statut_id);

INSERT INTO roles (id, code, libelle, description) VALUES
(1, 'ADMIN', 'Admin Boutique', 'Contrôle total sur la comptabilité, ventes, dettes, approvisionnements et utilisateurs'),
(2, 'VENTE', 'Chargé de Vente', 'Accès caisse tactile POS et registre des dettes clients'),
(3, 'STOCK', 'Chargé de Stock', 'Gestion des approvisionnements, réception BL et catalogue'),
(4, 'INVENTAIRE', 'Inventaire', 'Mode consultation et comptage des stocks et répertoires');

INSERT INTO modes_paiement (id, code, libelle, est_actif) VALUES
(1, 'ESPECES', 'Espèces (Cash)', 1),
(2, 'WAVE', 'Wave Mobile Money', 1),
(3, 'ORANGE_MONEY', 'Orange Money (OM)', 1),
(4, 'CARTE_BANCAIRE', 'Carte Bancaire / TPE', 1),
(5, 'DETTE', 'Dette / À crédit', 1);

INSERT INTO statuts_dette (id, code, libelle) VALUES
(1, 'NON_SOLDEE', 'Non soldée / En cours'),
(2, 'SOLDEE', 'Soldée / Intégralement payée'),
(3, 'EN_RETARD', 'En retard de paiement');

INSERT INTO statuts_appro (id, code, libelle) VALUES
(1, 'EN_ATTENTE', 'En attente de réception'),
(2, 'RECU', 'Réceptionné / Stock incrémenté'),
(3, 'ANNULE', 'Annulé');

INSERT INTO categories (id, code, libelle, description) VALUES
(1, 'CAT-ALIM', 'Alimentation Générale', 'Produits de consommation courante, épicerie'),
(2, 'CAT-BOIS', 'Boissons & Rafraîchissements', 'Sodas, jus, eaux minérales et boissons'),
(3, 'CAT-HYG', 'Hygiène & Entretien', 'Produits ménagers, savons et soins corporels'),
(4, 'CAT-ELEC', 'Petit Électronique & Accessoires', 'Câbles, lampes, accessoires utiles');

INSERT INTO utilisateurs (id, nom, prenom, email, mot_de_passe, role_id, actif) VALUES
(1, 'Diallo', 'Mamadou', 'admin@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 1, 1),
(2, 'Sow', 'Awa', 'vente@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 2, 1),
(3, 'Ndiaye', 'Ibrahima', 'stock@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 3, 1),
(4, 'Ba', 'Fatou', 'inventaire@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 4, 1);

INSERT INTO fournisseurs (id, nom, contact_nom, telephone, email, adresse) VALUES
(1, 'Grossiste Central Dakar', 'Ousmane Fall', '+221 77 123 45 67', 'contact@grossistedakar.sn', 'Zone Industrielle Petersen, Dakar'),
(2, 'SODIDA Boissons Distribution', 'Mariama Kane', '+221 78 234 56 78', 'commandes@sodida.sn', 'Rue 10 Castors, Dakar'),
(3, 'Import-Export Cosmétiques', 'Cheikh Diop', '+221 76 345 67 89', 'cheikh@impexport.sn', 'Avenue Lamine Guèye, Dakar');

INSERT INTO clients (id, nom, prenom, telephone, email, adresse, limite_credit, total_dettes_actuelles) VALUES
(1, 'Client', 'Comptoir (Passager)', '+221 00 000 00 00', 'comptoir@storemanager.pro', 'Vente directe magasin', 0.00, 0.00),
(2, 'Diop', 'Amadou', '+221 77 555 11 22', 'amadou.diop@gmail.com', 'Mermoz Pyrotechnie, Dakar', 150000.00, 45000.00),
(3, 'Seck', 'Khady', '+221 78 666 22 33', 'khady.seck@yahoo.fr', 'Sacré-Cœur 3, Dakar', 200000.00, 0.00),
(4, 'Faye', 'Babacar', '+221 76 777 33 44', 'babacar.faye@orange.sn', 'Yoff Tonghor, Dakar', 100000.00, 85000.00);

INSERT INTO produits (id, code, libelle, description, prix_achat, prix_vente, qte_stock, seuil_alerte, categorie_id) VALUES
(1, 'PRD-RIZ-25K', 'Sac de Riz Parfumé 25kg', 'Riz brisé 100% parfumé de qualité supérieure', 14500.00, 17500.00, 45, 10, 1),
(2, 'PRD-HUILE-5L', 'Bidon d''Huile Végétale 5L', 'Huile raffinée sans cholestérol', 5200.00, 6500.00, 30, 8, 1),
(3, 'PRD-SUCRE-PQT', 'Paquet de Sucre en Morceaux 1kg', 'Sucre blanc raffiné pur canne', 800.00, 1000.00, 85, 20, 1),
(4, 'PRD-EAU-10L', 'Pack Eau Minérale 10L', 'Eau de source naturelle purifiée', 1500.00, 2000.00, 60, 15, 2),
(5, 'PRD-SODA-CAN', 'Canette Soda Cola 33cl', 'Boisson gazeuse rafraîchissante', 350.00, 500.00, 120, 25, 2),
(6, 'PRD-SAVON-LOT', 'Lot de 4 Savons de Toilette', 'Savon doux antibactérien enrichi à l''aloe vera', 1200.00, 1600.00, 40, 10, 3),
(7, 'PRD-JAVEL-1L', 'Bouteille Eau de Javel 1L', 'Désinfectant multi-surfaces concentré', 600.00, 850.00, 4, 10, 3),
(8, 'PRD-LAMPE-LED', 'Ampoule LED Économique 15W', 'Éclairage blanc froid longue durée', 1100.00, 1750.00, 2, 8, 4);

INSERT INTO ventes (id, numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id) VALUES
(1, 'FAC-2026-0001', DATETIME('now', '-3 days'), 45000.00, 0.00, 45000.00, 5, 'VALIDEE', 2, 2);

INSERT INTO lignes_vente (id, vente_id, produit_id, quantite, prix_unitaire, remise, sous_total) VALUES
(1, 1, 1, 2, 17500.00, 0.00, 35000.00),
(2, 1, 4, 5, 2000.00, 0.00, 10000.00);

INSERT INTO dettes (id, vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id) VALUES
(1, 1, 2, 45000.00, 45000.00, DATETIME('now', '-3 days'), DATETIME('now', '+12 days'), 1);

INSERT INTO ventes (id, numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id) VALUES
(2, 'FAC-2026-0002', DATETIME('now', '-7 days'), 115000.00, 30000.00, 85000.00, 5, 'VALIDEE', 4, 2);

INSERT INTO lignes_vente (id, vente_id, produit_id, quantite, prix_unitaire, remise, sous_total) VALUES
(3, 2, 1, 5, 17500.00, 0.00, 87500.00),
(4, 2, 2, 4, 6500.00, 0.00, 26000.00),
(5, 2, 3, 1, 1000.00, 0.00, 1000.00);

INSERT INTO dettes (id, vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id) VALUES
(2, 2, 4, 85000.00, 85000.00, DATETIME('now', '-7 days'), DATETIME('now', '+7 days'), 1);

INSERT INTO approvisionnements (id, numero_bl, date_appro, montant_total, statut_id, fournisseur_id, user_id) VALUES
(1, 'BL-DIP-099', DATETIME('now', '-2 days'), 190000.00, 2, 2, 3),
(2, 'BL-SEN-102', DATETIME('now', '-1 days'), 190000.00, 1, 3, 3),
(3, 'BL-CCS-101', DATETIME('now', '-3 hours'), 525000.00, 1, 1, 3);

INSERT INTO lignes_approvisionnement (id, approvisionnement_id, produit_id, quantite, prix_achat_unitaire, sous_total) VALUES
(1, 1, 2, 20, 5200.00, 104000.00),
(2, 1, 7, 100, 600.00, 60000.00),
(3, 1, 4, 17, 1500.00, 26000.00),
(4, 2, 3, 50, 800.00, 40000.00),
(5, 2, 2, 25, 5200.00, 130000.00),
(6, 2, 4, 13, 1500.00, 20000.00),
(7, 3, 1, 35, 14500.00, 507500.00),
(8, 3, 6, 14, 1200.00, 17500.00);
