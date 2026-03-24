<?php
$pageTitle = 'Dashboard';
$db = Database::getInstance();
$user = Auth::user();

// Statistiken berechnen
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));

// Offene Rechnungen (Summe)
$openInvoices = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM invoices WHERE status IN ('sent', 'open', 'overdue')")->fetch()['total'];

// Umsatz aktueller Monat
$monthRevenue = $db->query("SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE strftime('%Y-%m', p.payment_date) = '{$currentMonth}'")->fetch()['total'];

// Umsatz Vormonat
$lastMonthRevenue = $db->query("SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p JOIN invoices i ON p.invoice_id = i.id WHERE strftime('%Y-%m', p.payment_date) = '{$lastMonth}'")->fetch()['total'];

// Offene Offerten
$openQuotes = $db->query("SELECT COUNT(*) as count FROM quotes WHERE status IN ('sent', 'open')")->fetch()['count'];

// Überfällige Rechnungen
$overdueInvoices = $db->query("SELECT COUNT(*) as count FROM invoices WHERE status = 'overdue' OR (status IN ('sent', 'open') AND due_date < date('now'))")->fetch()['count'];
$overdueAmount = $db->query("SELECT COALESCE(SUM(total), 0) as total FROM invoices WHERE status = 'overdue' OR (status IN ('sent', 'open') AND due_date < date('now'))")->fetch()['total'];

// Letzte Rechnungen
$recentInvoices = $db->query("SELECT i.*, c.name as company_name FROM invoices i LEFT JOIN companies c ON i.company_id = c.id ORDER BY i.created_at DESC LIMIT 5")->fetchAll();

// Letzte Aktivitäten
$recentActivity = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Umsatzdaten für Chart (letzte 12 Monate)
$chartData = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $label = date('M', strtotime("-{$i} months"));
    $revenue = $db->query("SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p WHERE strftime('%Y-%m', p.payment_date) = '{$m}'")->fetch()['total'];
    $chartData[] = ['label' => $label, 'value' => $revenue];
}

// Monatsnamen auf Deutsch
$monthNames = ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mär', 'Apr' => 'Apr', 'May' => 'Mai', 'Jun' => 'Jun',
               'Jul' => 'Jul', 'Aug' => 'Aug', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Dez'];

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<main class="main-content">
    <?php include BASE_PATH . '/templates/topbar.php'; ?>

    <div class="content">
        <!-- Begrüssung -->
        <div class="greeting">
            <h2>Guten Tag, <?= Helper::e(explode(' ', $user['name'])[0]) ?></h2>
            <p>Hier ist die Übersicht für heute, <?= strftime('%A, %d. %B %Y', time()) ?: date('l, d. F Y') ?></p>
        </div>

        <!-- Statistik-Karten -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Offene Rechnungen</span>
                    <span class="stat-icon stat-icon-blue">&#x1F4C3;</span>
                </div>
                <div class="stat-value"><?= Helper::money($openInvoices) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Umsatz <?= date('F') ?></span>
                    <span class="stat-icon stat-icon-yellow">&#x1F4B0;</span>
                </div>
                <div class="stat-value"><?= Helper::money($monthRevenue) ?></div>
                <?php if ($lastMonthRevenue > 0):
                    $change = round(($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100);
                ?>
                    <div class="stat-change <?= $change >= 0 ? 'positive' : 'negative' ?>">
                        <?= $change >= 0 ? '↑' : '↓' ?> <?= abs($change) ?>% vs. Vormonat
                    </div>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Offene Offerten</span>
                    <span class="stat-icon stat-icon-green">&#x1F4E8;</span>
                </div>
                <div class="stat-value"><?= $openQuotes ?></div>
            </div>

            <div class="stat-card <?= $overdueInvoices > 0 ? 'stat-card-warning' : '' ?>">
                <div class="stat-header">
                    <span class="stat-label">Überfällige</span>
                    <span class="stat-icon stat-icon-red">&#x26A0;</span>
                </div>
                <div class="stat-value"><?= $overdueInvoices ?></div>
                <?php if ($overdueAmount > 0): ?>
                    <div class="stat-change negative"><?= Helper::money($overdueAmount) ?> ausstehend</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Haupt-Content -->
        <div class="dashboard-grid">
            <!-- Umsatz-Chart -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3>Umsatzentwicklung <?= date('Y') ?></h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <?php
                        $maxVal = max(array_column($chartData, 'value'));
                        if ($maxVal == 0) $maxVal = 1;
                        foreach ($chartData as $bar):
                            $height = ($bar['value'] / $maxVal) * 100;
                            $labelDe = $monthNames[$bar['label']] ?? $bar['label'];
                        ?>
                            <div class="chart-bar-group">
                                <div class="chart-bar" style="height: <?= max($height, 2) ?>%"
                                     title="<?= Helper::money($bar['value']) ?>"></div>
                                <span class="chart-label"><?= $labelDe ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Schnellaktionen -->
            <div class="card">
                <div class="card-header">
                    <h3>Schnellaktionen</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="index.php?page=quotes&action=create" class="quick-action">
                            <span class="qa-icon">&#x1F4C4;</span>
                            <span>Neue Offerte erstellen</span>
                        </a>
                        <a href="index.php?page=invoices&action=create" class="quick-action">
                            <span class="qa-icon">&#x1F9FE;</span>
                            <span>Neue Rechnung erstellen</span>
                        </a>
                        <a href="index.php?page=contacts&action=create" class="quick-action">
                            <span class="qa-icon">&#x1F464;</span>
                            <span>Neuer Kunde erfassen</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unterer Bereich -->
        <div class="dashboard-grid">
            <!-- Letzte Rechnungen -->
            <div class="card">
                <div class="card-header">
                    <h3>Letzte Rechnungen</h3>
                    <a href="index.php?page=invoices" class="card-link">Alle anzeigen →</a>
                </div>
                <div class="card-body no-padding">
                    <?php if (empty($recentInvoices)): ?>
                        <div class="empty-state">
                            <p>Noch keine Rechnungen vorhanden.</p>
                            <a href="index.php?page=invoices&action=create" class="btn btn-sm btn-primary">Erste Rechnung erstellen</a>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <tbody>
                                <?php foreach ($recentInvoices as $inv): ?>
                                <tr>
                                    <td class="text-muted"><?= Helper::e($inv['number']) ?></td>
                                    <td><strong><?= Helper::e($inv['title']) ?></strong></td>
                                    <td><?= Helper::e($inv['company_name'] ?? '-') ?></td>
                                    <td class="text-right"><?= Helper::money($inv['total']) ?></td>
                                    <td><?= Helper::statusBadge($inv['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Letzte Aktivitäten -->
            <div class="card">
                <div class="card-header">
                    <h3>Letzte Aktivitäten</h3>
                    <a href="#" class="card-link">Alle anzeigen →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <div class="empty-state">
                            <p>Noch keine Aktivitäten.</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $act): ?>
                            <div class="activity-item">
                                <div class="activity-dot activity-dot-<?= $act['type'] ?>"></div>
                                <div class="activity-content">
                                    <p><?= Helper::e($act['message']) ?></p>
                                    <small class="text-muted"><?= Helper::date($act['created_at']) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include BASE_PATH . '/templates/footer.php'; ?>
