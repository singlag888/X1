$(function(){
	 //banner处滚动公告
	var active = 0,
	        as = document.getElementById('pagenavi').getElementsByTagName('div');
	    for (var i = 0; i < as.length; i++) {
	        (function () {
	            var j = i;
	            as[i].onclick = function () {
	                t2.slide(j);
	                return false;
	            }
	        })();
	    }
	    var t2 = new TouchSlider({
	        id: 'slider', speed: 600, timeout: 6000, before: function (index) {
	            active = index;
	        }
	    });
	    (function () {
	        $(".goLogin").live('click', function () {
	            layer.alert('请先登录!', {
	                skin: 'layui-layer-lan',
	                closeBtn: 1,
	                anim: 3,//动画类型
	                title: '',
	            })
	        });

	        // $.ajax({
	        //     type : 'post',
	        //     dataType : 'json',
	        //     url : '?a=domainInfo',
	        //     success : function(res){
	        //         var htmStr = '';
	        //         if(res == 1){
	        //             htmStr = '<a class="headboxright" href=\'?a=marketReg\'>注 册</a>';
	        //         }
	        //         $('header').append(htmStr);
	        //     },
	        //     error : function(data){
	        //             console.log(data);
	        //     }
	        // });
	    })()
})
