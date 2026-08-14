
DROP TABLE IF EXISTS lignes_approvisionnement CASCADE;
DROP TABLE IF EXISTS approvisionnements CASCADE;
DROP TABLE IF EXISTS paiements CASCADE;
DROP TABLE IF EXISTS dettes CASCADE;
DROP TABLE IF EXISTS lignes_vente CASCADE;
DROP TABLE IF EXISTS ventes CASCADE;
DROP TABLE IF EXISTS produits CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS clients CASCADE;
DROP TABLE IF EXISTS fournisseurs CASCADE;
DROP TABLE IF EXISTS utilisateurs CASCADE;
DROP TABLE IF EXISTS modes_paiement CASCADE;
DROP TABLE IF EXISTS statuts_dette CASCADE;
DROP TABLE IF EXISTS statuts_appro CASCADE;
DROP TABLE IF EXISTS roles CASCADE;



CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE modes_paiement (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    est_actif BOOLEAN DEFAULT TRUE
);

CREATE TABLE statuts_dette (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE statuts_appro (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    description TEXT
);


CREATE TABLE utilisateurs (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role_id INTEGER NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150),
    adresse TEXT,
    limite_credit NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (limite_credit >= 0),
    total_dettes_actuelles NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (total_dettes_actuelles >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fournisseurs (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    contact_nom VARCHAR(100),
    telephone VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150),
    adresse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE produits (
    id SERIAL PRIMARY KEY,
    code VARCHAR(100) UNIQUE NOT NULL,
    libelle VARCHAR(200) NOT NULL,
    description TEXT,
    prix_achat NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (prix_achat >= 0),
    prix_vente NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (prix_vente >= 0),
    qte_stock INTEGER NOT NULL DEFAULT 0 CHECK (qte_stock >= 0),
    seuil_alerte INTEGER NOT NULL DEFAULT 5 CHECK (seuil_alerte >= 0),
    categorie_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_prix_coherence CHECK (prix_vente >= prix_achat)
);



CREATE TABLE ventes (
    id SERIAL PRIMARY KEY,
    numero_facture VARCHAR(100) UNIQUE NOT NULL,
    date_vente TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    montant_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (montant_total >= 0),
    montant_paye NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (montant_paye >= 0),
    montant_restant NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (montant_restant >= 0),
    mode_paiement_id INTEGER NOT NULL REFERENCES modes_paiement(id) ON DELETE RESTRICT,
    statut VARCHAR(50) NOT NULL DEFAULT 'VALIDEE',
    client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    user_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lignes_vente (
    id SERIAL PRIMARY KEY,
    vente_id INTEGER NOT NULL REFERENCES ventes(id) ON DELETE CASCADE,
    produit_id INTEGER NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire NUMERIC(12, 2) NOT NULL CHECK (prix_unitaire >= 0),
    remise NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (remise >= 0),
    sous_total NUMERIC(12, 2) NOT NULL CHECK (sous_total >= 0)
);


CREATE TABLE dettes (
    id SERIAL PRIMARY KEY,
    vente_id INTEGER REFERENCES ventes(id) ON DELETE SET NULL,
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    montant_total NUMERIC(12, 2) NOT NULL CHECK (montant_total >= 0),
    montant_restant NUMERIC(12, 2) NOT NULL CHECK (montant_restant >= 0),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_echeance TIMESTAMP,
    statut_id INTEGER NOT NULL REFERENCES statuts_dette(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT check_montant_restant_coherent CHECK (montant_restant <= montant_total)
);

CREATE TABLE paiements (
    id SERIAL PRIMARY KEY,
    dette_id INTEGER NOT NULL REFERENCES dettes(id) ON DELETE CASCADE,
    montant NUMERIC(12, 2) NOT NULL CHECK (montant > 0),
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mode_paiement_id INTEGER NOT NULL REFERENCES modes_paiement(id) ON DELETE RESTRICT,
    reference_paiement VARCHAR(100),
    user_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



CREATE TABLE approvisionnements (
    id SERIAL PRIMARY KEY,
    numero_bl VARCHAR(100) UNIQUE NOT NULL,
    date_appro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    montant_total NUMERIC(12, 2) NOT NULL DEFAULT 0.00 CHECK (montant_total >= 0),
    statut_id INTEGER NOT NULL REFERENCES statuts_appro(id) ON DELETE RESTRICT,
    fournisseur_id INTEGER NOT NULL REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    user_id INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lignes_approvisionnement (
    id SERIAL PRIMARY KEY,
    approvisionnement_id INTEGER NOT NULL REFERENCES approvisionnements(id) ON DELETE CASCADE,
    produit_id INTEGER NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite INTEGER NOT NULL CHECK (quantite > 0),
    prix_achat_unitaire NUMERIC(12, 2) NOT NULL CHECK (prix_achat_unitaire >= 0),
    sous_total NUMERIC(12, 2) NOT NULL CHECK (sous_total >= 0)
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



INSERT INTO roles (code, libelle, description) VALUES
('ADMIN', 'Admin Boutique', 'Contrôle total sur la comptabilité, ventes, dettes, approvisionnements et utilisateurs'),
('VENTE', 'Chargé de Vente', 'Accès caisse tactile POS et registre des dettes clients'),
('STOCK', 'Chargé de Stock', 'Gestion des approvisionnements, réception BL et catalogue'),
('INVENTAIRE', 'Inventaire', 'Mode consultation et comptage des stocks et répertoires');

INSERT INTO modes_paiement (code, libelle, est_actif) VALUES
('ESPECES', 'Espèces (Cash)', TRUE),
('WAVE', 'Wave Mobile Money', TRUE),
('ORANGE_MONEY', 'Orange Money (OM)', TRUE),
('CARTE_BANCAIRE', 'Carte Bancaire / TPE', TRUE),
('DETTE', 'Dette / À crédit', TRUE);

INSERT INTO statuts_dette (code, libelle) VALUES
('NON_SOLDEE', 'Non soldée / En cours'),
('SOLDEE', 'Soldée / Intégralement payée'),
('EN_RETARD', 'En retard de paiement');

INSERT INTO statuts_appro (code, libelle) VALUES
('EN_ATTENTE', 'En attente de réception'),
('RECU', 'Réceptionné / Stock incrémenté'),
('ANNULE', 'Annulé');

INSERT INTO categories (code, libelle, description) VALUES
('CAT-ALIM', 'Alimentation Générale', 'Produits de consommation courante, épicerie'),
('CAT-BOIS', 'Boissons & Rafraîchissements', 'Sodas, jus, eaux minérales et boissons'),
('CAT-HYG', 'Hygiène & Entretien', 'Produits ménagers, savons et soins corporels'),
('CAT-ELEC', 'Petit Électronique & Accessoires', 'Câbles, lampes, accessoires utiles');

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, actif) VALUES
('Diallo', 'Mamadou', 'admin@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 1, TRUE),
('Sow', 'Awa', 'vente@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 2, TRUE),
('Ndiaye', 'Ibrahima', 'stock@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 3, TRUE),
('Ba', 'Fatou', 'inventaire@storemanager.pro', '$2y$10$qRMgTBjU5vVq0v5eX3eM4eqK0iG3sN9I1yLqQYV4yH3wYwJ1s9G2W', 4, TRUE);

INSERT INTO fournisseurs (nom, contact_nom, telephone, email, adresse) VALUES
('Grossiste Central Dakar', 'Ousmane Fall', '+221 77 123 45 67', 'contact@grossistedakar.sn', 'Zone Industrielle Petersen, Dakar'),
('SODIDA Boissons Distribution', 'Mariama Kane', '+221 78 234 56 78', 'commandes@sodida.sn', 'Rue 10 Castors, Dakar'),
('Import-Export Cosmétiques', 'Cheikh Diop', '+221 76 345 67 89', 'cheikh@impexport.sn', 'Avenue Lamine Guèye, Dakar');

INSERT INTO clients (nom, prenom, telephone, email, adresse, limite_credit, total_dettes_actuelles) VALUES
('Client', 'Comptoir (Passager)', '+221 00 000 00 00', 'comptoir@storemanager.pro', 'Vente directe magasin', 0.00, 0.00),
('Diop', 'Amadou', '+221 77 555 11 22', 'amadou.diop@gmail.com', 'Mermoz Pyrotechnie, Dakar', 150000.00, 45000.00),
('Seck', 'Khady', '+221 78 666 22 33', 'khady.seck@yahoo.fr', 'Sacré-Cœur 3, Dakar', 200000.00, 0.00),
('Faye', 'Babacar', '+221 76 777 33 44', 'babacar.faye@orange.sn', 'Yoff Tonghor, Dakar', 100000.00, 85000.00);

INSERT INTO produits (code, libelle, description, prix_achat, prix_vente, qte_stock, seuil_alerte, categorie_id) VALUES
('PRD-RIZ-25K', 'Sac de Riz Parfumé 25kg', 'Riz brisé 100% parfumé de qualité supérieure', 14500.00, 17500.00, 45, 10, 1),
('PRD-HUILE-5L', 'Bidon d''Huile Végétale 5L', 'Huile raffinée sans cholestérol', 5200.00, 6500.00, 30, 8, 1),
('PRD-SUCRE-PQT', 'Paquet de Sucre en Morceaux 1kg', 'Sucre blanc raffiné pur canne', 800.00, 1000.00, 85, 20, 1),
('PRD-EAU-10L', 'Pack Eau Minérale 10L', 'Eau de source naturelle purifiée', 1500.00, 2000.00, 60, 15, 2),
('PRD-SODA-CAN', 'Canette Soda Cola 33cl', 'Boisson gazeuse rafraîchissante', 350.00, 500.00, 120, 25, 2),
('PRD-SAVON-LOT', 'Lot de 4 Savons de Toilette', 'Savon doux antibactérien enrichi à l''aloe vera', 1200.00, 1600.00, 40, 10, 3),
('PRD-JAVEL-1L', 'Bouteille Eau de Javel 1L', 'Désinfectant multi-surfaces concentré', 600.00, 850.00, 4, 10, 3),
('PRD-LAMPE-LED', 'Ampoule LED Économique 15W', 'Éclairage blanc froid longue durée', 1100.00, 1750.00, 2, 8, 4);

INSERT INTO ventes (numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id) VALUES
('FAC-2026-0001', CURRENT_TIMESTAMP - INTERVAL '3 days', 45000.00, 0.00, 45000.00, 5, 'VALIDEE', 2, 2);

INSERT INTO lignes_vente (vente_id, produit_id, quantite, prix_unitaire, remise, sous_total) VALUES
(1, 1, 2, 17500.00, 0.00, 35000.00),
(1, 4, 5, 2000.00, 0.00, 10000.00);

INSERT INTO dettes (vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id) VALUES
(1, 2, 45000.00, 45000.00, CURRENT_TIMESTAMP - INTERVAL '3 days', CURRENT_TIMESTAMP + INTERVAL '12 days', 1);

INSERT INTO ventes (numero_facture, date_vente, montant_total, montant_paye, montant_restant, mode_paiement_id, statut, client_id, user_id) VALUES
('FAC-2026-0002', CURRENT_TIMESTAMP - INTERVAL '7 days', 115000.00, 30000.00, 85000.00, 5, 'VALIDEE', 4, 2);

INSERT INTO lignes_vente (vente_id, produit_id, quantite, prix_unitaire, remise, sous_total) VALUES
(2, 1, 5, 17500.00, 0.00, 87500.00),
(2, 2, 4, 6500.00, 0.00, 26000.00),
(2, 3, 1, 1000.00, 0.00, 1000.00);

INSERT INTO dettes (vente_id, client_id, montant_total, montant_restant, date_creation, date_echeance, statut_id) VALUES
(2, 4, 85000.00, 85000.00, CURRENT_TIMESTAMP - INTERVAL '7 days', CURRENT_TIMESTAMP + INTERVAL '7 days', 1);

INSERT INTO approvisionnements (numero_bl, date_appro, montant_total, statut_id, fournisseur_id, user_id) VALUES
('BL-2026-0001', CURRENT_TIMESTAMP - INTERVAL '2 days', 362500.00, 2, 1, 3);

INSERT INTO lignes_approvisionnement (approvisionnement_id, produit_id, quantite, prix_achat_unitaire, sous_total) VALUES
(1, 1, 25, 14500.00, 362500.00);
