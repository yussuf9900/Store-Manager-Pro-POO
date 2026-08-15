<?php
$pageTitle = "Gestion des Dettes & Créances — StoreManager Pro";
$activeNav = "dettes";
require dirname(__DIR__) . '/layout/header.php';
require dirname(__DIR__) . '/layout/navbar.php';
require dirname(__DIR__) . '/layout/toast.php';
?>

<div id="view-dettes" class="view-section">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--danger);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Encours Total Dû</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= number_format($totalEncours ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 24px;">🛑</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Clients Débiteurs</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($dettes ?? []) ?></div>
            </div>
            <span style="font-size: 24px;">👥</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--success);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Recouvrements</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= number_format($totalRecouvrements ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 24px;">📈</span>
        </div>
    </div>

    <div class="panel-card" style="margin-bottom: 0;">
        <div class="panel-title">
            <span>Registre des Dettes Actives</span>
            <input type="text" id="debt-search" class="search-control" placeholder="Rechercher un client..." onkeyup="filterDebtsTable()">
        </div>
        <table class="debt-table" id="debts-main-table">
            <thead>
                <tr>
                    <th>ID Dette</th>
                    <th>Date Création</th>
                    <th>Client</th>
                    <th>Montant Initial</th>
                    <th>Montant Payé</th>
                    <th>Reste Dû</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dettes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 24px 0;">Aucune dette active enregistrée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dettes as $d): ?>
                        <tr id="debt-row-<?= $d->getId() ?>" data-client-name="<?= strtolower(htmlspecialchars($d->getClient() ? $d->getClient()->getNomComplet() . ' ' . $d->getClient()->getTelephone() : '')) ?>">
                            <td style="font-weight: 700; color: var(--text-muted);">
                                #DT-<?= $d->getId() ?>
                                <?php if ($d->getVenteId()): ?>
                                    <span style="font-size: 10px; color: var(--text-muted); display: block; font-weight: normal; margin-top: 2px;">#CMD-<?= $d->getVenteId() ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px;"><?= $d->getDateCreation()->format('d/m/Y H:i') ?></td>
                            <td style="font-weight: 700;">
                                <?= htmlspecialchars($d->getClient() ? $d->getClient()->getNomComplet() : 'Client Inconnu') ?>
                                <?php if ($d->getClient() && $d->getClient()->getTelephone()): ?>
                                    <div style="font-size:11px; color:var(--text-muted); font-weight:normal;">Tél : <?= htmlspecialchars($d->getClient()->getTelephone()) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; color: var(--text-main);"><?= number_format($d->getMontantInitial(), 0, ',', ' ') ?> F</td>
                            <td style="font-weight: 700; color: var(--success);"><?= number_format($d->getMontantPaye(), 0, ',', ' ') ?> F</td>
                            <td style="color: var(--danger); font-weight: 800;"><?= number_format($d->getMontantRestant(), 0, ',', ' ') ?> F</td>
                            <td>
                                <?php if ($d->estSoldee()): ?>
                                    <span class="badge badge-success">SOLDÉE</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">NON SOLDÉE</span>
                                <?php endif; ?>
                            </td>
                            <td style="display: flex; gap: 6px;">
                                <button class="btn-quick-action" style="border-color: var(--warning); color: var(--warning);" onclick="toggleDetails('debt-repay-drawer-<?= $d->getId() ?>')">Rembourser</button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" style="padding: 0; border: none;">
                                <div class="details-drawer" id="debt-repay-drawer-<?= $d->getId() ?>" style="border: 1px solid rgba(45, 212, 191, 0.25); background: linear-gradient(180deg, rgba(11, 15, 25, 0.95) 0%, rgba(11, 15, 25, 0.98) 100%); border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); max-width: 850px; margin: 12px 0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-size: 16px;">💳</span>
                                            <span style="font-weight: 800; font-size: 13px; color: var(--text-main);">
                                                Nouveau Remboursement — <span style="color: var(--accent);"><?= htmlspecialchars($d->getClient() ? $d->getClient()->getNomComplet() : '') ?></span>
                                            </span>
                                        </div>
                                        <div style="background: rgba(244, 63, 94, 0.12); border: 1px solid rgba(244, 63, 94, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; color: var(--danger);">
                                            Reste dû : <?= number_format($d->getMontantRestant(), 0, ',', ' ') ?> FCFA
                                        </div>
                                    </div>

                                    <form method="POST" action="/dettes/rembourser" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                                        <input type="hidden" name="dette_id" value="<?= $d->getId() ?>">

                                        <div style="flex: 1; min-width: 200px;">
                                            <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Montant du Versement (FCFA)</label>
                                            <input type="number" name="montant" class="form-control" max="<?= $d->getMontantRestant() ?>" value="<?= $d->getMontantRestant() ?>" min="1" required style="font-size: 13px; font-weight: 700; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;">
                                        </div>

                                        <div style="flex: 1; min-width: 200px;">
                                            <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Canal de Paiement</label>
                                            <select name="mode_paiement_id" class="form-control" style="font-size: 13px; font-weight: 600; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;" required>
                                                <option value="1">💵 Espèces (Cash)</option>
                                                <option value="2">🌊 Wave</option>
                                                <option value="3">🟠 Orange Money</option>
                                                <option value="4">💳 Carte Bancaire</option>
                                            </select>
                                        </div>

                                        <div>
                                            <button type="submit" class="btn-submit btn-success" style="padding: 11px 24px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; border-radius: 10px; height: 42px;">
                                                ✓ Enregistrer le Remboursement
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterDebtsTable() {
        const q = document.getElementById("debt-search").value.toLowerCase();
        const rows = document.querySelectorAll("#debts-main-table tbody tr[data-client-name]");
        rows.forEach(r => {
            const name = r.getAttribute("data-client-name");
            r.style.display = name.includes(q) ? "" : "none";
        });
        const table = document.getElementById("debts-main-table");
        if (table && table.updatePagination) table.updatePagination();
    }

    document.addEventListener("DOMContentLoaded", () => {
        initPaginatedTable("debts-main-table", 8);
    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
