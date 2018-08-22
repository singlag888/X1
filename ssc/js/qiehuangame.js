//初始化切换官方信用玩法链接
var qhDiv = document.createElement('div');
var qhbtna = document.createElement("a");
qhDiv.id = 'smbtn';
qhDiv.className = 'smbtn';
qhbtna.id = 'qiehuan';
document.body.appendChild(qhDiv);
document.getElementById("smbtn").appendChild(qhbtna);
thisURL = '/?'+ document.URL.split('?')[1];
var thisURLstr = thisURL.substr(thisURL.length - 2);
var contentstr = document.getElementById("qiehuan");
if (thisURLstr === '_x') {
    contentstr.innerText = "切换官方";
    contentstr.href = thisURL.substr(0, thisURL.length - 2);
} else {
    contentstr.innerText = "切换信用";
    contentstr.href = thisURL + '_x';
}