<?php
$pageTitle = "Produits & Tiers — StoreManager Pro";
$activeNav = "catalog";
require dirname(__DIR__) . '/layout/header.php';
require dirname(__DIR__) . '/layout/navbar.php';
require dirname(__DIR__) . '/layout/toast.php';
?>

<div id="view-catalog" class="view-section">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--success);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Valeur Totale Stock</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= number_format($valeurStock ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 24px;">📦</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--accent);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Articles au Catalogue</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($produits ?? []) ?> références</div>
            </div>
            <span style="font-size: 24px;">🏷️</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Clients Enregistrés</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($clients ?? []) ?> clients</div>
            </div>
            <span style="font-size: 24px;">👥</span>
        </div>
    </div>

    <div style="display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
        <button id="catalog-tab-btn-products" class="nav-item active" style="padding: 10px 20px; font-size: 12px; text-transform: uppercase; font-weight: 700;" onclick="switchCatalogTab('products')">🏷️ Gestion Produits</button>
        <button id="catalog-tab-btn-clients" class="nav-item" style="padding: 10px 20px; font-size: 12px; text-transform: uppercase; font-weight: 700;" onclick="switchCatalogTab('clients')">👥 Gestion Clients</button>
        <button id="catalog-tab-btn-suppliers" class="nav-item" style="padding: 10px 20px; font-size: 12px; text-transform: uppercase; font-weight: 700;" onclick="switchCatalogTab('suppliers')">🤝 Gestion Fournisseurs</button>
    </div>

    <div id="catalog-panel-products" style="display: grid; grid-template-columns: 500px 1fr; gap: 32px; align-items: start;">
        <div class="panel-card" style="margin-bottom: 0;">
            <div class="panel-title">Ajouter un Article</div>
            <form method="POST" action="/catalogue/produit/ajouter">
                <div class="form-group">
                    <label for="libelle">Nom de l'Article</label>
                    <input type="text" name="libelle" class="form-control" placeholder="Ex: Carton de savon" required>
                </div>
                <div class="form-group">
                    <label for="prix_unitaire">Prix de Vente (FCFA)</label>
                    <input type="number" name="prix_unitaire" class="form-control" placeholder="Ex: 12000" min="0" required>
                </div>
                <div class="form-group">
                    <label for="quantite_stock">Stock Initial</label>
                    <input type="number" name="quantite_stock" class="form-control" placeholder="Ex: 50" min="0" required>
                </div>
                <div class="form-group">
                    <label for="seuil_alerte">Seuil d'Alerte</label>
                    <input type="number" name="seuil_alerte" class="form-control" value="5" min="1" required>
                </div>
                <button type="submit" class="btn-submit btn-success" style="width: 100%;">Enregistrer le Produit</button>
            </form>
        </div>

        <div class="panel-card" style="margin-bottom: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <label style="font-size: 13px; font-weight: 700; color: var(--accent); text-transform: uppercase;">Catalogue Courant</label>
                <input type="text" id="catalog-search" class="search-control" placeholder="Filtrer les produits..." onkeyup="filterProductsTable()">
            </div>
            <table class="debt-table" id="catalog-main-table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Prix de Vente</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucun article dans le catalogue.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produits as $p): ?>
                            <tr data-product-name="<?= strtolower(htmlspecialchars($p->getLibelle())) ?>">
                                <td style="font-weight: 700;"><?= htmlspecialchars($p->getLibelle()) ?></td>
                                <td><?= number_format($p->getPrixVente(), 0, ',', ' ') ?> F</td>
                                <td style="font-weight: 700; color: <?= $p->getQteStock() <= $p->getSeuilAlerte() ? 'var(--danger)' : 'var(--success)' ?>;">
                                    <?= $p->getQteStock() ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="catalog-panel-clients" style="display: none; grid-template-columns: 500px 1fr; gap: 32px; align-items: start;">
        <div class="panel-card" style="margin-bottom: 0;">
            <div class="panel-title">Enregistrer un Client</div>
            <form method="POST" action="/catalogue/client/ajouter">
                <div class="form-group">
                    <label for="nom_complet">Nom Complet</label>
                    <input type="text" name="nom_complet" class="form-control" placeholder="Ex: Abdou Ndiaye" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" placeholder="Ex: 776543210" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" class="form-control" placeholder="Ex: client@email.sn">
                </div>
                <div class="form-group">
                    <label for="plafond_dette">Plafond de Crédit (FCFA)</label>
                    <input type="number" name="plafond_dette" class="form-control" value="150000" min="0" required>
                </div>
                <button type="submit" class="btn-submit" style="width: 100%;">Créer le Compte Client</button>
            </form>
        </div>

        <div class="panel-card" style="margin-bottom: 0;">
            <label style="font-size: 13px; font-weight: 700; color: var(--accent); display: block; margin-bottom: 12px; text-transform: uppercase;">Répertoire Clients</label>
            <table class="debt-table" id="clients-main-table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Limite de Crédit</th>
                        <th>Encours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucun client enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $c): ?>
                            <tr>
                                <td style="font-weight: 700;"><?= htmlspecialchars($c->getNomComplet()) ?></td>
                                <td><?= htmlspecialchars($c->getTelephone() ?: '-') ?></td>
                                <td style="font-weight: 700; color: var(--accent);"><?= number_format($c->getLimiteCredit(), 0, ',', ' ') ?> F</td>
                                <td style="font-weight: 700; color: <?= $c->getTotalDettesActuelles() > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                    <?= number_format($c->getTotalDettesActuelles(), 0, ',', ' ') ?> F
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="catalog-panel-suppliers" style="display: none; grid-template-columns: 500px 1fr; gap: 32px; align-items: start;">
        <div class="panel-card" style="margin-bottom: 0;">
            <div class="panel-title">Enregistrer un Fournisseur</div>
            <form method="POST" action="/catalogue/fournisseur/ajouter">
                <div class="form-group">
                    <label for="nom">Nom de l'Entreprise</label>
                    <input type="text" name="nom" class="form-control" placeholder="Ex: Comptoir Céréalier Sénégalais" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="text" name="telephone" class="form-control" placeholder="Ex: 338245678" required>
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse / Dépôt</label>
                    <input type="text" name="adresse" class="form-control" placeholder="Ex: Hangar 4, Port de Dakar" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail (Optionnel)</label>
                    <input type="email" name="email" class="form-control" placeholder="Ex: contact@fournisseur.sn">
                </div>
                <button type="submit" class="btn-submit" style="width: 100%;">Créer le Fournisseur</button>
            </form>
        </div>

        <div class="panel-card" style="margin-bottom: 0;">
            <label style="font-size: 13px; font-weight: 700; color: var(--accent); display: block; margin-bottom: 12px; text-transform: uppercase;">Répertoire Fournisseurs</label>
            <table class="debt-table" id="suppliers-main-table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Entreprise</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fournisseurs)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucun fournisseur enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fournisseurs as $f): ?>
                            <tr>
                                <td style="font-weight: 700;"><?= htmlspecialchars($f->getNom()) ?></td>
                                <td><?= htmlspecialchars($f->getTelephone() ?: '-') ?></td>
                                <td><?= htmlspecialchars($f->getAdresse() ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function switchCatalogTab(tab) {
        document.getElementById('catalog-tab-btn-products').classList.remove('active');
        document.getElementById('catalog-tab-btn-clients').classList.remove('active');
        document.getElementById('catalog-tab-btn-suppliers').classList.remove('active');

        document.getElementById('catalog-panel-products').style.display = 'none';
        document.getElementById('catalog-panel-clients').style.display = 'none';
        document.getElementById('catalog-panel-suppliers').style.display = 'none';

        if (tab === 'products') {
            document.getElementById('catalog-tab-btn-products').classList.add('active');
            document.getElementById('catalog-panel-products').style.display = 'grid';
        } else if (tab === 'clients') {
            document.getElementById('catalog-tab-btn-clients').classList.add('active');
            document.getElementById('catalog-panel-clients').style.display = 'grid';
        } else if (tab === 'suppliers') {
            document.getElementById('catalog-tab-btn-suppliers').classList.add('active');
            document.getElementById('catalog-panel-suppliers').style.display = 'grid';
        }
    }

    function filterProductsTable() {
        const q = document.getElementById("catalog-search").value.toLowerCase();
        const rows = document.querySelectorAll("#catalog-main-table tbody tr[data-product-name]");
        rows.forEach(r => {
            const name = r.getAttribute("data-product-name");
            r.style.display = name.includes(q) ? "" : "none";
        });
        const table = document.getElementById("catalog-main-table");
        if (table && table.updatePagination) table.updatePagination();
    }

    document.addEventListener("DOMContentLoaded", () => {
        initPaginatedTable("catalog-main-table", 6);
        initPaginatedTable("clients-main-table", 6);
        initPaginatedTable("suppliers-main-table", 6);
    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
