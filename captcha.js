// console.log('Happy developing ✨')

function cutImage(nom) {
    const row = randomInt(5) + 3;
    const col = randomInt(5) + 3;
    nom = nom[randomInt(nom.length)]['chemin'];
    const img = new Image();
    img.src = "img/captcha/" + nom.toString();
    let random = randomInt(row * col);

    const larg = 150 / col;
    const haut = 150 / row;
    let posRes;
    if(document.getElementById("testCaptcha")) document.getElementById("testCaptcha").remove();

    const imgCaptcha = document.getElementById('imgCaptcha');
    const ctx = imgCaptcha.getContext('2d');

    const testCaptcha = document.createElement('canvas');
    testCaptcha.setAttribute('id', 'testCaptcha');
    testCaptcha.setAttribute('width', larg.toString());
    testCaptcha.setAttribute('height', haut.toString());
    testCaptcha.setAttribute('style', 'position: absolute; left: 25px');
    const ctxTest = testCaptcha.getContext('2d');
    imgCaptcha.insertAdjacentElement('afterend', testCaptcha);
    ctx.reset();

    img.addEventListener('load', () => {
        for(let y = 0; y < row; y++) {
            for(let x = 0; x < col; x++) {
                if(Math.floor(y * col + x) === random) {
                    testCaptcha.setAttribute('style', 'position: absolute;  left: 25px; top: ' + ((y * haut) + 25 ).toString() + 'px;');
                    ctxTest.drawImage(img, x * (img.width / col), y * (img.height / row), img.width / col, img.height / row, 0, 0, 150 / col, 150 / row);
                    testCaptcha.innerHTML = (150 + x * (150 / col) + larg / 2).toString();
                }
                else {
                    ctx.drawImage(img, x * (img.width / col), y * (img.height / row), img.width / col, img.height / row, 150 + x * (150 / col), y * (150 / row), 150 / col, 150 / row);
                }
            }
        }
    });
    document.getElementById('posCaptcha').addEventListener('input', function(e) {
        const offset = 25 + ((450 - larg) / 1000) * parseInt(e.target.value);
        testCaptcha.style.left = offset + "px";
    });
}

function testCaptcha() {
    const res = parseInt(document.getElementById('testCaptcha').innerHTML) / 450 * 1000;
    const test = document.getElementById('posCaptcha').value;
    const target = document.getElementById('verifCaptcha');
    if(test >= res - res*0.05 && test <= res + res*0.05) {
        //Si le captcha réussit
        target.setAttribute('checked', 'checked');
        document.getElementById('buttonCaptcha').setAttribute('disabled', 'disabled');
        document.getElementById('buttonCaptcha').removeAttribute('class');
        document.getElementById('buttonCaptcha').setAttribute('class', 'btn btn-success');
    }
    else {
        //Si le captcha fail
        target.removeAttribute('checked');
    }
}

function randomInt(max) {
    return Math.floor(Math.random() * max);
}

/*function get(el) {
    if (typeof el === 'string') return document.getElementById(el);
    else return el;
}*/