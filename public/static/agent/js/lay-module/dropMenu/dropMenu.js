 /* 
    Authors: Jeff Lai,
    option:{
        type:0, //0单击和鼠标经过时都生效(默认)，1点击时，2鼠标经过时
        elem:'#id', //$("#id")        
        width:"150px",
        css:{"background":"red"},
        location:"c", //下拉菜单依靠位置，c居中、l左、r右
        align: "c", //菜单内容文本对齐方式，c居中、l左、r右
        eventName:'lay-event', //事件标签名，默认lay-event
        eventNames:'lay-events', //动态事件标签名，默认lay-events
        data:[{title:'',event:'e1',icon:'layui-icon-search',isShow:true}], //菜单数据及事件
        event:{ e1:function(obj){}, e2:function(obj){} }, //事件处理
        menuClick(menuObj, title, event, value) { return false; }, //(新增)菜单点击回调事件，返回false不关闭菜单面板，menuObj点击对象、title菜单标题、event事件名称
        done:function(dropM){ } //菜单渲染完成后触发事件，dropM下拉菜单对象
    } 
 */
layui.define(['jquery','util'], function (exports) {
    var $ = layui.$,
        util = layui.util;
    var basedir = layui.cache.base;
    if (basedir.substr(basedir.length - 1, 1) != "/")
        basedir += "/";
    layui.link(basedir + "dropMenu/dropMenu.css");

    function hide($menu) {
        $menu.css({
            height: 0,
            opacity: 0,
        });
    }
    function show($menu) {
        $menu.css({
            height: "auto",
            opacity: 1,
            "z-index": 99999999,
        });
    }

    function clickopera(option){
        option.ele.click(function (e) {
            e.preventDefault();
            var $menu = $(this).next();
            if ($menu.css("opacity") == 1) {
                hide($menu);
            } else {
                hide($(".layui-dropMenu"));
                show($menu);
                offset($(this), $menu, option);
            }
        });

        $(".layui-table-body").scroll(function () {
            hide($(".layui-dropMenu"));
        });

        $(document).on("click", function (e) {
            if ($(e.target).next().hasClass("layui-dropMenu") || $(e.target).parent().hasClass("layui-dropMenu"))
                return;
            hide($(".layui-dropMenu"));
        });
    }

    function mouseopera(option){
        option.ele.parent().mouseover(
            function () {
                var $menu = $(this).children().last();
                show($menu);
                offset($(this).children().first(), $menu, option);
            }
        ).mouseout(function () {
            var $menu = $(this).children().last();
            hide($menu);
        });
    }

    function offset(ele, $menu, option){
        var tt = 0;
        if ($(window).height() < (ele.offset().top + $menu.height() + 20))
            tt = ele.offset().top - $menu.height() - $(document).scrollTop();
        else
            tt = ele.offset().top + ele.height() - $(document).scrollTop();

        var ll = 0;
        if (option.location == "c")
            ll = ele.offset().left - ($menu.width() / 3);
        else if (option.location == "r")
            ll = ele.offset().left;
        else if (option.location == "l")
            ll = ele.offset().left - $menu.width() + ele.width() + 6;

        $menu.css({
            top: tt,
            left: ll
        });
    }

    function creatediv(option){
        // console.time("creatediv");
        if (option.ele.attr(option.eventNames)) {            
            $.each(option.ele, function (i, el) {
               var html = '';
                var events = $(el).attr(option.eventNames).split(",");
                var data = $.grep(option.data, function (v, i) { return $.inArray(v.event, events) != -1; });
                $.each(data, function (i, v) {
                    if (v.isShow == false)
                        return;
                    if (v.type == "hr"){
                        html += `<li class="hr"></li>`;
                        return;
                    }
                    var val = v.value ? `value="${v.value}"` : ``;
                    if (v.icon)
                        html += `<li ${option.eventName}="${v.event}" ${val}><i class=" ${v.icon}"></i> ${v.title}</li>`;
                    else
                        html += `<li ${option.eventName}="${v.event}" ${val}>${v.title}</li>`;
                    
                })
                html = `<ul class="layui-dropMenu">${html}</ul>`;

                $(el).wrap(`<div class="layui-inline"></div>`);
                $(el).after(html);
            });
            
        }
        else {
            var html = '';
            $.each(option.data, function (i, v) {
                if (v.isShow == false)
                    return;
                if (v.type == "hr"){
                    html += `<li class="hr"></li>`;
                    return;
                }
                var val = v.value ? `value="${v.value}"` : ``;
                if (v.icon)
                    html += `<li ${option.eventName}="${v.event}" ${val}><i class=" ${v.icon}"></i> ${v.title}</li>`;
                else
                    html += `<li ${option.eventName}="${v.event}" ${val}>${v.title}</li>`;
            })
            html = `<ul class="layui-dropMenu">${html}</ul>`;
            option.ele.wrap(`<div class="layui-inline"></div>`);
            option.ele.after(html);
        }
        // console.timeEnd("creatediv");
    }

    function menuClick(option){
        option.ele.parent().find(".layui-dropMenu").on("click", "li", function (obj) {
            var bo = option.menuClick($(this), $(this).text(), $(this).attr(defConfig.eventName), $(this).attr("value"));
            if (bo == false)
                return;
            hide(option.ele.next());
        });
    }

    //默认配置
    var defConfig = {
        type: 0,
        location: "c",
        align: "l",
        eventName: "lay-event",
        eventNames: "lay-events",
    };

    var obj = {
        config: defConfig,
        render: function (option) {
            if (!option.elem) {
                console.error("dropMenu elem is empty");
                return;
            }
            if (typeof option.elem == "string")
                option.ele = $(option.elem);
            else
                option.ele = option.elem;

            option = $.extend({}, defConfig, option);

            var isShowDatas = $.map(option.data, function (v, i) { return v.isShow != false ? v : null; });
            if (isShowDatas.length == 0){
                // option.ele.hide();
                return;
            }

            creatediv(option);

            if (option.type == 0) {
                clickopera(option);
                mouseopera(option);
            } else if (option.type == 1) {
                clickopera(option);
            } else if (option.type == 2) {
                mouseopera(option);
            }
            
            if(option.width)
                option.ele.parent().find(".layui-dropMenu").width(option.width);

            if (option.align && option.align != "c"){
                if (option.align == "l")
                    option.ele.parent().find(".layui-dropMenu").css({ textAlign: "left" });
                if (option.align == "r")
                    option.ele.parent().find(".layui-dropMenu").css({ textAlign: "right" });
            }
            
            if(option.css)
                option.ele.parent().find(".layui-dropMenu").css(option.css);

            if(option.event)
              util.event(option.eventName, option.event);

            if(option.menuClick)
                menuClick(option);

            if(option.done)
              option.done(option.ele.parent().find(".layui-dropMenu"));
        }
    };
    exports('dropMenu', obj);
});