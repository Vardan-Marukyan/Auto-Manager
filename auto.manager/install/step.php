<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$data = $GLOBALS['MODULE_CUSTOM_VALUES'];

if ($data['success']): ?>
    <div style="padding: 20px; font-size: 16px; color: green;">
        <p><?= htmlspecialcharsbx($data['message']); ?></p>
    </div>
<?php endif; ?>
