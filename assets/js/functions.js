async function bonusRacial() {
    const race_id = document.getElementById('race').value;
    const response = await fetch('api/bonusRacial.php?race=' + race_id);
    const bonus_racial = await response.json();
    const carac = ['FOR','DEX','CON','INT','SAG','CHA'];
    for (const car of carac) {
        document.getElementById('bns_' + car).value = '0';
        document.getElementById('fnl_' + car).value = Math.floor((parseInt(document.getElementById('val_' + car).value) + parseInt(document.getElementById('bns_' + car).value)) / 2 - 5);
    }
    for (let i = 0; i < bonus_racial.length; i++) {
        const bonus = bonus_racial[i];
        document.getElementById('bns_' + `${bonus.sigle}`).value = `${bonus.valeur}`;
        document.getElementById('fnl_' + `${bonus.sigle}`).value = Math.floor((parseInt(document.getElementById('val_' + `${bonus.sigle}`).value) + parseInt(document.getElementById('bns_' + `${bonus.sigle}`).value))/ 2 - 5);
    }
    updateValues();
}

async function classedata() {
    const class_id = document.getElementById('classe').value;
    const response = await fetch('api/dedevie.php?classe=' + class_id);
    const classe = await response.json();
    document.getElementById('ddv').value = `${classe.dedevie}`;
    if (`${classe.caracmagie}` === 'null') document.getElementById('caracmagie').value = 'Aucune';
    else document.getElementById('caracmagie').value = `${classe.caracmagie}`;
    updateValues();
}

localStorage.setItem("nb", "1");
async function addItemList(type,systeme){
    const target = document.getElementById('add' + type);
    let nb = localStorage.getItem("nb");
    localStorage.setItem("nb", (parseInt(nb) + 1).toString());

    const response = await fetch('api/fetchItems.php?type=' + type + '&systeme=' + systeme + "&nb=" + nb);
    let body = await response.text();

    target.insertAdjacentHTML('beforebegin', body);

    let id = document.getElementById(type + nb);
    id.addEventListener('change', async function() {
        const response2 = await fetch('api/fetchItem.php?type=' + type + '&systeme=' + systeme + "&id=" + id.value);
        let result = await response2.json();

        if(type === "arme") document.getElementById("DM" + nb).value = `${result.degat}`;
        if(type === "armure") document.getElementById("DEF" + nb).value = `${result.defense}`;
    })
    updateValues();
}

function updateValues() {
    const contact = document.getElementById('contact');
    const distance = document.getElementById('distance');
    const magique = document.getElementById('magique');
    const caracmagie = document.getElementById('caracmagie');
    const PV = document.getElementById('pv');
    const DEF = document.getElementById('def');
    const init = document.getElementById('init');
    const FOR = document.getElementById('fnl_FOR').value;
    const DEX = document.getElementById('fnl_DEX').value;
    const CON = document.getElementById('fnl_CON').value;
    const INT = document.getElementById('fnl_INT').value;
    const SAG = document.getElementById('fnl_SAG').value;
    const CHA = document.getElementById('fnl_CHA').value;

    contact.value = parseInt(FOR);
    distance.value = parseInt(DEX);
    if(caracmagie.value === 'Aucune' || !caracmagie.value) magique.value = '0';
    else {
        let carmagie = 'fnl_' + caracmagie.value;
        magique.value = document.getElementById(carmagie).value;
    }
    PV.value = parseInt(document.getElementById('ddv').value.slice(2)) + parseInt(CON);
    let armures = document.getElementsByClassName('def');
    let temp = 10;
    temp += parseInt(DEX);
    for (let i = 0; i < armures.length; i++) {
        temp += parseInt(armures[i].value);
    }
    DEF.value = temp;
    init.value = 10 + parseInt(DEX) + parseInt(SAG);
    setTimeout(updateValues, 3000);
}