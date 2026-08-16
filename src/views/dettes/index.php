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
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Créances Actives</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= number_format($totalEncours ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 24px;">💸</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Clients Débiteurs</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($dettes ?? []) ?> clients</div>
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

    <div style="display: block;">
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
                            <tr id="debt-row-<?= $d->getId() ?>" data-client-name="<?= strtolower(htmlspecialchars(($d->getClient() ? $d->getClient()->getNomComplet() . ' ' . $d->getClient()->getTelephone() : '') . ' dt-' . $d->getId())) ?>" style="transition: all 0.2s;">
                                <td style="font-weight: 700; color: var(--text-muted);">
                                    #DT-<?= $d->getId() ?>
                                    <?php if ($d->getVenteId()): ?>
                                        <span style="font-size: 10px; color: var(--text-muted); display: block; font-weight: normal; margin-top: 2px;">#CMD-<?= $d->getVenteId() ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px;"><?= $d->getDateCreation()->format('d M Y H:i') ?></td>
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
                                    <span class="badge <?= $d->estSoldee() ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $d->estSoldee() ? 'SOLDÉE' : 'NON SOLDEE' ?>
                                    </span>
                                </td>
                                <td style="display: flex; gap: 6px;">
                                    <button class="btn-quick-action" onclick="toggleDetails('debt-lines-<?= $d->getId() ?>')">Articles</button>
                                    <button class="btn-quick-action" style="border-color: var(--accent); color: var(--accent);" onclick="toggleDetails('debt-details-<?= $d->getId() ?>')">💳 Paiements</button>
                                    <?php if (!$d->estSoldee()): ?>
                                        <button class="btn-quick-action" style="border-color: var(--warning); color: var(--warning);" onclick="toggleDetails('debt-repay-drawer-<?= $d->getId() ?>')">Rembourser</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="8" style="padding: 0; border: none;">
                                    <div class="details-drawer" id="debt-details-<?= $d->getId() ?>" style="display: none;">
                                        <div style="font-weight: 700; font-size: 12px; color: var(--accent); margin-bottom: 8px;">Paiements enregistrés :</div>
                                        <table class="debt-table" style="font-size: 11px;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Versement</th>
                                                    <th>Mode</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($d->getPaiements())): ?>
                                                    <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">Aucun acompte versé.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($d->getPaiements() as $p): ?>
                                                        <tr>
                                                            <td><?= $p->getDatePaiement()->format('Y-m-d H:i:s') ?></td>
                                                            <td style="font-weight: 700; color: var(--success);"><?= number_format($p->getMontant(), 0, ',', ' ') ?> F</td>
                                                            <td><?= htmlspecialchars($p->getModePaiement() ? $p->getModePaiement()->getLibelle() : 'Espèces') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="details-drawer" id="debt-lines-<?= $d->getId() ?>" style="display: none;">
                                        <div style="font-weight: 700; font-size: 12px; color: var(--accent); margin-bottom: 8px;">Articles de la Vente à Crédit :</div>
                                        <table class="debt-table" style="font-size: 11px;">
                                            <thead>
                                                <tr>
                                                    <th>Produit</th>
                                                    <th>Qté</th>
                                                    <th>P.U.</th>
                                                    <th>Sous-total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if ($d->getVente() && !empty($d->getVente()->getLignes())): ?>
                                                    <?php foreach ($d->getVente()->getLignes() as $lv): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($lv->getProduit() ? $lv->getProduit()->getLibelle() : 'Article #' . $lv->getProduitId()) ?></td>
                                                            <td><?= $lv->getQuantite() ?></td>
                                                            <td><?= number_format($lv->getPrixUnitaire(), 0, ',', ' ') ?> F</td>
                                                            <td style="font-weight: 700; color: var(--accent);"><?= number_format($lv->getSousTotal(), 0, ',', ' ') ?> F</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Aucun détail d'article disponible.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if (!$d->estSoldee()): ?>
                                    <div class="details-drawer" id="debt-repay-drawer-<?= $d->getId() ?>" style="display: none; border: 1px solid rgba(45, 212, 191, 0.25); background: linear-gradient(180deg, rgba(11, 15, 25, 0.95) 0%, rgba(11, 15, 25, 0.98) 100%); border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); max-width: 850px; margin: 12px 0;">
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

                                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 16px;">
                                            <span style="font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Raccourcis :</span>
                                            <button type="button" onclick="setRepayAmount(<?= $d->getId() ?>, <?= $d->getMontantRestant() ?>)" style="background: rgba(45, 212, 191, 0.1); border: 1px solid var(--accent); color: var(--accent); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer;">Tout solder (<?= number_format($d->getMontantRestant(), 0, ',', ' ') ?> F)</button>
                                            <button type="button" onclick="setRepayAmount(<?= $d->getId() ?>, <?= round($d->getMontantRestant() * 0.5) ?>)" style="background: rgba(255, 255, 255, 0.04); border: 1px solid var(--border-color); color: var(--text-main); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer;">50% (<?= number_format(round($d->getMontantRestant() * 0.5), 0, ',', ' ') ?> F)</button>
                                        </div>

                                        <form method="POST" action="/dettes/rembourser" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                                            <input type="hidden" name="dette_id" value="<?= $d->getId() ?>">

                                            <div style="flex: 1; min-width: 200px;">
                                                <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Montant du Versement (FCFA)</label>
                                                <div style="position: relative;">
                                                    <input type="number" name="montant" id="repay-input-<?= $d->getId() ?>" class="form-control" max="<?= $d->getMontantRestant() ?>" value="<?= $d->getMontantRestant() ?>" min="1" step="any" required style="font-size: 13px; font-weight: 700; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;">
                                                </div>
                                            </div>

                                            <div style="flex: 1; min-width: 200px;">
                                                <label style="font-size: 10px; color: var(--text-muted); display: block; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Canal de Paiement</label>
                                                <select name="mode_paiement_id" class="form-control" style="font-size: 13px; font-weight: 600; padding: 10px 12px; background: #0b0f19; border: 1px solid var(--border-color); color: white; width: 100%;" required>
                                                    <option value="3">🟠 Orange Money</option>
                                                    <option value="2">🌊 Wave</option>
                                                    <option value="1">💵 Espèces (Cash)</option>
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
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function setRepayAmount(debtId, amount) {
        const input = document.getElementById('repay-input-' + debtId);
        if (input) {
            input.value = amount;
        }
    }

    function toggleDetails(drawerId) {
        const drawer = document.getElementById(drawerId);
        if (!drawer) return;
        drawer.style.display = (drawer.style.display === 'none' || drawer.style.display === '') ? 'block' : 'none';
    }

    function filterDebtsTable() {
        const q = (document.getElementById("debt-search").value || "").toLowerCase();
        const rows = document.querySelectorAll("#debts-main-table tbody tr[data-client-name]");
        rows.forEach(r => {
            const name = r.getAttribute("data-client-name") || "";
            const isMatch = name.includes(q);
            r.style.display = isMatch ? "" : "none";
            const drawerRow = r.nextElementSibling;
            if (!isMatch && drawerRow) {
                const drawers = drawerRow.querySelectorAll('.details-drawer');
                drawers.forEach(d => d.style.display = 'none');
            }
        });
        const table = document.getElementById("debts-main-table");
        if (table && table.updatePagination) table.updatePagination();
    }

    document.addEventListener("DOMContentLoaded", () => {
        if (typeof initPaginatedTable === 'function') {
            initPaginatedTable("debts-main-table", 8);
        }
    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
