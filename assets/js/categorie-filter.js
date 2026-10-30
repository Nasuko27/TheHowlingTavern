let timer;

function fetchTable() {
    const input = document.getElementById('value');
    const corps = document.getElementById('refTableBody');
    if (!input || !corps) return;

    clearTimeout(timer);
    const saisie = input.value.trim();
    const endpoint = input.dataset.endpoint || '../api/filtre-categorie.php';

    timer = setTimeout(() => {
        fetch(endpoint + '?q=' + encodeURIComponent(saisie))
            .then(reponse => reponse.text())
            .then(html => {
                corps.innerHTML = html;
            })
            .catch(erreur => console.error(erreur));
    }, 300);
}

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('value');
    if (input) {
        input.addEventListener('input', fetchTable);
    }
});
