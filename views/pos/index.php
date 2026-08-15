<?php
$pageTitle = "Terminal de Caisse Tactile (POS) — StoreManager Pro";
$activeNav = "pos";
require dirname(__DIR__) . '/layout/header.php';
require dirname(__DIR__) . '/layout/navbar.php';
require dirname(__DIR__) . '/layout/toast.php';
?>

<div id="view-pos" class="view-section">
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid var(--success);">
            <div>
                <div class="kpi-label">CA Encaissé Net</div>
                <div class="kpi-val" style="color: var(--success);"><?= number_format($statistiques['montant_encaisse'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 28px;">💰</span>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--danger);">
            <div>
                <div class="kpi-label">Encours Client Total</div>
                <div class="kpi-val" style="color: var(--danger);"><?= number_format($statistiques['montant_credit'] ?? 0, 0, ',', ' ') ?> F</div>
            </div>
            <span style="font-size: 28px;">🛑</span>
        </div>

        <div class="kpi-card" style="border-left: 4px solid var(--accent);">
            <div>
                <div class="kpi-label">Commandes Enregistrées</div>
                <div class="kpi-val" style="color: var(--accent);"><?= (int)($statistiques['total_ventes'] ?? 0) ?></div>
            </div>
            <span style="font-size: 28px;">📊</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 600px 1fr; gap: 32px; align-items: start; margin-bottom: 32px;">
        
        <div class="panel-card" style="margin-bottom: 0; padding: 24px; border: 1px solid rgba(59, 130, 246, 0.2); background: linear-gradient(180deg, rgba(17, 24, 43, 0.5) 0%, rgba(10, 15, 30, 0.3) 100%); position: sticky; top: 24px;">
            <div class="panel-title" style="border-left-color: var(--accent); display: flex; justify-content: space-between; align-items: center;">
                <span>🛒 Nouvelle Vente</span>
                <span style="font-size: 11px; font-weight: 600; color: var(--text-muted); background: rgba(255,255,255,0.03); padding: 4px 8px; border-radius: 6px;">Terminal POS</span>
            </div>

            <form id="order-creation-form" method="POST" action="/pos/valider">
                
                <div class="form-group">
                    <label for="pos-client-select">Client Acheteur</label>
                    <div style="position: relative;">
                        <select id="pos-client-select" name="client_id" class="form-control" style="width: 100%; appearance: none; padding-right: 30px;" onchange="updateClientLimitInfo()">
                            <option value="0" data-limit="0" data-debt="0" data-available="0">Client Comptoir (Vente Directe)</option>
                            <?php foreach ($clients as $c): 
                                $limite = $c->getLimiteCredit();
                                $dette = $c->getTotalDettesActuelles();
                                $dispo = $c->getCreditDisponible();
                            ?>
                                <option value="<?= $c->getId() ?>" data-limit="<?= $limite ?>" data-debt="<?= $dette ?>" data-available="<?= $dispo ?>">
                                    <?= htmlspecialchars($c->getNomComplet()) ?> (<?= htmlspecialchars($c->getTelephone() ?: 'Sans tél') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--text-muted); font-size: 12px;">▼</span>
                    </div>
                    
                    <div id="credit-limit-info" style="display: none; font-size: 11px; background: rgba(0,0,0,0.3); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; margin-top: 6px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                            <span>Encours actuel : <b id="client-current-debt" style="color: var(--danger);">0 F</b></span>
                            <span>Plafond : <b id="client-max-limit">0 F</b></span>
                        </div>
                        <div style="width: 100%; height: 4px; background: rgba(255,255,255,0.1); border-radius: 2px; overflow: hidden; margin-bottom: 4px;">
                            <div id="client-limit-gauge" style="width: 0%; height: 100%; background: var(--success); transition: width 0.3s;"></div>
                        </div>
                        <div style="text-align: right; color: var(--accent); font-weight: 700;">
                            Crédit disponible : <span id="client-available-credit">0 F</span>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px dashed var(--border-color); padding-top: 16px; margin-top: 16px; margin-bottom: 16px;">
                    <label style="font-size: 12px; font-weight: 700; color: var(--accent); display: block; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Sélection des Articles</label>
                    <div style="display: grid; grid-template-columns: 2.2fr 0.8fr auto; gap: 8px; align-items: flex-end; margin-bottom: 16px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="pos-item-select" style="font-size: 10px;">Article</label>
                            <select id="pos-item-select" class="form-control" style="background-color: #0b0f1a; color: white; padding: 10px; font-size: 12px;">
                                <option value="" disabled selected>Sélectionner un article...</option>
                                <?php foreach ($produits as $p): 
                                    $badge = ($p->getQteStock() > 10) ? '🟢' : (($p->getQteStock() > 0) ? '🟡' : '🔴');
                                ?>
                                    <option value="<?= $p->getId() ?>" data-price="<?= $p->getPrixVente() ?>" data-name="<?= htmlspecialchars($p->getLibelle()) ?>" data-stock="<?= $p->getQteStock() ?>">
                                        <?= $badge ?> <?= htmlspecialchars($p->getLibelle()) ?> (<?= $p->getQteStock() ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 0; position: relative;">
                            <label for="pos-qty" style="font-size: 10px;">Qté</label>
                            <input type="number" id="pos-qty" class="form-control" value="1" min="1" style="padding: 10px; font-size: 12px;">
                        </div>

                        <button type="button" class="btn-submit" onclick="addToCart(event)" style="height: 38px; width: 38px; font-size: 18px; display: flex; justify-content: center; align-items: center; border-radius: 8px; padding: 0; flex-shrink: 0; min-width: 38px;">+</button>
                    </div>

                    <table class="debt-table" style="font-size: 11px; margin-top: 16px;">
                        <thead>
                            <tr>
                                <th style="padding-bottom: 8px;">Produit</th>
                                <th style="padding-bottom: 8px;">Qté</th>
                                <th style="padding-bottom: 8px;">Total</th>
                                <th style="padding-bottom: 8px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-rows">
                            <tr id="empty-cart-row">
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px 0; border-bottom: none;">Panier vide. Ajoutez des articles.</td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="hidden-cart-inputs"></div>
                </div>

                <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(30, 41, 59, 0.4) 100%); border: 1px solid rgba(59, 130, 246, 0.15); border-radius: 16px; padding: 14px; text-align: center; margin-bottom: 20px; box-shadow: inset 0 0 15px rgba(59, 130, 246, 0.08);">
                    <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 1px; display: block; margin-bottom: 4px;">Montant Total Net à Payer</span>
                    <div style="font-size: 24px; font-weight: 900; color: #60a5fa; letter-spacing: -0.5px; font-family: monospace; text-shadow: 0 0 10px rgba(96, 165, 250, 0.3);">
                        <span id="montant_total_display_text">0</span> <span style="font-size: 14px; font-weight: 700;">FCFA</span>
                    </div>
                    <input type="hidden" name="montant_total" id="montant_total_display" value="0">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="pos-mode-reglement" style="font-size: 10px;">Règlement</label>
                        <select name="mode_reglement" id="pos-mode-reglement" class="form-control" style="background-color: #0b0f1a; padding: 10px; font-size: 12px;" onchange="toggleMontantVerseVisibility()">
                            <?php foreach ($modesPaiement as $m): 
                                $mId = is_object($m) ? $m->getId() : ($m['id'] ?? 1);
                                $mLib = is_object($m) ? $m->getLibelle() : ($m['libelle'] ?? 'Espèces');
                            ?>
                                <option value="<?= htmlspecialchars($mLib) ?>" data-id="<?= $mId ?>"><?= htmlspecialchars($mLib) ?></option>
                            <?php endforeach; ?>
                            <option value="Dette">Crédit Total (Dette)</option>
                        </select>
                        <input type="hidden" name="mode_paiement_id" id="pos-mode-paiement-id" value="1">
                    </div>

                    <div class="form-group" id="group-montant-verse" style="margin-bottom: 0; position: relative;">
                        <label for="pos-montant-verse" style="font-size: 10px;">Versé (Avance)</label>
                        <input type="number" name="montant_verse" id="pos-montant-verse" class="form-control" value="0" min="0" style="padding: 10px; font-size: 12px; color: var(--success); font-weight: 700;">
                    </div>
                </div>

                <button type="submit" class="btn-submit btn-success" style="padding: 14px; font-weight: 800; font-size: 13px; width: 100%;">
                    Valider la Vente (DML)
                </button>
            </form>
        </div>

        <div class="panel-card" style="margin-bottom: 0;">
            <div class="panel-title">Registre Général des Ventes & Commandes</div>
            <table class="debt-table" id="orders-main-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Total Facture</th>
                        <th>Règlement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventesRecentes)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px 0;">Aucune vente enregistrée aujourd'hui.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ventesRecentes as $v): 
                            if ($v->getMontantPaye() <= 0 && $v->getMontantRestant() > 0) {
                                $reglementText = 'CRÉDIT TOTAL';
                            } elseif ($v->getMontantPaye() > 0 && $v->getMontantRestant() > 0) {
                                $reglementText = 'AVANCE (Credit)';
                            } else {
                                $modeLibelle = $v->getModePaiement() ? $v->getModePaiement()->getLibelle() : 'Espèces';
                                $reglementText = 'COMPTANT (' . $modeLibelle . ')';
                            }
                        ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--text-muted);">#CMD-<?= $v->getId() ?></td>
                                <td style="font-weight: 700;">
                                    <?= htmlspecialchars($v->getClient() ? $v->getClient()->getNomComplet() : 'Client Comptoir') ?>
                                    <?php if ($v->getClient() && $v->getClient()->getTelephone()): ?>
                                        <div style="font-size:11px; color:var(--text-muted); font-weight:normal;">Tél : <?= htmlspecialchars($v->getClient()->getTelephone()) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 800; color: var(--accent);"><?= number_format($v->getMontantTotal(), 0, ',', ' ') ?> F</td>
                                <td style="color: #ffffff; font-weight: 700; font-size: 11px;">
                                    <?= htmlspecialchars($reglementText) ?>
                                </td>
                                <td>
                                    <button class="btn-quick-action" onclick="toggleDetails('order-details-<?= $v->getId() ?>')">Lignes</button>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="5" style="padding: 0; border: none;">
                                    <div class="details-drawer" id="order-details-<?= $v->getId() ?>">
                                        <div style="font-weight: 700; font-size: 12px; color: var(--accent); margin-bottom: 8px;">Détails Facture (<?= htmlspecialchars($v->getNumeroFacture()) ?>) :</div>
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
                                                <?php foreach ($v->getLignes() as $ligne): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($ligne->getProduit() ? $ligne->getProduit()->getLibelle() : 'Article #' . $ligne->getProduitId()) ?></td>
                                                        <td><?= $ligne->getQuantite() ?></td>
                                                        <td><?= number_format($ligne->getPrixUnitaire(), 0, ',', ' ') ?> F</td>
                                                        <td style="font-weight: 700; color: var(--accent);"><?= number_format($ligne->getSousTotal(), 0, ',', ' ') ?> F</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
    let posCart = [];

    function updateClientLimitInfo() {
        const sel = document.getElementById("pos-client-select");
        const opt = sel.options[sel.selectedIndex];
        const info = document.getElementById("credit-limit-info");
        const limit = parseFloat(opt.getAttribute("data-limit")) || 0;
        const debt = parseFloat(opt.getAttribute("data-debt")) || 0;
        const available = parseFloat(opt.getAttribute("data-available")) || 0;

        if (parseInt(opt.value) === 0 || limit === 0) {
            info.style.display = "none";
        } else {
            info.style.display = "block";
            document.getElementById("client-current-debt").innerText = debt.toLocaleString("fr-FR") + " F";
            document.getElementById("client-max-limit").innerText = limit.toLocaleString("fr-FR") + " F";
            document.getElementById("client-available-credit").innerText = available.toLocaleString("fr-FR") + " F";

            const gauge = document.getElementById("client-limit-gauge");
            const pct = Math.min(100, Math.round((debt / limit) * 100));
            gauge.style.width = pct + "%";
            gauge.style.background = (pct > 80) ? "var(--danger)" : (pct > 50 ? "var(--warning)" : "var(--success)");
        }
    }

    function addToCart(e) {
        if (e && e.preventDefault) e.preventDefault();
        const itemSelect = document.getElementById("pos-item-select");
        const qtyInput = document.getElementById("pos-qty");

        if (!itemSelect.value) {
            alert("Veuillez sélectionner un produit.");
            return;
        }

        const opt = itemSelect.options[itemSelect.selectedIndex];
        const id = parseInt(opt.value);
        const name = opt.getAttribute("data-name") || opt.getAttribute("data-libelle");
        const price = parseFloat(opt.getAttribute("data-price"));
        const stock = parseInt(opt.getAttribute("data-stock"));
        const qty = parseInt(qtyInput.value) || 1;

        const existing = posCart.find(i => i.id === id);
        const currentQtyInCart = existing ? existing.qty : 0;

        if (currentQtyInCart + qty > stock) {
            alert("Stock insuffisant ! Disponible : " + stock);
            return;
        }

        if (existing) {
            existing.qty += qty;
        } else {
            posCart.push({ id, name, price, qty, stock });
        }

        qtyInput.value = 1;
        itemSelect.selectedIndex = 0;
        renderCart();
    }

    function removeFromCart(index) {
        posCart.splice(index, 1);
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById("cart-rows");
        const hiddenInputs = document.getElementById("hidden-cart-inputs");
        const totalDisplay = document.getElementById("montant_total_display_text");
        const totalInput = document.getElementById("montant_total_display");
        const montantVerseInput = document.getElementById("pos-montant-verse");

        if (posCart.length === 0) {
            tbody.innerHTML = '<tr id="empty-cart-row"><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px 0; border-bottom: none;">Panier vide. Ajoutez des articles.</td></tr>';
            hiddenInputs.innerHTML = '';
            totalDisplay.innerText = '0';
            if (totalInput) totalInput.value = '0';
            if (montantVerseInput) montantVerseInput.value = '0';
            return;
        }

        let html = '';
        let hiddenHtml = '';
        let total = 0;

        posCart.forEach((item, idx) => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            html += `
                <tr>
                    <td style="font-weight: 700;">${item.name}</td>
                    <td>${item.qty}</td>
                    <td style="font-weight: 700; color: var(--accent);">${subtotal.toLocaleString('fr-FR')} F</td>
                    <td><button type="button" onclick="removeFromCart(${idx})" class="btn-quick-action" style="padding: 2px 6px; border-color: var(--danger); color: var(--danger);">🗑️</button></td>
                </tr>
            `;
            hiddenHtml += `
                <input type="hidden" name="product_ids[]" value="${item.id}">
                <input type="hidden" name="product_qtys[]" value="${item.qty}">
            `;
        });

        tbody.innerHTML = html;
        hiddenInputs.innerHTML = hiddenHtml;
        totalDisplay.innerText = total.toLocaleString('fr-FR');
        if (totalInput) totalInput.value = total;
        
        const modeSelect = document.getElementById("pos-mode-reglement");
        if (modeSelect && modeSelect.value !== "Dette" && montantVerseInput) {
            montantVerseInput.value = total;
        }
    }

    function toggleMontantVerseVisibility() {
        const mode = document.getElementById("pos-mode-reglement");
        const opt = mode.options[mode.selectedIndex];
        const hiddenId = document.getElementById("pos-mode-paiement-id");
        if (hiddenId) {
            hiddenId.value = opt.getAttribute("data-id") || 1;
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initPaginatedTable("orders-main-table", 6);
    });
</script>

<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
