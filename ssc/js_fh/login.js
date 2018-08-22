(function () {
        // var flag;
        // setInterval(function () {
        //     if (flag) {
        //         $('.hot').css({'display': 'none'});
        //         $('.hot').css({'display': 'block'})
        //     } else {
        //         $('.hot').css({'display': 'none'});
        //         $('.hot').css({'display': 'block'})
        //     }
        //     flag = !flag;
        // }, 100);

        // var img = document.getElementById('lb-img');//显示轮播显示区域
        // var allLi = $('.lb-tt li');//轮播点
        // var a = $('#lb-img a');
        // //克隆第一张图片，放到最后一张，无缝滚动
        // var node;
        // for (var i = 0; i < a.length; i++) {
        //     node = a[0].cloneNode(true);
        // }
        // img.appendChild(node);
        // var hot = 0, li = 0;
        // var allH = $('.lb-img').find('.lb-img1').length;

        // var hotF = document.getElementById('lb-img');//显示轮播显示区域

        // function lbAuto() {
        //     hot++;
        //     li++;
        //     if (hot == allH) {
        //         hot = 1;
        //         li = 1;
        //         hotF.style.left = 0 + 'px'
        //     }
        //     for (var i = 0; i < allLi.length; i++) {
        //         if (li == 5) {
        //             li = 0;
        //         }
        //         if (i == li) {
        //             allLi[i].className = 'on'
        //         } else {
        //             allLi[i].className = ''
        //         }
        //     }
        //     animate(hotF, {left: -490 * hot}, "", 8)
        // }

        // var timer;
        // timer = setInterval(function () {
        //     lbAuto();
        // }, 5000);
        // $('.id-lb').on('mouseover', function () {
        //     clearInterval(timer)
        // });
        // $('.id-lb').on('mouseout', function () {
        //     timer = setInterval(function () {
        //         lbAuto();
        //     }, 5000);
        // });
        // //鼠标移到轮播指示点，显示相对于的图片
        // for (var x = 0; x < allLi.length; x++) {
        //     allLi[x].ind = x;
        //     allLi[x].onmouseover = function () {
        //         clearInterval(timer);
        //         $(this).addClass('on').siblings().removeClass('on');
        //         for (var i = 0; i < a.length; i++) {
        //             if (i == this.ind) {
        //                 hotF.style.left = -490 * this.ind + 'px';
        //             }
        //         }
        //     }
        // }
/*---------- 轮播 begin ----------*/
var $carousel = document.getElementById('lb-img');//显示轮播显示区域
var $millisec = 5000; // 轮播切换时间

/* 生成轮播点 begin */
var $aList = $('#lb-img a');

var $html = '';
for (var $i = 0; $i < $aList.length; ++$i) {
    if ($html) {
        $html += '<li></li>';
    } else {
        $html = '<li class="on"></li>';
    }
}
$('.lb-tt').html($html);
/* 生成轮播点 end */

var $liList = $('.lb-tt li');//轮播点

/* 克隆第一张图片，放到最后一张，无缝滚动 begin */
var $firstNode;
for (var $i = 0; $i < $aList.length; ++$i) {
    $firstNode = $aList[0].cloneNode(true);
}
$firstNode && $carousel.appendChild($firstNode);
/* 克隆第一张图片，放到最后一张，无缝滚动 end */

// 因为复制了一张图,所有这里要加一个图.
var $imageLength = $aList.length + 1;

var $liNum = 0;

function lbAuto() {
    ++$liNum;
    if ($liNum === $imageLength) {
        $liNum = 1;
        $carousel.style.left = 0 + 'px';
    }
    for (var $i = 0; $i < $liList.length; ++$i) {
        if ($liNum === $liList.length) {
            $liNum = 0;
        }
        if ($i === $liNum) {
            $liList[$i].className = 'on';
        } else {
            $liList[$i].className = '';
        }
    }
    animate($carousel, {left: -490 * $liNum}, "", 8);
}

var timer;
timer = setInterval(function () {
    lbAuto();
}, $millisec);
$('.id-lb').on('mouseover', function () {
    clearInterval(timer);
});
$('.id-lb').on('mouseout', function () {
    timer = setInterval(function () {
        lbAuto();
    }, $millisec);
});
//鼠标移到轮播指示点，显示相对于的图片
for (var x = 0; x < $liList.length; ++x) {
    $liList[x].ind = x;
    $liList[x].onmouseover = function () {
        clearInterval(timer);
        $(this).addClass('on').siblings().removeClass('on');
        for (var $i = 0; $i < $aList.length; ++$i) {
            if ($i === this.ind) {
                $carousel.style.left = -490 * this.ind + 'px';
            }
        }
    }
}
/*---------- 轮播 end ----------*/

        /****************公告滚动begin***************/
        function ScrollImgLeft(){
        var speed=30;
        var MyMar = null;
        var scroll_begin = document.getElementById("NewSl_begin");
        var scroll_end = document.getElementById("NewSl_end");
        var scroll_div = document.getElementById("NewSl");
        scroll_end.innerHTML=scroll_begin.innerHTML;
        function Marquee(){
            if(scroll_end.offsetWidth-scroll_div.scrollLeft<=0)
                scroll_div.scrollLeft-=scroll_begin.offsetWidth;
            else
                scroll_div.scrollLeft++;
        }
        MyMar=setInterval(Marquee,speed);
        scroll_div.onmouseover = function(){
            clearInterval(MyMar);
        }
        scroll_div.onmouseout = function(){
            MyMar = setInterval(Marquee,speed);
        }
    }
    ScrollImgLeft();
        //var speed = 30;
        //var scroll_begin = document.getElementById("NewSl_begin");
        //var scroll_end = document.getElementById("NewSl_end");
        //var scroll_div = document.getElementById("NewSl");
        //scroll_end.innerHTML = scroll_begin.innerHTML;

        //function Marquee() {
            //if (scroll_end.offsetWidth - scroll_div.scrollLeft <= 0)
                //scroll_div.scrollLeft -= scroll_begin.offsetWidth;
            //else
                //scroll_div.scrollLeft++;
        //}
        //var MyMar = setInterval(Marquee, speed);
        //scroll_div.onmouseover = function() { clearInterval(MyMar); }
        //scroll_div.onmouseout = function() { MyMar = setInterval(Marquee, speed); }
        /****************公告滚动end***************/

        var runTime = {
            timer_1 : 0,
            timer_2 : 0,
            timer_9 : 0,
            timer_12 : 0,
            timer_23 : 0
        }
        var remainTime = {
            remaintimer_1 : 0,
            remaintimer_2 : 0,
            remaintimer_9 : 0,
            remaintimer_12 : 0,
            remaintimer_23 : 0
        }
        $(".goLogin").live('click',function(){
            layer.alert('请先登录!', {
            skin: 'layui-layer-lan',
            closeBtn: 1,
            anim: 3 ,//动画类型
            title:'',
            })
        });
        // $.ajax({
        //     url: "?a=openInfo",
        //     type: "POST",
        //     cache: false,
        //     dataType: "json",
        //     timeout: 30000,
        //     success: function(res) {
        //         if(res.length != 0){
        //             var timestamp = Date.parse(new Date());
        //             var count = 0;
        //             var openStr = ''
        //             $.each(res,function(i,item){
        //                 if(i == '1' || i == '2' || i == '9' || i == '12' || i == '23'){//轮播下面的开奖
        //                     var ts = item.issueInfo.end_time - (timestamp / 1000);
        //                     var codes,li='';
        //                     if(ts > 0){
        //                         issueTimer(ts, i);
        //                         $('#bannerLotteryId_'+ i +' .on').html('第 '+item.issueInfo.issue+' 期');
        //                         if(/^\d\d(\s\d\d){4}$/.test(item.lastIssueInfo.code)){//如果是11选5的格式
        //                             codes = item.lastIssueInfo.code.split(' ');
        //                         }else if(/^\d{3,5}$/.test(item.lastIssueInfo.code)){//如果是时时彩格式
        //                             codes = item.lastIssueInfo.code.split('');
        //                         }
        //                         if(Object.prototype.toString.call(codes) === '[object Array]'){
        //                             $.each(codes,function(j,it){
        //                                 li += '<li>'+it+'</li>'
        //                             });
        //                             $('#bannerLotteryId_'+ i +' .banner-num').html(li);
        //                         }
        //                     } else {//说明当前彩种在停售
        //                         $('#bannerLotteryId_'+ i +' .on').html('等待开奖');
        //                     }
        //                 } else {
        //                     if(count < 7 && i != 17 && i != 14 && i != 15){//左下角开奖公告只显示7个
        //                         var code = item.lastIssueInfo.code;
        //                         var codestr2 = '';
        //                         if(code != ''){
        //                             if(/^\d{3,5}$/.test(code)){
        //                                 var codeTmp = String(code).split('');
        //                             }else if(/^(\d{1,2}\s){4,9}\d{1,2}$/.test(code)){
        //                                 var codeTmp = String(code).split(' ');
        //                             }
        //
        //                             $.each(codeTmp, function(k,v){
        //                                 codestr2 += '<span>'+v+'</span>';
        //                             });
        //                         } else {
        //                             codestr2 = '<span>正在开奖</span>';
        //                         }
        //                         var codestr1 = '<div class="id-lt-ss"><div><span class="ss-h">'+item.lastIssueInfo.cname+'</span><span style="float:right">'+item.lastIssueInfo.issue+'期</span></div><div class="gg-ball">';
        //                         var codestr3 = '<div class="fr gg-ttd"><a href="' + item.fun + '" class="goLogin">立即投注</a></div></div></div>';
        //                         openStr += codestr1 + codestr2 + codestr3;
        //                         count++;
        //                     }
        //                 }
        //             });
        //            $('#openInfo').append(openStr);
        //         } else {
        //             console.log('未获得数据');
        //         }
        //     }
        // })

    /****************开奖信息end***************/
    // function issueTimer(time,lotteryId){
    //     //console.log(time,lotteryId);
    //     switch (lotteryId){
    //         case '1':
    //             remainTime.remaintimer_1 = time;
    //             runTime.timer_1 = setInterval(function () { remainTime.remaintimer_1--; setTime( remainTime.remaintimer_1, lotteryId);},1000);
    //             break;
    //         case '2':
    //             remainTime.remaintimer_2 = time;
    //             runTime.timer_2 = setInterval(function () { remainTime.remaintimer_2--; setTime( remainTime.remaintimer_2, lotteryId);},1000);
    //             break;
    //         case '9':
    //             remainTime.remaintimer_9 = time;
    //             runTime.timer_9 = setInterval(function () { remainTime.remaintimer_9--; setTime( remainTime.remaintimer_9, lotteryId);},1000);
    //             break;
    //         case '12':
    //             remainTime.remaintimer_12 = time;
    //             runTime.timer_12 = setInterval(function () { remainTime.remaintimer_12--; setTime( remainTime.remaintimer_12, lotteryId);},1000);
    //             break;
    //         case '23':
    //             remainTime.remaintimer_23 = time;
    //             runTime.timer_23 = setInterval(function () { remainTime.remaintimer_23--; setTime( remainTime.remaintimer_23, lotteryId);},1000);
    //             break;
    //         default:
    //             console.log('错误彩种ID'+lotteryId);
    //     }
    //
    // }

//    倒计时
//     function setTime( sec, lotteryId ){
//         if(sec <= 0 ){
//             switch (lotteryId){
//                 case '1':
//                     clearInterval(runTime.timer_1);
//                     break;
//                 case '2':
//                     clearInterval(runTime.timer_2);
//                     break;
//                 case '9':
//                     clearInterval(runTime.timer_9);
//                     break;
//                 case '12':
//                     clearInterval(runTime.timer_12);
//                     break;
//                 case '23':
//                     clearInterval(runTime.timer_23);
//                     break;
//             }
//         } else {
//             var dayT,hourT,minT,secT;
//
//             dayT = '#bannerLotteryId_'+lotteryId+' .day';
//             hourT = '#bannerLotteryId_'+lotteryId+' .hour';
//             minT = '#bannerLotteryId_'+lotteryId+' .min';
//             secT = '#bannerLotteryId_'+lotteryId+' .second';
//
//             sec = parseInt(sec);
//             var day = parseInt(sec/(3600*24));
//             $('#bannerLotteryId_'+lotteryId+' .day').text(day);
//             sec = sec - (3600*24)*day;
//             var h = parseInt(sec/3600);
//             $('#bannerLotteryId_'+lotteryId+' .hour').text(h);
//             sec = sec - 3600*h;
//             var m = parseInt(sec/60);
//             $('#bannerLotteryId_'+lotteryId+' .min').text(m);
//             sec = sec - 60*m;
//             $('#bannerLotteryId_'+lotteryId+' .second').text(sec);
//         }
//
//     }

       
    })();

    function idMv() {
        $('.select-all').addClass('bor-r');
        $('.se-all-list').css({display:'block'});
        //$('.select-list-t').removeClass('is');
        $('.select-all').removeClass('on');
        //$('.select-list').css({border:'none'})

    }
    function idMu() {
        $('.select-all').removeClass('bor-r');
        $('.se-all-list').css({display:'none'});
        //$('.select-list-t').addClass('is');
        $('.select-all').addClass('on');
        //$('.select-list').css({border:'2px solid #e4393c ',borderTop:'none'})
    }
