//纯对象的属性个数
function propLen(obj) {
    var count = 0;
    for (var i in obj) {
        count++;
    }
    return count;
}

//设置cookie，expire为多少秒后到期
function setCookie(name, value, expire) {
    var exp = new Date();
    exp.setTime(exp.getTime() + expire * 1000);
    document.cookie = name + "=" + escape(value) + ";expires=" + exp.toGMTString();
}
//获取cookie
function getCookie(name) {
    var arr = document.cookie.match(new RegExp("(^| )" + name + "=([^;]*)(;|$)"));
    if (arr != null)
        return unescape(arr[2]);
    return null;
}
//删除cookie
function delCookie(name) {
    var exp = new Date();
    exp.setTime(exp.getTime() - 1);
    var cval = getCookie(name);
    if (cval != null)
        document.cookie = name + "=" + cval + ";expires=" + exp.toGMTString();
}

//格式化服务器时间
function getTS(dateStr) {
    return Math.floor(new Date(dateStr.replace(/[\-\u4e00-\u9fa5]/g, "/")).getTime() / 1000);
}

function showTime() {
    var d = new Date();
    var str = d.getFullYear() + '-' + padLeft(d.getMonth() + 1) + '-' + padLeft(d.getDate().toString()) + ' ' + padLeft(d.getHours().toString()) + ':' + padLeft(d.getMinutes().toString()) + ':' + padLeft(d.getSeconds().toString());
    document.getElementById("nowTime").innerHTML = str; //ff不支持innerText
}

String.prototype.trim = function() {
    return this.replace(/(^\s*)|(\s*$)/g, '');
}

function rtrim(str, charlist) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Erkekjetter
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   input by: rem
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: rtrim('    Kevin van Zonneveld    ');
    // *     returns 1: '    Kevin van Zonneveld'
    charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
    var re = new RegExp('[' + charlist + ']+$', 'g');
    return (str + '').replace(re, '');
}

function checkAll(obj) {
    //1.83
    if ($(document).prop) {
        $(":checkbox[id!='" + obj + "']").prop("checked", $("#" + obj).prop("checked"));
    } else { //1.41
        $(":checkbox[id!='" + obj + "']").attr("checked", $("#" + obj).attr("checked"));
    }
}

function isEmail(str) {
    var re = /^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/;
    return re.test(str);
}

function getXY(wnd) {
    var scrollX = 0,
        scrollY = 0,
        width = 0,
        height = 0,
        contentWidth = 0,
        contentHeight = 0;
    if (typeof(wnd) == 'undefined') {
        wnd = window.self;
    }
    if (typeof(wnd.pageXOffset) == 'number') {
        scrollX = wnd.pageXOffset;
        scrollY = wnd.pageYOffset;
    } else if (wnd.document.body && (wnd.document.body.scrollLeft || wnd.document.body.scrollTop)) {
        scrollX = wnd.document.body.scrollLeft;
        scrollY = wnd.document.body.scrollTop;
    } else if (wnd.document.documentElement && (wnd.document.documentElement.scrollLeft || wnd.document.documentElement.scrollTop)) {
        scrollX = wnd.document.documentElement.scrollLeft;
        scrollY = wnd.document.documentElement.scrollTop;
    }
    if (typeof(wnd.innerWidth) == 'number') {
        width = wnd.innerWidth;
        height = wnd.innerHeight;
    } else if (wnd.document.documentElement && (wnd.document.documentElement.clientWidth || wnd.document.documentElement.clientHeight)) {
        width = wnd.document.documentElement.clientWidth;
        height = wnd.document.documentElement.clientHeight;
    } else if (wnd.document.body && (wnd.document.body.clientWidth || wnd.document.body.clientHeight)) {
        width = wnd.document.body.clientWidth;
        height = wnd.document.body.clientHeight;
    }
    if (wnd.document.documentElement && (wnd.document.documentElement.scrollHeight || wnd.document.documentElement.offsetHeight)) {
        if (wnd.document.documentElement.scrollHeight > wnd.document.documentElement.offsetHeight) {
            contentWidth = wnd.document.documentElement.scrollWidth;
            contentHeight = wnd.document.documentElement.scrollHeight;
        } else {
            contentWidth = wnd.document.documentElement.offsetWidth;
            contentHeight = wnd.document.documentElement.offsetHeight;
        }
    } else if (wnd.document.body && (wnd.document.body.scrollHeight || wnd.document.body.offsetHeight)) {
        if (wnd.document.body.scrollHeight > wnd.document.body.offsetHeight) {
            contentWidth = wnd.document.body.scrollWidth;
            contentHeight = wnd.document.body.scrollHeight;
        } else {
            contentWidth = wnd.document.body.offsetWidth;
            contentHeight = wnd.document.body.offsetHeight;
        }
    } else {
        contentWidth = width;
        contentHeight = height;
    }
    if (height > contentHeight)
        height = contentHeight;
    if (width > contentWidth)
        width = contentWidth;
    var rect = new Object();
    rect.scrollX = scrollX;
    rect.scrollY = scrollY;
    rect.width = width;
    rect.height = height;
    rect.contentWidth = contentWidth;
    rect.contentHeight = contentHeight;
    return rect;
}

function showPackageDetail(wrapId) {
    var str_award = '普通玩法';
    var url = "?c=game&a=packageDetail&wrap_id=" + wrapId;
    parent.layer.open({
        type: 2,
        shadeClose: false,
        title: str_award + '&nbsp;&nbsp;最近投注详情',
        shade: [0.3, '#000'],
        offset: ['60px', ''],
        border: [0],
        area: ['824px', '500px'],
        content: [url]
    });
    $('div.xubox_title').addClass('layui-layer-title');
}

function showBalance() {
    var wnd = parent || self;
    $('#nowBalance', wnd.document).text(' loading... ');
    $.get('/?c=user&a=showBalance', function(response) {
        if (response.balance >= 0) {
            $('#nowBalance', wnd.document).text('￥' + response.balance);
        } else {
            alert('系统繁忙，请稍候再试');
        }
    }, 'json');
}

//PK10用户余额
function PK10_showBalance() {
    $('#nowBalance').text(' loading... ');
    $.get('/?c=user&a=showBalance', function(response) {
        if (response.balance >= 0) {
            $('#nowBalance').text('￥' + response.balance);
        } else {
            alert('系统繁忙，请稍候再试');
        }
    }, 'json');
}

/**
 * 仅兼容IE和FF
 *  IE  Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)
 ff  Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.28) Gecko/20120306 Firefox/3.6.28
 chrome  Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.83 Safari/537.1
 */
function copyToClipboard(txt) {
    if (window.clipboardData) { //IE
        window.clipboardData.clearData();
        window.clipboardData.setData("Text", txt);
        if (window.clipboardData.getData("Text") == txt) {
            alert("复制成功！按 Ctrl+V 组合键进行粘贴。");
        } else {
            alert("复制失败！请设置允许访问剪贴板。");
        }
    } else if (navigator.userAgent.indexOf("Opera") != -1) {
        alert('复制功能暂不支持Opera');
    } else if (navigator.userAgent.indexOf("Firefox") != -1) { //FF
        try {
            netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
        } catch (e) {
            alert("被浏览器拒绝！\n请在浏览器地址栏输入'about:config'并回车\n然后将'signed.applets.codebase_principal_support'设置为'true'");
        }
        var clip = Components.classes['@mozilla.org/widget/clipboard;1'].createInstance(Components.interfaces.nsIClipboard);
        if (!clip)
            return;
        var trans = Components.classes['@mozilla.org/widget/transferable;1'].createInstance(Components.interfaces.nsITransferable);
        if (!trans)
            return;
        trans.addDataFlavor('text/unicode');
        var str = new Object();
        var len = new Object();
        var str = Components.classes["@mozilla.org/supports-string;1"].createInstance(Components.interfaces.nsISupportsString);
        var copytext = txt;
        str.data = copytext;
        trans.setTransferData("text/unicode", str, copytext.length * 2);
        var clipid = Components.interfaces.nsIClipboard;
        if (!clip)
            return false;
        clip.setData(trans, null, clipid.kGlobalClipboard);
        alert("复制成功！按 Ctrl+V 组合键进行粘贴。")
    } else if (navigator.userAgent.indexOf("Chrome") != -1) { //chrome
        alert('复制功能暂不支持chrome');
    }
}

function array_unique(inputArr) {
    // http://kevin.vanzonneveld.net
    // +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // +      input by: duncan
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nate
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Michael Grier
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %          note 1: The second argument, sort_flags is not implemented;
    // %          note 1: also should be sorted (asort?) first according to docs
    // *     example 1: array_unique(['Kevin','Kevin','van','Zonneveld','Kevin']);
    // *     returns 1: {0: 'Kevin', 2: 'van', 3: 'Zonneveld'}
    // *     example 2: array_unique({'a': 'green', 0: 'red', 'b': 'green', 1: 'blue', 2: 'red'});
    // *     returns 2: {a: 'green', 0: 'red', 1: 'blue'}
    var key = '',
        tmp_arr2 = {},
        val = '',
        tmp_arr3 = [];

    var __array_search = function(needle, haystack) {
        var fkey = '';
        for (fkey in haystack) {
            if (haystack.hasOwnProperty(fkey)) {
                if ((haystack[fkey] + '') === (needle + '')) {
                    return fkey;
                }
            }
        }
        return false;
    };

    for (key in inputArr) {
        if (inputArr.hasOwnProperty(key)) {
            val = inputArr[key];
            if (false === __array_search(val, tmp_arr2)) {
                tmp_arr2[key] = val;
                tmp_arr3.push(val);
            }
        }
    }
    //return tmp_arr2;  //返回对象
    return tmp_arr3; //返回数组
}

function number_format(number, decimals, dec_point, thousands_sep) {
    // http://kevin.vanzonneveld.net
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     bugfix by: Michael White (http://getsprink.com)
    // +     bugfix by: Benjamin Lupton
    // +     bugfix by: Allan Jensen (http://www.winternet.no)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +     bugfix by: Howard Yeend
    // +    revised by: Luke Smith (http://lucassmith.name)
    // +     bugfix by: Diogo Resende
    // +     bugfix by: Rival
    // +      input by: Kheang Hok Chin (http://www.distantia.ca/)
    // +   improved by: davook
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Jay Klehr
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Amir Habibi (http://www.residence-mixte.com/)
    // +     bugfix by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +      input by: Amirouche
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: number_format(1234.56);
    // *     returns 1: '1,235'
    // *     example 2: number_format(1234.56, 2, ',', ' ');
    // *     returns 2: '1 234,56'
    // *     example 3: number_format(1234.5678, 2, '.', '');
    // *     returns 3: '1234.57'
    // *     example 4: number_format(67, 2, ',', '.');
    // *     returns 4: '67,00'
    // *     example 5: number_format(1000);
    // *     returns 5: '1,000'
    // *     example 6: number_format(67.311, 2);
    // *     returns 6: '67.31'
    // *     example 7: number_format(1000.55, 1);
    // *     returns 7: '1,000.6'
    // *     example 8: number_format(67000, 5, ',', '.');
    // *     returns 8: '67.000,00000'
    // *     example 9: number_format(0.9, 0);
    // *     returns 9: '1'
    // *    example 10: number_format('1.20', 2);
    // *    returns 10: '1.20'
    // *    example 11: number_format('1.20', 4);
    // *    returns 11: '1.2000'
    // *    example 12: number_format('1.2000', 3);
    // *    returns 12: '1.200'
    // *    example 13: number_format('1 000,50', 2, '.', ' ');
    // *    returns 13: '100 050.00'
    // Strip all characters but numerical ones.
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? '' : thousands_sep, //我的改动：默认为空而不是逗号
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec);
}

function round(value, precision, mode) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +    revised by: Onno Marsman
    // +      input by: Greenseed
    // +    revised by: T.Wild
    // +      input by: meo
    // +      input by: William
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Josep Sanz (http://www.ws3.es/)
    // +    revised by: Rafał Kukawski (http://blog.kukawski.pl/)
    // %        note 1: Great work. Ideas for improvement:
    // %        note 1:  - code more compliant with developer guidelines
    // %        note 1:  - for implementing PHP constant arguments look at
    // %        note 1:  the pathinfo() function, it offers the greatest
    // %        note 1:  flexibility & compatibility possible
    // *     example 1: round(1241757, -3);
    // *     returns 1: 1242000
    // *     example 2: round(3.6);
    // *     returns 2: 4
    // *     example 3: round(2.835, 2);
    // *     returns 3: 2.84
    // *     example 4: round(1.1749999999999, 2);
    // *     returns 4: 1.17
    // *     example 5: round(58551.799999999996, 2);
    // *     returns 5: 58551.8
    var m, f, isHalf, sgn; // helper variables
    precision |= 0; // making sure precision is integer
    m = Math.pow(10, precision);
    value *= m;
    sgn = (value > 0) | -(value < 0); // sign of the number
    isHalf = value % 1 === 0.5 * sgn;
    f = Math.floor(value);

    if (isHalf) {
        switch (mode) {
            case 'PHP_ROUND_HALF_DOWN':
                value = f + (sgn < 0); // rounds .5 toward zero
                break;
            case 'PHP_ROUND_HALF_EVEN':
                value = f + (f % 2 * sgn); // rouds .5 towards the next even integer
                break;
            case 'PHP_ROUND_HALF_ODD':
                value = f + !(f % 2); // rounds .5 towards the next odd integer
                break;
            default:
                value = f + (sgn > 0); // rounds .5 away from zero
        }
    }

    return (isHalf ? value : Math.round(value)) / m;
}

function str_repeat(input, multiplier) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Ian Carter (http://euona.com/)
    // *     example 1: str_repeat('-=', 10);
    // *     returns 1: '-=-=-=-=-=-=-=-=-=-='

    var y = '';
    while (true) {
        if (multiplier & 1) {
            y += input;
        }
        multiplier >>= 1;
        if (multiplier) {
            input += input;
        } else {
            break;
        }
    }
    return y;
}

function is_numeric(mixed_var) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: David
    // +   improved by: taith
    // +   bugfixed by: Tim de Koning
    // +   bugfixed by: WebDevHobo (http://webdevhobo.blogspot.com/)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: is_numeric(186.31);
    // *     returns 1: true
    // *     example 2: is_numeric('Kevin van Zonneveld');
    // *     returns 2: false
    // *     example 3: is_numeric('+186.31e2');
    // *     returns 3: true
    // *     example 4: is_numeric('');
    // *     returns 4: false
    // *     example 4: is_numeric([]);
    // *     returns 4: false
    return (typeof(mixed_var) === 'number' || typeof(mixed_var) === 'string') && mixed_var !== '' && !isNaN(mixed_var);
}

//使用时间戳加随机字符串的方式组成唯一token
function getRandChar(len) {
    len = len || 36;
    var timestamp = new Date().getTime();
    var x = "0123456789qwertyuiopasdfghjklzxcvbnm",
        random = '';
    for (i = 0; i < len; i++) {
        random += x.charAt(Math.floor(Math.random() * x.length));
    }

    return timestamp + random;
}