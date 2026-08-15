<?php
$currentNav = $activeNav ?? 'pos';
$userName = $currentUser ? $currentUser->getNomComplet() : 'Admin Boutique';
?>
<div class="navbar">
    <div class="nav-logo">
        <span>📦</span> StoreManager Pro
    </div>
    <div class="nav-menu">
        <a href="/dashboard" class="nav-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">Tableau de Bord</a>
        <a href="/pos" class="nav-item <?= $currentNav === 'pos' ? 'active' : '' ?>" id="nav-pos">Ventes / POS</a>
        <a href="/dettes" class="nav-item <?= $currentNav === 'dettes' ? 'active' : '' ?>" id="nav-dettes">Gestion Dettes</a>
        <a href="/supplies" class="nav-item <?= $currentNav === 'supplies' ? 'active' : '' ?>" id="nav-supplies">Approvisionnements</a>
        <a href="/catalog" class="nav-item <?= $currentNav === 'catalog' ? 'active' : '' ?>" id="nav-catalog">Produits & Tiers</a>
    </div>
    
    <div style="margin-left: auto; display: flex; align-items: center; gap: 14px;">
        <div style="text-align: right;">
            <div id="current-user-role" style="font-size: 12px; font-weight: 800; color: var(--accent);"><?= htmlspecialchars($userName) ?></div>
            <div style="font-size: 10px; color: var(--text-muted);"><?= date('d/m/Y H:i') ?></div>
        </div>
        <a href="/pos" class="btn-quick-action" style="border-color: var(--danger); color: var(--danger); background: rgba(248, 113, 113, 0.08); padding: 8px 12px;">Caisse 🛒</a>
    </div>
</div>
