let timer;
const searchEl = document.getElementById('searchInput');
const statusFilter = searchEl.dataset.status || '';
const tbodyId = searchEl.dataset.tbody || 'userTableBody';
searchEl.addEventListener('input', function () {
    clearTimeout(timer);
    const recherche = this.value.trim();
    if (recherche === '') { window.location.reload(); return; }
    timer = setTimeout(() => {
        fetch('../api/recherche_list.php?q=' + encodeURIComponent(recherche) + (statusFilter ? '&status=' + statusFilter : ''))
            .then(reponse => reponse.json())
            .then(utilisateurs => {
                const body = document.getElementById(tbodyId);
                body.innerHTML = '';
                utilisateurs.forEach(utilisateur => {
                    const couleur = utilisateur.status == 1 ? '#0682ef' : utilisateur.status == 2 ? '#bc2626' : '#26bc26';
                    const role = utilisateur.status == 1 ? 'Admin' : utilisateur.status == 2 ? 'Banni' : 'User';
                    const av = utilisateur.avatar ? '../assets/img/avatars/' + utilisateur.avatar : '../assets/img/usrplaceholder.png';
                    const ligne = document.createElement('tr');
                    ligne.innerHTML = `
                        <td><img src="${av}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover"></td>
                        <td><strong>${utilisateur.login}</strong></td>
                        <td>${utilisateur.prenom}</td>
                        <td>${utilisateur.nom}</td>
                        <td>${utilisateur.email}</td>
                        <td><span style="background-color:${couleur};padding:2px 6px;border-radius:4px;color:#fff">${role}</span></td>
                        <td><a href="edituser.php?id=${parseInt(utilisateur.id)}" class="btn btn-sm btn-outline-warning">Configurer</a></td>
                    `;
                    body.appendChild(ligne);
                });
            })
            .catch(erreur => console.error(erreur));
    }, 300);
});
