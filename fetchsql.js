async function fetchTable() {
    const categorie = document.getElementById('table').value;
    const target = document.getElementById('tableau');
    if(categorie !== '') {
        const response = await fetch('fetchdata.php?table=' + categorie);
        const data = await response.text();
        target.innerHTML = data;
    } else {
        target.innerHTML = '';
    }
}
