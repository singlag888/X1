/*! jQuery UI.slider - v1.0 - 2013-01-26
 * original by William
 * Copyright (c) 2013 AUTHORS.txt; Licensed MIT, GPL
 * Depends:
 * jQuery 1.4.1 or above
 */
(function($){

$.fn.bgiframe = ($.browser.msie && /msie 6\.0/i.test(navigator.userAgent) ? function(s) {
    s = $.extend({
        top     : 'auto', // auto == .currentStyle.borderTopWidth
        left    : 'auto', // auto == .currentStyle.borderLeftWidth
        width   : 'auto', // auto == offsetWidth
        height  : 'auto', // auto == offsetHeight
        opacity : true,
        src     : 'javascript:false;'
    }, s);
    var html = '<iframe class="bgiframe"frameborder="0"tabindex="-1"src="'+s.src+'"'+
                   'style="display:block;position:absolute;z-index:-1;'+
                       (s.opacity !== false?'filter:Alpha(Opacity=\'0\');':'')+
                       'top:'+(s.top=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderTopWidth)||0)*-1)+\'px\')':prop(s.top))+';'+
                       'left:'+(s.left=='auto'?'expression(((parseInt(this.parentNode.currentStyle.borderLeftWidth)||0)*-1)+\'px\')':prop(s.left))+';'+
                       'width:'+(s.width=='auto'?'expression(this.parentNode.offsetWidth+\'px\')':prop(s.width))+';'+
                       'height:'+(s.height=='auto'?'expression(this.parentNode.offsetHeight+\'px\')':prop(s.height))+';'+
                '"/>';
    return this.each(function() {
        if ( $(this).children('iframe.bgiframe').length === 0 )
            this.insertBefore( document.createElement(html), this.firstChild );
    });
} : function() { return this; });

// old alias
$.fn.bgIframe = $.fn.bgiframe;

function prop(n) {
    return n && n.constructor === Number ? n + 'px' : n;
}
})(jQuery);

/*
<div id="ui-dialog" style="outline: 0px none; display: none; z-index: 1002;" class="ui-dialog" tabindex="-1"><div class="ui-dialog-titlebar"><span class="ui-dialog-title" id="ui-dialog-title"></span></div><div id="dialog" class="ui-dialog-content"></div><div class="ui-dialog-button ui-helper-clearfix"><div class="ui-dialog-buttonset"></div></div></div><div class="ui-widget-overlay" style="z-index: 1001;"></div>
标题动态设置到<span class="ui-dialog-title" id="ui-dialog-title"></span>
动态button插入<div class="ui-dialog-buttonset">
<button type="button" class="ui-button"><span class="ui-button-text">确认</span></button>
<button type="button" class="ui-button"><span class="ui-button-text">取消</span></button>
 */
(function($){
    $.fn.dialog = function(settings, wnd){
        wnd = wnd || window.self;
        if (typeof(settings) == 'string') {
            if (settings == 'close') {  ///^[a-z][A-Z]\w+$/.test(settings)
                //eval(settings + "()");
                $('#ui-dialog', wnd.document).remove();
                $('#ui-widget-overlay', wnd.document).remove();
                return true;
            }
            else {
                throw "unknown command";
            }
        }

        if (!$.isPlainObject(settings)) {
            throw "settings must be a pure object";
        }

        var ps = $.extend({
            bgiframe:true,  //todo:是否使用 bgiframe 插件解决 IE6 下无法遮盖 select 元素问题。
            width:400,      //设定对话框宽度，像素单位。
            showTitle: true, //默认显示标题栏,否为不显示
            buttons: {
                '确认': function() {
                    close();
                },
                '取消': function() {
                    close();
                }
            }
        }, settings);
        
        if ($("#ui-widget-overlay", wnd.document).length > 0) {
            console.info("之前的层先移除");
            $('#ui-dialog', wnd.document).remove();
            $('#ui-widget-overlay', wnd.document).remove();
        }

        //var uiDialog = $('<div id="ui-dialog" style="outline: 0px none; z-index: 9002;" class="ui-dialog" tabindex="-1"><div class="ui-dialog-titlebar"><span class="ui-dialog-title" id="ui-dialog-title"></span></div><div class="ui-dialog-content"></div><div class="ui-dialog-button ui-helper-clearfix"><div class="ui-dialog-buttonset"></div></div></div>');
        //uiDialog.css('width', ps.width).appendTo($('body', wnd.document)).hide();
        //uiDialog.css('width', ps.width).hide();
        $('body', wnd.document).append('<div id="ui-dialog" style="outline: 0px none; z-index: 100000;" class="ui-dialog" tabindex="-1"><div class="ui-dialog-titlebar"><span class="ui-dialog-title" id="ui-dialog-title"></span></div><div class="ui-dialog-main"><div class="ui-dialog-boxtab"><div class="ui-dialog-content"></div></div><div class="ui-dialog-button ui-helper-clearfix"><div id="ui-dialog-buttonset" class="ui-dialog-buttonset"></div></div></div><div class="ui-dialog-foot"></div></div>');
        var uiDialog = $('#ui-dialog', wnd.document).css('width', ps.width).hide();
        var dialogTitle = $('#ui-dialog .ui-dialog-titlebar', wnd.document);
        var dialogContent = $('#ui-dialog .ui-dialog-content', wnd.document);
        var dialogButtonSet = $('#ui-dialog-buttonset', wnd.document);
        $('body', wnd.document).append('<div id="ui-widget-overlay" class="ui-widget-overlay" style="z-index: 99999;"></div>');
        var dialogOverlay = $('#ui-widget-overlay', wnd.document).css('width', $(wnd.document).width()).css('height', $(wnd.document).height()).hide();
        $('#ui-dialog-title', wnd.document).text($(this).attr('title') != '' ? $(this).attr('title') : '提示XXX');
        if (!ps.showTitle) {
            $('.ui-dialog-titlebar', wnd.document).css('display', 'none');
        }
        dialogContent.html(this.html());
        $.each(ps.buttons, function(i, n){
            //iframe中的ie6不支持追加jquery对象
            //$('<button class="ui-button"></button>').bind('click', n).append($('<span class="ui-button-text"></span>').text(i)).appendTo(dialogButtonSet);
            var obj = $('<button class="ui-button"><span class="ui-button-text">' + i +  '</span></button>');
            $('#ui-dialog-buttonset', wnd.document).append('<button class="ui-button"><span class="ui-button-text">' + i +  '</span></button>');
            $('#ui-dialog-buttonset', wnd.document).children(':last').bind('click', n);
        });

        //居中显示
        var rect = getXY(wnd);
        //console.info("contentWidth=" + rect.contentWidth +",contentHeight=" + rect.contentHeight +",width="+rect.width + ",height=" + rect.height +",scrollX=" +rect.scrollX +",scrollY=" +rect.scrollY);
        //$(document).width()=2010,$(document).height()=1249,$(window).width()=1351,$(window).height()=619
        //console.info("$(document).width()="+$(document).width()+",$(document).height()="+$(document).height()+",$(window).width()="+$(window).width()+",$(window).height()="+$(window).height());
        uiDialog.css('left',  rect.scrollX + (rect.width - uiDialog.width())/2);
        uiDialog.css('top', rect.scrollY + (rect.height - uiDialog.height())/3);
        dialogOverlay.show();
        uiDialog.show();
        //设置第一个按钮焦点
        $('#ui-dialog button:first', wnd.document).focus();
        if (ps.bgiframe && $.fn.bgiframe) {
			//dialogOverlay.bgiframe();
		}

        //关闭对话框
        var close = function(){
            uiDialog.remove();
            dialogOverlay.remove();
        };

        //拖动处理
        ps.limit = {
            minWidth: 0,
            maxWidth: rect.scrollX + rect.width - uiDialog.width(),
            minHeight: 0,
            maxHeight: rect.scrollY + rect.height - uiDialog.height()
        };
        //console.info(ps.limit);
        var updatePosition = function(left,top){
            uiDialog.css('left', left).css('top', top);
        };
        var dragDrop = {
            drag: function(e){
                var curLeft = Math.min(Math.max(e.data.left + e.pageX - e.data.pageX, ps.limit.minWidth), ps.limit.maxWidth);
                var curTop = Math.min(Math.max(e.data.top + e.pageY - e.data.pageY, ps.limit.minHeight), ps.limit.maxHeight);
                //console.info(curLeft + "," + curTop);
                updatePosition(curLeft, curTop);
            },
            drop: function(e){
                //console.info("释放了鼠标，结束位置：" + parseInt(uiDialog.css('left')) + "," + parseInt(uiDialog.css('top')));
                uiDialog.removeClass('uiDialog-hover');
                $(wnd.document).unbind('mousemove', dragDrop.drag).unbind('mouseup', dragDrop.drop);
            }
        };
        dialogTitle.bind('mousedown', function(e){
            //console.info("按下了鼠标，记住开始位置：" + parseInt(uiDialog.css('left')) + "," + parseInt(uiDialog.css('top')));
            $(this).addClass('ui-dialog-hover');
            var dragData = {
                left: parseInt(uiDialog.css('left')),
                top: parseInt(uiDialog.css('top')),
                pageX: e.pageX,
                pageY: e.pageY
            }
            //console.info(dragData.left + "," + dragData.top + "," + dragData.pageX + "," + dragData.pageY);
            $(wnd.document).bind('mousemove', dragData, dragDrop.drag).bind('mouseup', dragData, dragDrop.drop);
        });
    };

    //再封装一下
    $.alert = function(msg, callback){
        $('<div title="温馨提示">'+msg+'</div>').dialog({
            bgiframe:true,
            width:354,
            buttons:{
                "确定":function(){
                    $(this).dialog("close", window.parent);       //关闭这个对话框
                    if (typeof(callback) == "function") {
                        callback();
                    }
                }
            }
        }, window.parent);
    };
    $.confirm = function(msg,callback){
        $('<div title="温馨提示">'+msg+'</div>').dialog({
            bgiframe:true,
            width:354,
            buttons:{
                "确定":function(){
                    $(this).dialog("close", window.parent);       //关闭这个对话框
                    if (typeof(callback) == "function") {
                        callback();
                    }
                },
                "取消": function(){
                    $(this).dialog("close", window.parent);       //关闭这个对话框
                }
            }
        }, window.parent);
    }
})(jQuery);