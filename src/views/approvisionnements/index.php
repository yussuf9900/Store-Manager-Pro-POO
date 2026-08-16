<?php
$pageTitle = "Approvisionnements & Réceptions — StoreManager Pro";
$activeNav = "supplies";
require dirname(__DIR__) . '/layout/header.php';
require dirname(__DIR__) . '/layout/navbar.php';
require dirname(__DIR__) . '/layout/toast.php';
?>

<div id="view-supplies" class="view-section">
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--accent);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Coût Total des Entrées</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= number_format($totalCoutEntrees ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 24px;">📥</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--warning);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Bons de Réception (BL)</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($approvisionnements ?? []) ?> BL</div>
            </div>
            <span style="font-size: 24px;">📄</span>
        </div>
        <div class="panel-card" style="padding: 16px; display: flex; align-items: center; justify-content: space-between; border-left: 4px solid var(--success);">
            <div>
                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Fournisseurs Actifs</span>
                <div style="font-size: 18px; font-weight: 800; color: white; margin-top: 4px;"><?= count($fournisseurs ?? []) ?> entreprises</div>
            </div>
            <span style="font-size: 24px;">🤝</span>
        </div>
    </div>

    <div style="display: block;">
        <div class="panel-card" style="padding: 20px; margin-bottom: 0;">
            <div class="panel-title" style="font-size: 15px; margin-bottom: 16px;">Bordereaux de Livraison (Réceptions de Stock)</div>
            <table class="debt-table" id="supplies-main-table" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Réf BL</th>
                        <th>Fournisseur</th>
                        <th>Valeur Lot</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($approvisionnements)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px 0;">Aucun approvisionnement enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($approvisionnements as $app): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-muted); padding: 8px 0;"><?= htmlspecialchars($app->getNumeroBL()) ?></td>
                                <td style="padding: 8px 0;">
                                    <?= htmlspecialchars($app->getFournisseur() ? $app->getFournisseur()->getNom() : 'Fournisseur Inconnu') ?>
                                    <?php if ($app->getFournisseur() && $app->getFournisseur()->getTelephone()): ?>
                                        <div style="font-size:10px; color:var(--text-muted);">Tél : <?= htmlspecialchars($app->getFournisseur()->getTelephone()) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 800; color: var(--accent); padding: 8px 0;"><?= number_format($app->getMontantTotal(), 0, ',', ' ') ?> F</td>
                                <td style="padding: 8px 0;">
                                    <?php if ($app->isRecu()): ?>
                                        <span class="badge badge-success">REÇU</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">EN COURS</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 0; display: flex; gap: 6px;">
                                    <button class="btn-quick-action" onclick="toggleDetails('supply-details-<?= $app->getId() ?>')">Lignes</button>
                                    <?php if (!$app->isRecu()): ?>
                                        <button type="button" class="btn-quick-action" style="border-color: var(--success); color: var(--success); background: rgba(52, 211, 153, 0.05);" onclick="toggleDetails('supply-receive-drawer-<?= $app->getId() ?>')">Réceptionner</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" style="padding: 0; border: none;">
                                    <div class="details-drawer" id="supply-details-<?= $app->getId() ?>">
                                        <div style="font-weight: 700; font-size: 11px; color: var(--accent); margin-bottom: 6px;">Détails Réception :</div>
                                        <table class="debt-table" style="font-size: 10px;">
                                            <thead>
                                                <tr>
                                                    <th>Produit</th>
                                                    <th>Qté Livrée</th>
                                                    <th>Coût Unitaire</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($app->getLignes() ?? [] as $ligne): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ligne->getProduit() ? $ligne->getProduit()->getLibelle() : 'Produit #' . $ligne->getProduitId()) ?></td>
                                                        <td><?= $ligne->getQuantite() ?></td>
                                                        <td><?= number_format($ligne->getPrixAchatUnitaire(), 0, ',', ' ') ?> F</td>
                                                        <td style="font-weight: 700; color: var(--accent);"><?= number_format($ligne->getSousTotal(), 0, ',', ' ') ?> F</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <?php if (!$app->isRecu()): ?>
                                        <div class="details-drawer" id="supply-receive-drawer-<?= $app->getId() ?>" style="border: 1px solid rgba(52, 211, 153, 0.3); background: linear-gradient(180deg, rgba(11, 15, 25, 0.95) 0%, rgba(11, 15, 25, 0.98) 100%); border-radius: 14px; padding: 18px 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); max-width: 850px; margin: 12px 0;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span style="font-size: 16px;">📦</span>
                                                    <span style="font-weight: 800; font-size: 13px; color: var(--text-main);">
                                                        Réceptionner le BL — <span style="color: var(--accent);"><?= htmlspecialchars($app->getNumeroBL()) ?></span>
                                                    </span>
                                                </div>
                                                <div style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; color: var(--warning);">
                                                    Fournisseur : <?= htmlspecialchars($app->getFournisseur() ? $app->getFournisseur()->getNom() : 'Fournisseur Inconnu') ?>
                                                </div>
                                            </div>

                                            <form method="POST" action="/supplies/receptionner">
                                                <input type="hidden" name="approvisionnement_id" value="<?= $app->getId() ?>">

                                                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                                                    <?php foreach ($app->getLignes() ?? [] as $ligne): ?>
                                                        <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                                                            <div>
                                                                <div style="font-weight: 700; font-size: 13px; color: white;">
                                                                    <?= htmlspecialchars($ligne->getProduit() ? $ligne->getProduit()->getLibelle() : 'Produit #' . $ligne->getProduitId()) ?>
                                                                </div>
                                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                                    Quantité théorique commandée : <strong style="color: var(--text-main);"><?= $ligne->getQuantite() ?></strong>
                                                                </div>
                                                            </div>
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <label style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Qté Reçue :</label>
                                                                <input type="number" name="quantites_livrees[<?= $ligne->getId() ?? $ligne->getProduitId() ?>]" class="form-control" value="<?= $ligne->getQuantite() ?>" min="0" required style="width: 100px; padding: 6px 10px; font-size: 13px; font-weight: 700; text-align: center; background: #0b0f1a;">
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div style="display: flex; justify-content: flex-end;">
                                                    <button type="submit" class="btn-submit btn-success" style="padding: 11px 24px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px; border-radius: 10px;">
                                                        ✓ Valider la Réception en Stock
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
    document.addEventListener("DOMContentLoaded", () => {
        initPaginatedTable("supplies-main-table", 8);
    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
