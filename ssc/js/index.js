 $(function() {
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

         var arr=[];
        for(var i=1;i<12;i++){
        arr[i]=i;
        }
        arr.sort(function(){
        return Math.random()-0.5
        })
        arr.length=5;
         sd_number.eq(0).html(arr[0]);
         sd_number.eq(1).html(arr[1]);
         sd_number.eq(2).html(arr[2]);
         sd_number.eq(3).html(arr[3]);
         sd_number.eq(4).html(arr[4]);
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
         var arr = [0,1,2,3,4,5,6,7,8,9];
         var index0 = Math.floor((Math.random()*arr.length));
         var index1 = Math.floor((Math.random()*arr.length));
         var index2 = Math.floor((Math.random()*arr.length));

         fc_number.eq(0).html(arr[index0]);
         fc_number.eq(1).html(arr[index1]);
         fc_number.eq(2).html(arr[index2]);

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
    })

  $(function() {
    var myclick = function(v) {
    // var llis = document.getElementsByClassName("edded");
    // for(var i = 0; i < llis.length; i++) {
    //     var lli = llis[i];
    //     if(lli == document.getElementById("tab" + v)) {
    //         lli.style.border = "2px solid red";
    //     } else {
    //         lli.style.border = "2px solid #fff";
    //     }
    // }

    // var divs = document.getElementsByClassName("tab_css_gg");
    // for(var i = 0; i < divs.length; i++) {

    //     var divv = divs[i];

    //     if(divv == document.getElementById("tab" + v + "_content")) {
    //         divv.style.display = "block";
    //     } else {
    //         divv.style.display = "none";
    //     }
    // }

}


    $('.download_sec div').mouseenter(function() {
        var index = $(this).index();
        $(this).css("borderBottom","2px solid red").siblings().css("borderBottom","");
        $('.download_icn div').eq(index).show().siblings().hide();
    });
    
       var nums = $('#move div').length;

    setInterval(function() {

        var top = $('#move').css('marginTop');

        var old_top = parseInt(top);

        if(old_top<(nums-9)*-65){
            $('#move').css('marginTop','0px');
            var top = $('#move').css('marginTop');
            var old_top = parseInt(top);
        }
        var num = -65;
        var new_top =old_top+num;

      $('#move').animate({marginTop:new_top},'slow')

    },2000)

})