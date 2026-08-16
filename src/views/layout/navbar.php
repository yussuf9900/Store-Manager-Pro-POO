<?php
$currentNav = $activeNav ?? 'pos';

$authManager = new \App\Service\AuthManager();
$user = $currentUser ?? $authManager->getCurrentUser();

$userName = ($user && method_exists($user, 'getNomComplet')) ? $user->getNomComplet() : 'Admin Boutique';
$userRoleCode = ($user && $user->getRole()) ? strtoupper($user->getRole()->getCode()) : 'ADMIN';
$userRoleLibelle = ($user && $user->getRole()) ? $user->getRole()->getLibelle() : 'Admin Boutique';

$canDashboard = ($userRoleCode === 'ADMIN');
$canPOS = in_array($userRoleCode, ['ADMIN', 'VENTE']);
$canDettes = in_array($userRoleCode, ['ADMIN', 'VENTE']);
$canSupplies = in_array($userRoleCode, ['ADMIN', 'STOCK']);
$canCatalog = in_array($userRoleCode, ['ADMIN', 'STOCK', 'INVENTAIRE']);
?>
<div class="navbar">
    <div class="nav-logo">
        <span>📦</span> StoreManager Pro
    </div>
    <div class="nav-menu">
        <?php if ($canDashboard): ?>
            <a href="/dashboard" class="nav-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>" id="nav-dashboard">Tableau de Bord</a>
        <?php endif; ?>
        <?php if ($canPOS): ?>
            <a href="/pos" class="nav-item <?= $currentNav === 'pos' ? 'active' : '' ?>" id="nav-pos">Ventes / POS</a>
        <?php endif; ?>
        <?php if ($canDettes): ?>
            <a href="/dettes" class="nav-item <?= $currentNav === 'dettes' ? 'active' : '' ?>" id="nav-dettes">Gestion Dettes</a>
        <?php endif; ?>
        <?php if ($canSupplies): ?>
            <a href="/supplies" class="nav-item <?= $currentNav === 'supplies' ? 'active' : '' ?>" id="nav-supplies">Approvisionnements</a>
        <?php endif; ?>
        <?php if ($canCatalog): ?>
            <a href="/catalog" class="nav-item <?= $currentNav === 'catalog' ? 'active' : '' ?>" id="nav-catalog">Produits & Tiers</a>
        <?php endif; ?>
    </div>
    
    <div style="margin-left: auto; display: flex; align-items: center; gap: 14px;">
        <div style="text-align: right;">
            <div id="current-user-role" style="font-size: 12px; font-weight: 800; color: var(--accent);"><?= htmlspecialchars($userRoleLibelle ?: $userName) ?></div>
            <div style="font-size: 10px; color: var(--text-muted);">Session active</div>
        </div>
        <a href="/logout" class="btn-quick-action" style="border-color: var(--danger); color: var(--danger); background: rgba(248, 113, 113, 0.08); padding: 8px 12px; text-decoration: none;">Déconnexion 🚪</a>
    </div>
</div>
