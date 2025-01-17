var jsdom = require("jsdom");
const { JSDOM } = jsdom;
const { window } = new JSDOM();
const { document } = new JSDOM("").window;
global.document = document;

var $ = require("jquery")(window);

// test
console.log("$.get:", typeof $.get);
console.log("$.ajax:", typeof $.ajax);
$.get("http://localhost:3000", data => {
    console.log(data);
});

module.exports = $;