/**
 * TP-Tool JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {

    // Aktive Navigation hervorheben
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
    document.querySelectorAll('.nav-item').forEach(function(item) {
        if (item.getAttribute('href') && item.getAttribute('href').includes('page=' + currentPage)) {
            item.classList.add('active');
        }
    });

    // Lösch-Bestätigungen
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Automatische Berechnung in Formularen
    document.querySelectorAll('.calc-row').forEach(function(row) {
        const qty = row.querySelector('.calc-qty');
        const price = row.querySelector('.calc-price');
        const total = row.querySelector('.calc-total');

        if (qty && price && total) {
            function calc() {
                total.value = (parseFloat(qty.value || 0) * parseFloat(price.value || 0)).toFixed(2);
                updateTotals();
            }
            qty.addEventListener('input', calc);
            price.addEventListener('input', calc);
        }
    });

    // Suche mit Tastenkürzel
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            var searchInput = document.querySelector('.search-input');
            if (searchInput) searchInput.focus();
        }
    });
});

/**
 * Gesamtsummen aktualisieren
 */
function updateTotals() {
    var subtotal = 0;
    document.querySelectorAll('.calc-total').forEach(function(el) {
        subtotal += parseFloat(el.value || 0);
    });

    var mwstRate = parseFloat(document.getElementById('mwst_rate')?.value || 8.1);
    var mwst = subtotal * mwstRate / 100;
    var total = subtotal + mwst;

    var subEl = document.getElementById('subtotal');
    var mwstEl = document.getElementById('mwst_amount');
    var totalEl = document.getElementById('total');

    if (subEl) subEl.textContent = formatMoney(subtotal);
    if (mwstEl) mwstEl.textContent = formatMoney(mwst);
    if (totalEl) totalEl.textContent = formatMoney(total);

    if (document.getElementById('subtotal_input')) document.getElementById('subtotal_input').value = subtotal.toFixed(2);
    if (document.getElementById('mwst_input')) document.getElementById('mwst_input').value = mwst.toFixed(2);
    if (document.getElementById('total_input')) document.getElementById('total_input').value = total.toFixed(2);
}

/**
 * CHF formatieren
 */
function formatMoney(amount) {
    return "CHF " + amount.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, "'");
}
