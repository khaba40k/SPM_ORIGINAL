const readline = require('readline').createInterface({
    input: process.stdin,
    output: process.stdout
});

readline.question('Вхідні дані: ', name => {
    console.log(OUT(name));
    readline.close();
});

function OUT(text) {
    return "ТИ НАПИСАВ: " +  text;
}

