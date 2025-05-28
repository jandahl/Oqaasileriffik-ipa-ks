const process = require('process');
const fs = require('fs');

process.chdir(__dirname);

let $ = function(){};
let window = {};

let js = fs.readFileSync(__dirname + '/../js/hyphenation.js', 'utf-8') + '';
js = js.replace(/['"]use strict['"](;?)/g, '');
eval(js);

let tests = fs.readFileSync('input-hyphenation.txt', 'utf-8') + '';
tests = tests.split('\n');

let good = true;
for (let i=0 ; i<tests.length ; ++i) {
    let h = tests[i];
    let r = tests[i].replace(/-/g, '');
    let rv = kal_hyphenate(r).replace(/\u00ad/g, '-');
    if (rv !== h) {
		good = false;
        console.log(`ERROR: ${r} returned ${rv}, but should be ${h}`);
    }
}

if (good) {
	console.log('SUCCESS');
}
