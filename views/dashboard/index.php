<?php
$pageTitle = "Tableau de Bord — StoreManager Pro";
$activeNav = "dashboard";
require dirname(__DIR__) . '/layout/header.php';
require dirname(__DIR__) . '/layout/navbar.php';
require dirname(__DIR__) . '/layout/toast.php';
?>

<div id="view-dashboard" class="view-section">
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid var(--success);">
            <div>
                <div class="kpi-label">Ventes Comptant</div>
                <div class="kpi-val" style="color: var(--success);"><?= number_format($statistiques['montant_encaisse'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--success); stroke-dashoffset: 20;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--danger);">
            <div>
                <div class="kpi-label">Dettes à Récupérer</div>
                <div class="kpi-val" style="color: var(--danger);"><?= number_format($statistiques['montant_credit'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--danger); stroke-dashoffset: 70;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--accent);">
            <div>
                <div class="kpi-label">Chiffre d'Affaires</div>
                <div class="kpi-val" style="color: var(--accent);"><?= number_format($statistiques['chiffre_affaires'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--accent); stroke-dashoffset: 40;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--warning);">
            <div>
                <div class="kpi-label">Commandes du Jour</div>
                <div class="kpi-val" style="color: var(--warning);"><?= (int)($statistiques['total_ventes'] ?? 0) ?> ventes</div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--warning); stroke-dashoffset: 15;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--success);">
            <div>
                <div class="kpi-label">Taux Encaissement</div>
                <div class="kpi-val" style="color: var(--success);">
                    <?php 
                        $ca = (float)($statistiques['chiffre_affaires'] ?? 0);
                        $enc = (float)($statistiques['montant_encaisse'] ?? 0);
                        $taux = ($ca > 0) ? round(($enc / $ca) * 100, 1) : 100;
                        echo $taux . ' %';
                    ?>
                </div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--success); stroke-dashoffset: <?= max(0, 157 - (157 * ($taux / 100))) ?>;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--accent);">
            <div>
                <div class="kpi-label">Panier Moyen</div>
                <div class="kpi-val" style="color: var(--accent);"><?= number_format($statistiques['panier_moyen'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <div class="progress-ring-container">
                <svg class="progress-ring" width="60" height="60">
                    <circle class="progress-ring-circle-bg" cx="30" cy="30" r="25"/>
                    <circle class="progress-ring-circle" style="stroke: var(--accent); stroke-dashoffset: 50;" cx="30" cy="30" r="25"/>
                </svg>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 32px; align-items: start;">
        
        <div class="panel-card" style="padding: 20px;">
            <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <button id="dash-left-tab-sales" class="nav-item active" style="flex: 1; padding: 10px; font-size: 11px; text-transform: uppercase;" onclick="switchDashLeftTab('sales')">🛒 Ventes Récentes</button>
                <button id="dash-left-tab-ruptures" class="nav-item" style="flex: 1; padding: 10px; font-size: 11px; text-transform: uppercase;" onclick="switchDashLeftTab('ruptures')">⚠️ Ruptures & Alertes</button>
            </div>

            <div id="dash-left-panel-sales">
                <div class="panel-title">Flux de Ventes Récentes</div>
                <table class="debt-table">
                    <thead>
                        <tr>
                            <th>Facture</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventesRecentes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucune vente enregistrée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ventesRecentes as $v): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--text-muted);">#CMD-<?= $v->getId() ?></td>
                                    <td><?= $v->getDateVente()->format('d/m H:i') ?></td>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($v->getClient() ? $v->getClient()->getNomComplet() : 'Client Comptoir') ?></td>
                                    <td style="font-weight: 800; color: var(--accent);"><?= number_format($v->getMontantTotal(), 0, ',', ' ') ?> F</td>
                                    <td style="color: white; font-weight: 700; font-size: 11px;">
                                        <?= htmlspecialchars($v->getModePaiement() ? $v->getModePaiement()->getLibelle() : 'Espèces') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="dash-left-panel-ruptures" style="display: none;">
                <div class="panel-title" style="border-left-color: var(--danger);">Ruptures & Stocks Critiques</div>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php if (empty($produitsEnAlerte ?? [])): ?>
                        <div style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucun produit en rupture ou seuil critique.</div>
                    <?php else: ?>
                        <?php foreach ($produitsEnAlerte as $p): ?>
                            <div style="background: rgba(251,191,36,0.05); padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.02);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 13px;"><?= htmlspecialchars($p->getLibelle()) ?></div>
                                        <div style="color: <?= $p->getQteStock() <= 0 ? 'var(--danger)' : 'var(--warning)' ?>; font-weight: 800; font-size: 11px;"><?= $p->getQteStock() ?> en stock (Seuil: <?= $p->getSeuilAlerte() ?>)</div>
                                    </div>
                                    <a href="/supplies" class="btn-quick-action" style="border-color: var(--accent); color: var(--accent); background: rgba(45, 212, 191, 0.05);">Approvisionner</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel-card" style="padding: 20px;">
            <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <button id="dash-right-tab-debtors" class="nav-item active" style="flex: 1; padding: 10px; font-size: 11px; text-transform: uppercase;" onclick="switchDashRightTab('debtors')">👥 Clients Débiteurs</button>
            </div>

            <div id="dash-right-panel-debtors">
                <div class="panel-title" style="border-left-color: var(--danger);">Clients avec Dettes en cours</div>
                <table class="debt-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Téléphone</th>
                            <th>Cumul Dû</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientsDebiteurs ?? [])): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px 0;">Aucun client débiteur enregistré.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientsDebiteurs as $c): ?>
                                <tr>
                                    <td style="font-weight: 700;"><?= htmlspecialchars($c->getNomComplet()) ?></td>
                                    <td style="color: var(--text-muted);"><?= htmlspecialchars($c->getTelephone()) ?></td>
                                    <td style="font-weight: 800; color: var(--danger);"><?= number_format($c->getTotalDettesActuelles(), 0, ',', ' ') ?> F</td>
                                    <td>
                                        <a href="/dettes" class="btn-quick-action" style="border-color: var(--accent); color: var(--accent); background: rgba(45, 212, 191, 0.05);">Gérer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function switchDashLeftTab(tab) {
        document.getElementById('dash-left-tab-sales').classList.remove('active');
        document.getElementById('dash-left-tab-ruptures').classList.remove('active');
        document.getElementById('dash-left-panel-sales').style.display = 'none';
        document.getElementById('dash-left-panel-ruptures').style.display = 'none';

        if (tab === 'sales') {
            document.getElementById('dash-left-tab-sales').classList.add('active');
            document.getElementById('dash-left-panel-sales').style.display = 'block';
        } else if (tab === 'ruptures') {
            document.getElementById('dash-left-tab-ruptures').classList.add('active');
            document.getElementById('dash-left-panel-ruptures').style.display = 'block';
        }
    }

    function switchDashRightTab(tab) {
        document.getElementById('dash-right-tab-debtors').classList.add('active');
        document.getElementById('dash-right-panel-debtors').style.display = 'block';
    }
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
