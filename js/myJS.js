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

        if ([
            this.#INPUT_FIELD, this.#DATA_LIST = dataList, this.#CITY_SELECT, this.#WAREHOUSE_SELECT
        ].some((el) => { return el === null })) return;

        this.#INPUT_FIELD.addEventListener("input", (obj) => { this.#LOADED = false; this.#INPUT_EVENT(obj.target.value); });
        this.#CITY_SELECT.addEventListener("change", (obj) => {
            this.#CITY_CHANGE_EVENT(obj.target.options[obj.target.selectedIndex].text, obj.target.value)
        });
        this.#WAREHOUSE_SELECT.addEventListener("change", (obj) => { this.#WAREHOUSE_CHANGE_EVENT(obj.target.value) });

        this.#_WAREHOUSE_FINDER = new WAREHOUSE_FINDER(token, [
            "9a68df70-0267-42a8-bb5c-37f427e36ee4",
            "841339c7-591a-42e2-8233-7a0a00f0ed6f"
        ],  this.#WAREHOUSE_SELECT);

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
        if (number > 0 && number != this.#viddNumber) {
            this.#INPUT_FIELD.value = this.#CITY_SELECT.options[this.#CITY_SELECT.selectedIndex].text + ", № " + number;
        }

        this.#viddNumber = number;
    }

    #CLEAR() {
        this.#DATA_LIST.innerHTML = "";
        this.#CITY_SELECT.innerHTML = "";
        this.#WAREHOUSE_SELECT.innerHTML = "";
        this.#FINDED_ARRAY = [];
    }

    #FIND(me, text) {

        let getNumberByString = function (str) {
            let out = ""; let started = false;

            let numStart = str.indexOf("№") > -1 ? str.indexOf("№") : str.indexOf("#");

            if (numStart > -1) {
                for (let i = numStart; i < str.length; i++) {
                    if (IsNumeric(str[i])) {
                        started = true;
                        out = out.toString() + str[i];
                    } else if (started) {
                        break;
                    }
                }
            } else {
                for (let i = str.length - 1; i >= 0; i--) {
                    if (IsNumeric(str[i])) {
                        started = true;
                        out = str[i] + out.toString();
                    } else if (started) {
                        break;
                    }
                }
            }

            if (out.length < 7) {
                return out != "" ? out * 1 : 0;
            } else {
                return getNumberByString(str.replace(out, ""));
            }

        };

        let temp_num = getNumberByString(text);

        //console.clear();

        text = me.#TRANS_LITER(text.toLowerCase().trim());

        if (text.length < 3) {
            me.#CLEAR();

            me.#viddNumber = temp_num;
            return false;
        }

        text = me.#EDIT_STR(text);

        if (me.#CurrentTEXT == text && me.#viddNumber == temp_num && me.#FINDED_ARRAY.length > 0) {
            //console.log("ТЕКСТ НЕ ЗМІНИВСЯ; НОМЕР НЕ ЗМІНИВСЯ; ДАНІ ПІДГРУЖЕНО");
            return true;
        } else if (me.#CurrentTEXT == text) {
            //console.log("ТЕКСТ НЕ ЗМІНИВСЯ");

            me.#viddNumber = temp_num;

            me.#SET_VIDD(me.#CurrentCityRef, temp_num);

            return true;
        }

        //console.log("ТЕКСТ ЗМІНИВСЯ");

        me.#viddNumber = temp_num;

        //me.#CLEAR();

        me.#CurrentTEXT = text;

        me.#FINDCITIES(text);

        return true;
    }

    TEST(temp) {
        return this.#EDIT_STR(temp);
    }

    #EDIT_STR(str) {
        let CityOblToStartPos = function (txt) {
            let temp = [];
            let obl = "";
            let cit = "";
            let rjn = "";

            temp = txt.match(/(^|\s)+(м|с|смт|с.м.т|місто|село|селище)[.\s]+(\S+)/);

            if (temp != null) {
                cit = temp.length > 3 ? temp[3] : temp[0];
                txt = txt.replace(temp[0], '');
            }

            temp = txt.match(/([\S]+)[\s]+обл($|[\s]|[\S]+)/);

            if (temp != null) {
                obl = temp.length > 1 ? temp[1] : temp[0];
                txt = txt.replace(temp[0], '');
            }

            temp = txt.match(/([\S]+)[\s]+(р\-н|рай)($|[\s]|[\S]+)/);

            if (temp != null) {
                rjn = temp.length > 1 ? temp[1] : temp[0];
                txt = txt.replace(temp[0], '');
            }

            return cit + " " + obl + " " + rjn + " " + txt;
        }

        str = CityOblToStartPos(str);

        str = str.replace(/[#|№].*/gi, '').trim();
        str = str.replace(/[^А-яєїі'\s\'-]/gi, ' ').trim();
        str = str.replace(/(^|[^А-яїіє])від[^\s]+/gi, '').trim();

        let arr = str.split(" ");

        str = "";

        arr.forEach((w) => { if (w.length > 3) str += w + " "; });

        return str.trim();
    }

    #FINDCITIES(findSTR) {
        let me = this;

        //console.log("FIND STRING: [" + findSTR + "]");

        let removeWord = function (input) {
            let arr = input.split(" ");
            let temp_str = "";

            if (arr.length > 1) {

                for (let i = 0; i < (arr.length - 1); i++) {
                    temp_str += arr[i] + " ";
                }

                return temp_str.trim();
            } else {
                return input;
            }
        }

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

                //console.log("FINDED CITYES: " + me.#FINDED_ARRAY.length);

                if (me.#FINDED_ARRAY.length > 0) {

                    me.#FINDED_ARRAY.forEach((c) => {
                        me.#SET_NORM_NAME(c);
                    });

                    me.#CITY_SELECT.innerHTML = me.#GET_OPTION_SELECT();
                    me.#DATA_LIST.innerHTML = me.#GET_OPTION_DATALIST();

                    if (me.#CITY_SELECT.value != 1) {

                        if (!me.#LOADED) {
                            me.#INPUT_FIELD.value = me.#CITY_SELECT.options[me.#CITY_SELECT.selectedIndex].text +
                                ", № " + (me.#viddNumber != 0 ? me.#viddNumber : "");
                            me.#CurrentTEXT = me.#EDIT_STR(me.#INPUT_FIELD.value);
                        }

                        me.#SET_VIDD(me.#CITY_SELECT.value, me.#viddNumber);
                    }
                } else if (removeWord(findSTR) != findSTR) {
                    me.#FINDCITIES(removeWord(findSTR));
                }
            },
            async: true
        });
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
    #WAREHOUSE_SELECTOR;
    #TOKEN = "";
    #REF = "";
    #CUR_NUMBER = 0;

    constructor(token, typesArr, selector) {
        this.#TYPES = typesArr;
        this.#WAREHOUSE_SELECTOR = selector;
        this.#TOKEN = token;
    }

    GET_WAREHOUSES(CITY_REF, NUMBER = 0) {
        if (CITY_REF == undefined || CITY_REF == "") { this.#REF = ""; console.log("ERR"); return; }

        //console.log("FIND NUMBER: " + NUMBER);

        if (CITY_REF == this.#REF && NUMBER != this.#CUR_NUMBER) {
            this.#SELECT_NUMBER(NUMBER);
            return;
        }

        this.#REF = CITY_REF;
        this.#CUR_NUMBER = NUMBER;

        let out = '';
        let counter = 0;
        let APPLY = false;
        let param;
        let me = this;

        //console.log("ПОШУК ВІДДІЛУ № " + (NUMBER != 0 ? NUMBER:"-"));

        param = { "SettlementRef": CITY_REF, "TypeOfWarehouseRef": '' };

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
                    if (data.data.length > 0) APPLY = true;

                    data.data.forEach((d) => {
                        out += "<option value='" + d.Number + "'>" + d.Description + "</option>\n";
                        counter++;
                    });
                },
                async: false
            });
        });

        //console.log("FINDED WAREH: " + counter);

        if (counter > 1) {
            out = "<option disabled selected value='0' />оберіть відділення (" + counter + ")...\n" + out;
        }

        if (!APPLY && this.#CUR_NUMBER != 0) {
            this.GET_WAREHOUSES(this.#REF);
        } else {
            this.#WAREHOUSE_SELECTOR.innerHTML = out;
            this.#SELECT_NUMBER(NUMBER);
        }
    }

    #SELECT_NUMBER(number) {

        let el = $(this.#WAREHOUSE_SELECTOR);
        let finded = false;

        $.each(el[0], (ind, op) => {
            if (op.value == number) { finded = true; return; }
        });

        let setNum = function (me, isFinded) {
            if (isFinded) {
                me.#WAREHOUSE_SELECTOR.value = number;
            }
            else {
                me.#WAREHOUSE_SELECTOR.value = 0;
            }
        }

        setTimeout(setNum, 250, this, finded);
    }

}
