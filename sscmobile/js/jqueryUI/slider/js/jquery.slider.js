/*! jQuery UI.slider - v1.0 - 2013-01-26
 * original by William
 * Copyright (c) 2013 AUTHORS.txt; Licensed MIT, GPL
 * Depends:
 * jQuery 1.4.1 or above
 */
(function($){
    $.fn.slider = function(settings){
        var ps = $.extend({
            initPosition: 0.5,    //0-1
            barCssName: 'defaultBar',
            completedCssName: 'completed',
            sliderCssName: 'slider',
            sliderHover: 'slider-hover',
            onChanging: function(percent){
                console.info("percent=" + percent);
            },
            onChanged: function(percent){
                console.info("percent=" + percent);
            }
        }, settings);

        ps.size = {barWidth: $(this).width(), sliderWidth: 5};
        var sliderBar = $('<div><div>&nbsp;</div><div>&nbsp;</div></div>').attr('class', ps.barCssName).css('width', ps.size.barWidth).bind('selectstart', function(){return false;}).appendTo($(this));
        //sliderBar[0].onselectstart = function(){return false;};   //不让滚动条选中
        var completed = sliderBar.find('div:eq(0)').attr('class', ps.completedCssName);
        var slider = sliderBar.find('div:eq(1)').attr('class', ps.sliderCssName).css('width', ps.size.sliderWidth);
        ps.limit = {min: 0, max: sliderBar.width() - slider.width()};
        var updateProgress = function(obj1, obj2, left){
            obj1.css('width', left);
            obj2.css('left', left);
        };
        updateProgress(completed, slider, ps.initPosition * ps.size.barWidth);
        ps.onChanged(parseInt(slider.css('left'))/ps.limit.max);

        var slide = {
            drag: function(e){
                var curLeft = Math.min(Math.max(e.data.left + e.pageX - e.data.pageX, ps.limit.min), ps.limit.max);
                //console.info(curLeft);
                updateProgress(completed, slider, curLeft);
                ps.onChanging(parseInt(slider.css('left'))/ps.limit.max);
            },
            drop: function(e){
                console.info("释放了鼠标，结束位置：" + parseInt(slider.css('left')));
                slider.removeClass('slider-hover');
                $(document).unbind('mousemove', slide.drag).unbind('mouseup', slide.drop);
                ps.onChanged(parseInt(slider.css('left'))/ps.limit.max);
            }
        };

        slider.bind('mousedown', function(e){
            console.info("按下了鼠标，开始位置：" + parseInt(slider.css('left')));
            $(this).addClass('slider-hover');
            var dragData = {
                left: parseInt($(this).css('left')),
                pageX: e.pageX
            }
//console.info(dragData.left);
            $(document).bind('mousemove', dragData, slide.drag).bind('mouseup', dragData, slide.drop);
        });
    };
})(jQuery);