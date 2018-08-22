// Copyright (c) 2010 Ivan Vanderbyl
// Originally found at http://ivan.ly/ui
// 
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
// 
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

(function( $ ){
  // Simple wrapper around jQuery animate to simplify animating progress from your app
  // Inputs: Progress as a percent, Callback
  // TODO: Add options and jQuery UI support.
  $.fn.animateProgress = function(progress, callback) {    
    return this.each(function() {
      $(this).animate({
        width: progress+'%'
      }, {
        duration: 2000, 
        
        // swing or linear
        easing: 'swing',

        // this gets called every step of the animation, and updates the label
        step: function( progress ){
          var labelEl = $('.ui-label', this),
              valueEl = $('.value', labelEl);
          
          if (Math.ceil(progress) < 20 && $('.ui-label', this).is(":visible")) {
            labelEl.hide();
          }else{
            if (labelEl.is(":hidden")) {
              labelEl.fadeIn();
            };
          }
          
          if (Math.ceil(progress) == 100) {
            labelEl.text('完成');
            setTimeout(function() {
              labelEl.fadeOut();
            }, 1000);
          }else{
            valueEl.text(Math.ceil(progress) + '%');
          }
        },
        complete: function(scope, i, elem) {
          if (callback) {
            callback.call(this, i, elem );
          };
        }
      });
    });
  };
})( jQuery );


$(function() {

  //>>导出数据
  function exportFile(url, data)
  {
    //>>发起postygfi ,开始打包数据
    $.post(url, data, function (response)
    {
      //>>判断是否是最后一个

      if (response && response.flag === true)
      {
        //>>此阶段数据打包成功

        var nowUp = parseInt((data.page / data.totalPage) * 100);
        $('#snow_progress_bar .snow_ui-progress').animateProgress(nowUp, function ()
        {
          if (nowUp === 100)
          {
            //>>如果两个值相等 或者当前页面大于总页数
            //>>后台数据已经打包完成 ,获取下载地址
            layer.closeAll()
            location.href = response.data.fileName;

          }
          else
          {
            data.page += 1;
            exportFile(url, data);
          }
        });
      }
      else
      {
        layer.closeAll();
        //>>数据出错 TODO
        layer.alert('网络故障,下载失败');
      }

    }, "json");

  }

  $('#export_excel_data').click(function ()
  {
    //>>先判断当前查询是否有数据
    var urlCount = $(this).attr('data-urlCount');//>>总量接口
    var urlData  = $(this).attr('data-urlData');  //>>数据接口
    if ($('.no-records').length === 1 || $('#listDiv tbody tr').length < 1)
    {
      layer.alert('当前查询没有数据');
    }
    else
    {

      var my_url = location.href + '?' + urlCount;
      //>>获取查询数据



      $.getJSON(my_url, function (response)
      {
         if (response && response.flag === true)
         {
          //>>如果返回值正常
          var data = response.data;   //>>返回信息
          data.page = 1;       //>>初始值为1
          url = '?' + urlData;       //>>初始值为1

           layer.open({
             type: 1,
             skin: 'layui-layer-rim', //加上边框
             area: ['420px', '240px'], //宽高
             title: '正在为你打包中,打包完成自动下载',
//                    shadeClose: true,
             content: '<div id="snow_container">\
                <div class="snow_content">\
                    <h1></h1>\
                    </div>\
                        <!-- Progress bar -->\
                    <div id="snow_progress_bar" class="snow_ui-progress-bar snow_ui-container">\
                    <div class="snow_ui-progress" style="width: 5%;">\
                    <span class="snow_ui-label" style="display:none;">正在加载...<b class="value">5%</b></span>\
                </div>\
                </div>\
                    <!-- /Progress bar -->\
                <div class="snow_content" id="snow_main_content" style="display: none;">\
                    <p>加载完成。跳转到下载页面</p>\
                </div>\
                </div>\
                <div style="text-align:center;margin:50px 0">'
           });

          exportFile(url, data);
          }
         else
         {
           //>>错误
           var error = response.data !== undefined ? response.data.error :(response.errMsg !== undefined ? response.errMsg : '网络故障');
           layer.alert(error);
          }
        });

     }


   })

  //>>snow  添加  批量删除
  $('.snow-delete-all').click(function()
  {
    var url = $(this).attr('data-url');  //>>获取url
    var id  = $(this).attr('data-id');   //>>获取id
    //>>获取所有已经选中的存款id
    var arr = [];
    $('.snow-id:checked').each(function(index)
    {
      arr[index] = $(this).attr(id);

    })
    if(arr.length === 0)
    {
      layer.alert('没有选中任何数据');
      return false;
    }
    //>>调用ajax 删除数据
    layer.confirm('确认删除这些数据', {
      btn: ['确定','取消'] //按钮
    }, function()
    {
      $.post(url, {ids : arr}, function(response)
      {
        if(response && response.flag !== undefined && response.flag === true)
        {
          //>>删除成功
          //>>刷新页面
          location.reload() ;
        }else{
          var error = response.data !== undefined ? response.data.error :(response.errMsg !== undefined ? response.errMsg : '网络故障');
          layer.alert("删除失败\n\r" + error)
        }
      },'json')
    }, function(){});


  })


})
