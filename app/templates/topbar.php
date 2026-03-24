<header class="topbar">
    <div class="topbar-left">
        <h1 class="page-title"><?= Helper::e($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar-right">
        <div class="search-box">
            <input type="text" placeholder="Suchen... (&#x2318;K)" class="search-input">
        </div>
        <a href="index.php?page=logout" class="btn btn-sm btn-outline" title="Abmelden">Abmelden</a>
        <a href="index.php?page=invoices&action=create" class="btn btn-sm btn-primary">+ Neu</a>
    </div>
</header>
