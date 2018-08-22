/**
 * Created by 49937 on 2017/4/11.
 */
 //首页开奖号码随机效果开始
        ssc_change();
        sd_change();
        xy_change();
        js_change();
        fc_change();
       function ssc_change() {
         var ssc_number = $('#ssc_number li');
         var arr = [0,1,2,3,4,5,6,7,8,9];
         var index0 = Math.floor((Math.random()*arr.length));
         var index1 = Math.floor((Math.random()*arr.length));
         var index2 = Math.floor((Math.random()*arr.length));
         var index3 = Math.floor((Math.random()*arr.length));
         var index4 = Math.floor((Math.random()*arr.length));

         ssc_number.eq(0).html(arr[index0]);
         ssc_number.eq(1).html(arr[index1]);
         ssc_number.eq(2).html(arr[index2]);
         ssc_number.eq(3).html(arr[index3]);
         ssc_number.eq(4).html(arr[index4]);
    };

       function sd_change() {
         var sd_number = $('#sd_number li');
         var arr = [0,1,2,3,4,5,6,7,8,9];
         var index0 = Math.floor((Math.random()*arr.length));
         var index1 = Math.floor((Math.random()*arr.length));
         var index2 = Math.floor((Math.random()*arr.length));
         var index3 = Math.floor((Math.random()*arr.length));
         var index4 = Math.floor((Math.random()*arr.length));
         // console.log()
         sd_number.eq(0).html(arr[index0]);
         sd_number.eq(1).html(arr[index1]);
         sd_number.eq(2).html(arr[index2]);
         sd_number.eq(3).html(arr[index3]);
         sd_number.eq(4).html(arr[index4]);
    };

       function xy_change() {
         var xy_number = $('#xy_number li');
         var arr = [0,1,2,3,4,5,6,7,8,9];
         var index0 = Math.floor((Math.random()*arr.length));
         var index1 = Math.floor((Math.random()*arr.length));
         var index2 = Math.floor((Math.random()*arr.length));

         xy_number.eq(0).html(arr[index0]);
         xy_number.eq(1).html(arr[index1]);
         xy_number.eq(2).html(arr[index2]);

       };
        function js_change() {
         var js_number = $('#js_number li');
         var arr = [0,1,2,3,4,5,6,7,8,9];
         var index0 = Math.floor((Math.random()*arr.length));
         var index1 = Math.floor((Math.random()*arr.length));
         var index2 = Math.floor((Math.random()*arr.length));

         js_number.eq(0).html(arr[index0]);
         js_number.eq(1).html(arr[index1]);
         js_number.eq(2).html(arr[index2]);

       };
        function fc_change() {
         var fc_number = $('#fc_number li');
         var arr=[];
        for(var i=1;i<50;i++){
        arr[i]=i;
        }
        arr.sort(function(){
        return Math.random()-0.5
        })
        arr.length=7;

         fc_number.eq(0).html(arr[0]);
         fc_number.eq(1).html(arr[1]);
         fc_number.eq(2).html(arr[2]);
         fc_number.eq(3).html(arr[3]);
         fc_number.eq(4).html(arr[4]);
         fc_number.eq(5).html(arr[5]);
         fc_number.eq(6).html(arr[6]);

       };

      $('#number_show div:nth-child(1)').mouseenter(function() {
        ssc_change();
      });
      $('#number_show div:nth-child(2)').mouseenter(function() {
        sd_change();
       });
      $('#number_show div:nth-child(3)').mouseenter(function() {
        xy_change();
      });
       $('#number_show div:nth-child(4)').mouseenter(function() {
        js_change();
      });
      $('#number_show div:nth-child(5)').mouseenter(function() {
        fc_change();
      });
       $('.one').click(function() {
        ssc_change();
      });
       $('.two').click(function() {
        sd_change();
      });
       $('.three').click(function() {
        xy_change();
      });
       $('.four').click(function() {
       js_change();
      });
       $('.five').click(function() {
        fc_change();
      });
        //首页随机开奖效果结束
//获取非行内样式
function getstyle(obj, attr) {

    if (obj.currentStyle) {
        return obj.currentStyle[attr];
    } else {
        return getComputedStyle(obj, null)[attr];
    }
}
function animate(obj, json, callback,sp) {
    //首先清除定时器，避免多次点击产生混乱。
    clearInterval(obj.timer);
    //生成一个定时器
    obj.timer = setInterval(function () {
        var flag = true;
        //遍历对象json
        for (var attr in json) {
            //获取当前对象attr属性值
            var cur = parseInt(getstyle(obj, attr));
            //获取当前的目标值
            var target = json[attr];
            //获取速度
            var speed = (target - cur) / sp;
            //对速度进行取整
            speed = target > cur ? Math.ceil(speed) : Math.floor(speed);
            //判断当前动画是否执行完毕，如果没有继续执行。
            if (target != cur) {
                obj.style[attr] = cur + speed + 'px';
                flag = false;
            }
        }
        //当所有动画执行完毕，清除定时器。执行回调函数
        if (flag) {
            clearInterval(obj.timer);
            //p判断用户是否传入回调函数，是否执行回调函数
            !!callback && callback();
            // if(callback){
            //     callback();
            // }
        }
    }, 10)
}
//选择彩种
function mv() {
    $('.select_top').css({'background':'#000','z-index':'111'});
    $('.select-list').css({'display':'block','background':'#fff'})

}
function mu() {
    // $('.select-dis').css({display:'block'});
    $('.select_top').css({'background':''});
    $('.select-list').css({'display':'none'})

}
function xyOver() {
    $('.se-xy-list').css('display','block');
    // $('.se-all-xy').css('background','#e4393c').css('color','#fff');

}
function xyOut() {
    $('.se-xy-list').css('display','none');
    // $('.se-all-xy').css('background','#fff').css('color','#333');
}
//全部选项
function amv() {
    $('.select-all').addClass('bor-r');
    $('.se-all-list').css({display:'block'})

}
function amu() {
    $('.select-all').removeClass('bor-r');
    $('.se-all-list').css({display:'none'})

}
//选项卡
function lsSelect(_this) {
    var $this=_this;
    $('.ls-text div').each(function () {
        var index=$($this).index();
        $($this).addClass('on').siblings().removeClass('on');

        $('.ls-logo').eq(index).removeClass('ds').siblings().addClass('ds')
    })
}
//资讯分页第一页。。。。
function paging(_this) {
    var $this=_this;
    $($this).addClass('on').siblings().removeClass('on')
}
//首页登录下选项
function idSe_x(_this,obj,d,fn) {
   $(d).find('input').val(1);
   $(d).find('.toal-much').html(2);
    var $this=_this;
    $(obj).each(function () {
        var index=$($this).index();
        $($this).attr('class','banner_list_hover_x').siblings().attr('class','banner_list_x')
        $(d).eq(index).show().siblings().hide();
    });
    fn;
}
function idSe(_this,obj,d) {
   $(d).find('input').val(1);
   $(d).find('.toal-much').html(2);
    var $this=_this;
    $(obj).each(function () {
        var index=$($this).index();
        $($this).attr('class','banner_list_hover').siblings().attr('class','banner-list')
        $(d).eq(index).show().siblings().hide();
    });
}
//首页购买彩票运算

function re() {
    var number=$('.id-banner-input input').val();
    number=parseInt(number);
    if(number==0){
        $('.id-banner-input input').val(0)
    }else {
        number--;
        $('.id-banner-input input').val(number);
        var money=number*2;
        $('.toal-much').text(money)
    }
}
function add() {
    var number=$('.id-banner-input input').val();
    number=parseInt(number);
        number++;
        $('.id-banner-input input').val(number);
        var money=number*2;
        $('.toal-much').text(money)
}
function inpChange() {
    var number=$('.id-banner-input input').val();
    number=parseInt(number);
    var money=number*2;
    $('.toal-much').text(money)
}
