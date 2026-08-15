<?php 
$flashSuccessList = [];
if (!empty($flashSuccess)) {
    $flashSuccessList = is_array($flashSuccess) ? $flashSuccess : [$flashSuccess];
} elseif (class_exists(\App\Core\SessionManager::class) && \App\Core\SessionManager::hasFlash('success')) {
    $sMsg = \App\Core\SessionManager::getFlash('success');
    $flashSuccessList = is_array($sMsg) ? $sMsg : [$sMsg];
}

$flashErrorList = [];
if (!empty($flashError)) {
    $flashErrorList = is_array($flashError) ? $flashError : [$flashError];
} elseif (class_exists(\App\Core\SessionManager::class) && \App\Core\SessionManager::hasFlash('error')) {
    $eMsg = \App\Core\SessionManager::getFlash('error');
    $flashErrorList = is_array($eMsg) ? $eMsg : [$eMsg];
}
?>
<div class="toast-box" id="toast-box">
    <?php foreach ($flashSuccessList as $msg): ?>
        <div class="toast success" id="main-toast">
            <span>✅</span>
            <span><?= htmlspecialchars((string)$msg) ?></span>
        </div>
    <?php endforeach; ?>
    <?php foreach ($flashErrorList as $msg): ?>
        <div class="toast danger" id="main-toast">
            <span>⚠️</span>
            <span><?= htmlspecialchars((string)$msg) ?></span>
        </div>
    <?php endforeach; ?>
</div>
