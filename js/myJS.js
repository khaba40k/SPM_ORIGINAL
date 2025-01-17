class SerialFormVars {

    constructor(_in) {

        var me = this;

        me.NAME = [];
        me.VAL = [];

        _in.forEach(function (val, ind) {
            if (val.value.trim() !== "") {
                me.NAME[ind] = val.name;
                let n = val.value * 1;
                me.VAL[ind] = Number.isNaN(n) ? val.value : n * 1;
            }
        }, me);

        if (me.NAME.indexOf("type_ID") === -1) {
            me.NAME.push("type_ID");
            me.VAL.push(1);
        }

        if (me.NAME.indexOf("count") === -1) {
            me.NAME.push("count");
            me.VAL.push(1);
        }
        else {
            var issp = me.NAME.indexOf('is_spis');

            if (issp != -1) {
                this.SET('count', this.GET('count') * -1);
            }
        }

        if (me.NAME.indexOf("costs") === -1) {
            me.NAME.push("costs");
            me.VAL.push(0);
        }
    }

    GET(name, hideNULL = false) {
        this.ANS = 'NULL';

        this.NAME.forEach(function (val, ind) {
            if (val == name) {
                this.ANS = this.VAL[ind];
            }
        }, this);

        if (hideNULL) {
            if (this.ANS == 'NULL') this.ANS = '';
        }

        return this.ANS;
    }

    SET(name, value) {
        if (this.NAME.indexOf(name) === -1) {
            this.NAME.push(name);
            this.VAL.push(value);
        } else {
            this.NAME.forEach(function (val, ind) {
                if (name == val) {
                    this.VAL[ind] = value;
                }
            }, this);
        }
    }

    ToArray(_append = []) {
        var OUT = {
            date_in: this.GET('date_in'),
            service_ID: this.GET('service_ID'),
            type_ID: this.GET('type_ID'),
            count: this.GET('count'),
            color: this.GET('color'),
            costs: this.GET('costs'),
            comm: this.GET('comm')
        };

        for (var key in _append) {
            OUT[key] = _append[key];
        }

        return OUT;
    }
}

function IsNumeric(val) {
    return Number(parseFloat(val)) == val;
}

class HTEL {
    static FORMAT(inEl, level = 0) {

        //inEl = inEl.replace(/[=]["](.*?)["]/g, "='$1'");

        //if (level == 5) {
        //    console.log(inEl);
        //}

        let _out = "";

        const _tabLen = 2;

        _out = "<div>" +  inEl + "</div>";

        let doc = new DOMParser().parseFromString(_out, "text/xml");

        _out = "";

        let _tab = "\n".padEnd((level * _tabLen) + 1, "\t");

        doc.firstChild.childNodes.forEach(function (node) {
            if (node.nodeType != 3) {
                if (node.children.length < 1) {
                    _out += _tab + node.outerHTML;
                } else {
                    let _child = HTEL.FORMAT(node.innerHTML, level + 1);;

                    let _temp = node;

                    _temp.innerHTML = _child;

                    _out += _tab + _temp.outerHTML;
                }

            } else {
                _out += _tab + node.textContent;
            }

            _out = _out.replace(/>([^<|^\n])/g, ">" + _tab.padEnd(_tab.length + _tabLen, "\t") + "$1");
            _out = _out.replace(/[^>][\n]([^\t])/g, _tab.padEnd(_tab.length + _tabLen, "\t") + "$1");
        });

        _out = _out.replace(/([^\t|^\n])<\//g, "$1" + _tab + "</");

        return _out;
    }

}

class NovaPay {

    #TOKEN = '';
    #FINDED_ARRAY = [];
    #lat_arr = [
    "q",
    "w",
    "e",
    "r",
    "t",
    "y",
    "u",
    "i",
    "o",
    "p",
    "\\[",
    "\\]",
    "a",
    "s",
    "d",
    "f",
    "g",
    "h",
    "j",
    "k",
    "l",
    "\\;",
    "z",
    "x",
    "c",
    "v",
    "b",
    "n",
    "m"
];
    #cir_arr = [
    "й",
    "ц",
    "у",
    "к",
    "е",
    "н",
    "г",
    "ш",
    "щ",
    "з",
    "х",
    "ї",
    "ф",
    "і",
    "в",
    "а",
    "п",
    "р",
    "о",
    "л",
    "д",
    "ж",
    "я",
    "ч",
    "с",
    "м",
    "и",
    "т",
    "ь"
    ];
    #CurrentTEXT = "";
    #CurrentCityRef = "";
    #viddNumber = 0;
    #INPUT_FIELD = null;
    #DATA_LIST = null;
    #CITY_SELECT = null;
    #_WAREHOUSE_FINDER = null;
    #WAREHOUSE_SELECT = null;
    #TIMEOUT = null;
    #LOADED = false;

    constructor(token, inputEl = null, dataList = null, citySelect = null, warehouseSelect = null, loaded = false) {
        this.#TOKEN = token;
        this.#LOADED = loaded;

        this.#INPUT_FIELD = inputEl;
        this.#DATA_LIST = dataList;
        this.#CITY_SELECT = citySelect;
        this.#WAREHOUSE_SELECT = warehouseSelect;

        this.#INPUT_FIELD.addEventListener("input", (obj) => { this.#LOADED = false; this.#INPUT_EVENT(obj.target.value); });
        this.#CITY_SELECT.addEventListener("change", (obj) => {
            this.#CITY_CHANGE_EVENT(obj.target.options[obj.target.selectedIndex].text, obj.target.value)
        });
        this.#WAREHOUSE_SELECT.addEventListener("change", (obj) => { this.#WAREHOUSE_CHANGE_EVENT(obj.target.value) });

        this.#_WAREHOUSE_FINDER = new WAREHOUSE_FINDER(token, [
            "9a68df70-0267-42a8-bb5c-37f427e36ee4",
            "841339c7-591a-42e2-8233-7a0a00f0ed6f"
        ], (html, number) => { this.#viddNumber = number, this.#WAREHOUSE_SELECT.innerHTML = html; this.#WAREHOUSE_CHANGE_EVENT(number) });

        if (loaded) {
            this.#FIND(this, this.#INPUT_FIELD.value);
        }
    }

    #INPUT_EVENT(text) {
        clearTimeout(this.#TIMEOUT);

        this.#TIMEOUT = setTimeout(this.#FIND, 300, this, text);
    }

    #CITY_CHANGE_EVENT(city, ref) {

        this.#SET_VIDD(ref, this.#viddNumber);

        this.#INPUT_FIELD.value = city + ", № " + (this.#viddNumber != 0 ? this.#viddNumber : "");

        this.#CurrentCityRef = ref;
    }

    #WAREHOUSE_CHANGE_EVENT(number) {
        if (number > 0) {
            this.#viddNumber = number;
            this.#INPUT_FIELD.value = this.#CITY_SELECT.options[this.#CITY_SELECT.selectedIndex].text + ", № " + this.#viddNumber;
        }
    }

    #FIND(me, text) {
        let temp_num = me.#getNumberByString(text);

        //if (temp_num != me.#viddNumber) {
        //    console.log("НОМЕР ЗМІНЕНО [" + me.#viddNumber + " => " + temp_num + "] CityRef: " + me.#CurrentCityRef);
        //}

        text = me.#TRANS_LITER(text.toLowerCase().trim());

        if (text.length < 3) {
            me.#DATA_LIST.innerHTML = "";
            me.#CITY_SELECT.innerHTML = "";
            me.#WAREHOUSE_SELECT.innerHTML = "";

            me.#viddNumber = temp_num;
            me.#FINDED_ARRAY = [];
            return false;
        }

        let editStr = function (str) {
            str = str.replace(/[#|№].*/gi, '').trim();
            str = str.replace(/[^А-яїі\s\'-]/gi, '').trim();

            let arr = str.split(" ");

            str = "";

            arr.forEach((w) => { if (w.length > 3) str += w + " "; });

            return str.trim();
        }

        text = editStr(text);

        if (me.#CurrentTEXT == text && me.#viddNumber == temp_num) {
            return;
        } else if (me.#CurrentTEXT == text) {
            me.#viddNumber = temp_num;
            me.#SET_VIDD(me.#CurrentCityRef, temp_num);
            return;
        }

        me.#viddNumber = temp_num;

        me.#DATA_LIST.innerHTML = "";
        me.#CITY_SELECT.innerHTML = "";
        me.#WAREHOUSE_SELECT.innerHTML = "";

        me.#CurrentTEXT = text;

        me.#FINDCITIES(text);
    }

    #FINDCITIES(findSTR) {
        let me = this;

        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'https://api.novaposhta.ua/v2.0/json/',
            data: JSON.stringify({
                modelName: 'AddressGeneral',
                calledMethod: "searchSettlements",
                methodProperties: {
                    "CityName": findSTR,
                    "Limit": 30,
                    "Page": 1
                },
                apiKey: this.#TOKEN
            }),
            headers: {
                'Content-Type': 'application/json'
            },
            success: function (texts) {
                me.#FINDED_ARRAY = me.#ExcludeEmptyWarehouse(texts.data[0].Addresses);
                if (me.#FINDED_ARRAY.length > 0) {

                    me.#FINDED_ARRAY.forEach((c) => {
                        me.#SET_NORM_NAME(c);
                    });

                    me.#DATA_LIST.innerHTML = me.#GET_OPTION_DATALIST();
                    me.#CITY_SELECT.innerHTML = me.#GET_OPTION_SELECT();

                    if (me.#CITY_SELECT.value != 1) {

                        me.#SET_VIDD(me.#CITY_SELECT.value, me.#viddNumber);

                        if (!me.#LOADED) {
                            me.#INPUT_FIELD.value = me.#CITY_SELECT.options[me.#CITY_SELECT.selectedIndex].text +
                                ", № " + (me.#viddNumber != 0 ? me.#viddNumber : "");
                        }
                    }
                }
            },
            async: true
        }, me);
    }

    #ExcludeEmptyWarehouse(inpArr) {
        inpArr.forEach((adr, ind) => {
            if (adr.Warehouses * 1 == 0) {
                inpArr.splice(ind, 1);
            } 
        });
        return inpArr;
    }

    #GET_OPTION_DATALIST() {
        return this.#SHOW_OPTON_LIST(this.#CurrentTEXT, false);
    }

    #GET_OPTION_SELECT() {
        return this.#SHOW_OPTON_LIST(this.#CurrentTEXT, true);
    }

    #SET_VIDD(_CityRef, number = 0) {

        this.#_WAREHOUSE_FINDER.GET_WAREHOUSES(_CityRef, number);

    }

    #getNumberByString(text) {
        let out = ""; let started = false;

        for (let i = text.length - 1; i >= 0; i--) {
            if (IsNumeric(text[i])) {
                started = true;
                out = text[i] + out.toString();
            } else if (started) {
                break;
            }
        }

        return out != "" ? out * 1: 0;
    }

    #TRANS_LITER(word = '') {
        let _out = word.toString();

        let i = 0;
        let temp = ``;

        for (i = 0; i < this.#lat_arr.length; i++) {
            temp = new RegExp(`${this.#lat_arr[i]}`, "gi");
            _out = _out.replace(temp, this.#cir_arr[i]);
        }

        return _out;
    }

    #SHOW_OPTON_LIST(txt, frSelect) {
        let OUT = '';

        if (frSelect) {
            let isSelected = '';

            let full_compliance = false;

            let firstWord = function (input) {
                let arr = input.replace(/[^А-яїі\-\s]+/gi, '').split(" ");
                let OUT = input;
                arr.some((w) => {
                    if (w.length > 3) {
                        OUT = w;
                        return true;
                    } 
                }, OUT);

                return OUT.split(" ", 1)[0];
            };

            let findLetter = (this.#LOADED || this.#viddNumber != 0) ? firstWord(txt) : txt;

            this.#FINDED_ARRAY.forEach((c) => {
                isSelected = '';

                if (this.#LOADED || this.#viddNumber != 0) {
                    
                    if (!full_compliance && (c.MainDescription.toLowerCase() + " ").includes(findLetter + " ")) {
                        isSelected = 'selected';
                        full_compliance = true;
                    }
                } else {
                    if (!full_compliance && findLetter.includes(c.Present.toLowerCase())) {
                        isSelected = 'selected';
                        full_compliance = true;
                    }
                }

                if (isSelected != '') this.#CurrentCityRef = c.Ref;

                OUT += "<option value='" + c.Ref + "' " + isSelected + ">" + c.Present + "</option>\n";
            });

            if (!full_compliance && this.#FINDED_ARRAY.length > 1) {
                OUT = "<option disabled selected value='1'>оберіть населений пункт...</option>\n" + OUT;
            }
        }
        else {
            this.#FINDED_ARRAY.forEach((c) => {
                OUT += "<option value=\"" + c.Present + "\" />\n";
            });
        }

        return OUT;
    }

    #SET_NORM_NAME(inp) {
        let name = inp.SettlementTypeCode + " " + inp.MainDescription.replace(/\s\((.+?)\)/g, "");//.replace("'", "")

        if (inp.Region != "") {
            name += " (" + inp.Area + " " + inp.ParentRegionCode + ", " + inp.Region + " " + inp.RegionTypesCode + ")";
        } else {
            name += " (" + inp.Area + " " + inp.ParentRegionCode + ")";
        }

        inp.Present = name;
    }

}

class WAREHOUSE_FINDER {
    #TYPES = [];
    #CALLBACK;
    #BUFFER = [];
    #TOKEN = "";

    constructor(token, typesArr, callback) {
        this.#TYPES = typesArr;
        this.#CALLBACK = callback;
        this.#TOKEN = token;
    }

    GET_WAREHOUSES(CITY_REF, NUMBER = 0) {
        if (CITY_REF == undefined || CITY_REF == "") { me.#CALLBACK("", 0); return; }
        let out = '';
        let counter = 0;
        let MoreThenOne = false;
        let param;
        let me = this;

        if (NUMBER != 0) {
            param = {
                "SettlementRef": CITY_REF,
                "TypeOfWarehouseRef": '',
                "WarehouseId": NUMBER
            };
        } else {
            param = { "SettlementRef": CITY_REF, "TypeOfWarehouseRef": '' };
        }

        this.#TYPES.forEach((type) => {

            param.TypeOfWarehouseRef = type;

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: 'https://api.novaposhta.ua/v2.0/json/',
                data: JSON.stringify({
                    modelName: 'AddressGeneral',
                    calledMethod: 'getWarehouses',
                    methodProperties: param,
                    apiKey: me.#TOKEN
                }),
                headers: {
                    'Content-Type': 'application/json'
                },
                success: function (data) {
                    //console.log("FIND: " + type);

                    if (data.data.length == 0) return;

                    data.data.forEach((d) => {
                        out += "<option value='" + d.Number + "'>" + d.Description + "</option>\n";
                        counter++;
                    });

                    console.log(counter);

                    if (!MoreThenOne && counter != 1) {
                        MoreThenOne = true;
                        out = "<option disabled selected />оберіть відділення (" + counter + ")...\n" + out;
                    }

                    me.#BUFFER.push(counter);

                    me.#CHECK_BUFFER(out, NUMBER);
                },
                async: false
            }, me);
        });
    }

    #CHECK_BUFFER(ANS, N) {
        if (this.#BUFFER.length == this.#TYPES.length) {
            if (N != 0 && this.#BUFFER.every((b) => { return b == 0; })) {
                this.#BUFFER = [];
                this.GET_WAREHOUSES(CITY_REF);
            } else {
                this.#CALLBACK(ANS, N);
            }

            this.#BUFFER = [];
        }
    }
}