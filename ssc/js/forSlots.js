$(document).ready(function () {
   init;//初始化
   $(".mesBg").height($(document).height());//背景遮罩层
   $(".mesBox").hide();
   var click = 1; //控制在一次抽奖结束前下次点击无效
   var index; //控制的抽奖结束后效果的还原
   $(".button a").click(function () {
       //进行判断是否抽奖完毕
       if (click != 1) {
           return;
       }
       click++;
       //抽奖完后将图片还原到初始状态  
       if (index != 1) {
           $(".cjtab td img").parent().removeClass();
       }
       var i = 1; //图片轮播的地方
       var T = 1; //控制转了多少圈才停下来的变量
       var time = setInterval(function () {
           i++;
           //控制图片轮播的范围（24张图片）
           if (i > 24) {
               i = 1;
               T++;
           };
           index = i;
           var tit = $("#img" + i).attr("title");//获取奖品名称
           var inserEnd = "<tr><td>"+init.date+"</td><td>"+tit+"</td><td>"+init.money+"元</td></tr>";
           var inserTop = "<li>恭喜会员于"+init.date+"抽中"+init.money+"元</li>";
           //此处为图片的滚动
           $(".cjtab td img").parent().removeClass();
           $("#img" + i).parent().addClass("on");
           //当滚动3圈后开始出奖
           if (T == 3) {
               if (i == init.s) {
                   clearInterval(time);
                   click = 1;
                   $("#img" + i).parent().addClass("jggg");
                   $(".mestext .strg strong").html(init.money);
                   setTimeout(function(){
                    $("#img" + i).parent().removeClass("jggg");
                    $(".mesBox").fadeIn(200);
                    $(".pz_main tr:last").after(inserEnd);
                    $(".notice .nc ul:first").append(inserTop);
                   }, 2500);
               }
           }
       }, 100)
   });
   $(".mestext a").click(function () {
       $(".mesBox").fadeOut(200);
   });
});

$(function(){
    //中奖轮播滚动
    var jianxie=$("#jianxie");
    var demo1_jx=$("#demo1_jx");
    var demo2_jx=$("#demo2_jx");
    demo2_jx.html(demo1_jx.html());//设置2个一样的ul
    var ismove_jx=true;
    function startScroll(){
      if(ismove_jx){
        time=setInterval(scrollUp,20);//滚动速度
        jianxie.scrollTop(jianxie.scrollTop()+1);
      }
    };
    function scrollUp(){
      if(jianxie.scrollTop()%38==0){
        clearInterval(time);
        setTimeout(startScroll,500);//停留时间
      }else{
        jianxie.scrollTop(jianxie.scrollTop()+1);
        if(jianxie.scrollTop()>=demo1_jx.height()){
          jianxie.scrollTop(0);
        }
      }
    };
    setTimeout(startScroll,500);
    jianxie.hover(function(){
      clearInterval(time);
      ismove_jx=false;
    },function(){
      setTimeout(startScroll,1000);
      ismove_jx=true;
    });

    //平滑滚动
    $('.sup_star a').click(function(){$('html,body').animate({scrollTop:$('.luck').offset().top}, 500);});

    function remve(){//即将启幕,开始后删除
        $("#j_grayscale,#c_grayscale,.sup_star,.cj_star,script[src='js/grayscale.js']").remove();
        $('.nodeat,.nodeat *,.noda,.noda *,.noda .button a,.cj_star .button a').removeAttr("style");
      };

    $(document).ready(function() { 
        function jump(count) { 
            window.setTimeout(function(){ 
                count--; 
                if(count > 0) { 
                    jump(count); 
                } else { 
                    remve(); 
                } 
            }, 1000); 
        } 
        jump(10); 
    });
  });