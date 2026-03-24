<?php $user = Auth::user(); ?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">TP</div>
        <div class="sidebar-brand">
            <strong><?= APP_NAME ?></strong>
            <small><?= APP_SUBTITLE ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-label">ÜBERSICHT</span>
            <a href="index.php?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">&#x1F4CA;</span> Dashboard
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-label">VERKAUF</span>
            <a href="index.php?page=quotes" class="nav-item <?= $currentPage === 'quotes' ? 'active' : '' ?>">
                <span class="nav-icon">&#x1F4C4;</span> Offerten
                <?php
                $qCount = Database::getInstance()->query("SELECT COUNT(*) as c FROM quotes WHERE status IN ('sent','open')")->fetch()['c'];
                if ($qCount > 0): ?>
                    <span class="nav-badge"><?= $qCount ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?page=invoices" class="nav-item <?= $currentPage === 'invoices' ? 'active' : '' ?>">
                <span class="nav-icon">&#x1F9FE;</span> Rechnungen
                <?php
                $iCount = Database::getInstance()->query("SELECT COUNT(*) as c FROM invoices WHERE status IN ('sent','open','overdue')")->fetch()['c'];
                if ($iCount > 0): ?>
                    <span class="nav-badge badge-warn"><?= $iCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-label">KONTAKTE</span>
            <a href="index.php?page=contacts" class="nav-item <?= $currentPage === 'contacts' ? 'active' : '' ?>">
                <span class="nav-icon">&#x1F464;</span> Kunden
            </a>
            <a href="index.php?page=companies" class="nav-item <?= $currentPage === 'companies' ? 'active' : '' ?>">
                <span class="nav-icon">&#x1F3E2;</span> Firmen
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-label">SYSTEM</span>
            <a href="index.php?page=settings" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                <span class="nav-icon">&#x2699;</span> Einstellungen
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= mb_substr($user['name'], 0, 2) ?></div>
            <div class="user-info">
                <strong><?= Helper::e($user['name']) ?></strong>
                <small><?= $user['role'] === 'admin' ? 'Administrator' : 'Benutzer' ?></small>
            </div>
        </div>
    </div>
</aside>
