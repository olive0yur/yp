(function ($) {
    $.fn.extend({
        /**
         * 选择省份
         */
        carSelectProvince: function (option) {
            let provinceTimer;
            let keyTimer;
            $(this).on('click', function () {
                var that = $(this);
                // console.log($(document).width())
                // let toppx = that.offset().top -$(document).scrollTop() + 40
                let leftpx = that.offset().left
                let toppx = that.offset().top - $(document).scrollTop() - 120
                if (toppx <= 0) {
                    toppx = that.offset().top - $(document).scrollTop() + 40
                }
                let width = that.width()
                width = width < 190 ? 190 : width
                width = 260
                if($(document).width() - leftpx < width) {
                    leftpx = $(document).width()-width-30
                }
                // console.log(width)
                console.log(leftpx)
                if (that.siblings('div[lay-event="province-select"]') || that.siblings('div[lay-event="key-select"]')) {
                    $(this).siblings('div[lay-event="province-select"]').remove();
                    $(this).siblings('div[lay-event="key-select"]').remove();
                }
                // 输入小写字母转为大写
                that.css('text-transform', 'uppercase');
                let provinces = ["京", "沪", "浙", "苏", "粤", "鲁", "晋", "冀", "豫", "川", "渝", "辽", "吉", "黑", "皖", "鄂", "津", "贵", "云", "桂", "琼", "青", "新", "藏", "蒙", "宁", "甘", "陕", "闽", "赣", "湘"];
                let keyNums = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '港', '澳', '学', '领'];

                let provinceSelectContent = ['<div lay-event="province-select" style="background-color:#f8f7f7;padding:5px;position: fixed;top:' + toppx + 'px;left:' + leftpx + 'px;z-index: 9999;width: ' + width + 'px">'];
                provinceSelectContent.push('<ul>')
                provinces.forEach((item) => {
                    provinceSelectContent.push('<li style="float:left;width: 25px;height: 25px;border:1px solid #837d7d;text-align: center;line-height: 25px;margin:0px -1px -1px 0px ;cursor: pointer">' + item + '</li>')
                })
                provinceSelectContent.push('</ul></div>')
                let keySelectContent = ['<div lay-event="key-select" style="background-color:#f8f7f7;padding:5px;position: fixed;top:' + toppx + 'px;left:' + leftpx + 'px;z-index: 9999;width: ' + width + 'px">'];
                keySelectContent.push('<ul>')
                keyNums.forEach((item) => {
                    keySelectContent.push('<li style="float:left;width: 25px;height: 25px;border:1px solid #837d7d;text-align: center;line-height: 25px;margin:0px -1px -1px 0px ;cursor: pointer">' + item + '</li>')
                })
                keySelectContent.push('</ul></div>')

                that.after(provinceSelectContent.join(''))

                that.next().find('li').on('click', function () {
                    let province = $(this).html();
                    let keys = []
                    that.next().remove();
                    that.val(province);
                    that.after(keySelectContent.join(''))
                    that.next().find('li').on('click', function () {
                        let val = $(this).html();
                        keys.push(val)
                        if (keys.length > 7) {
                            layer.msg('当前已输入' + (keys.length + 1) + '位')
                        }
                        that.val(province + keys.join(''));
                        that.focus();
                    });
                    $(that.next()).on('mouseover', function () {
                        clearTimeout(keyTimer)
                    });
                    $(that.next()).on('mouseout', function () {
                        keyTimer = setTimeout(() => {
                            $(this).remove();
                        }, 100);
                    });
                });
                $(that.next()).on('mouseover', function () {
                    clearTimeout(provinceTimer)
                });
                $(that.next()).on('mouseout', function () {
                    provinceTimer = setTimeout(() => {
                        $(this).remove();
                    }, 100);
                });
            });
            $(this).parent().on('mouseenter', 'li', function () {
                $(this).css('background-color', '#5B9DF5')
                $(this).css('color', 'white')
            }).on('mouseleave', 'li', function () {
                $(this).css('background-color', 'inherit')
                $(this).css('color', 'inherit')
            });
            $(this).on('keyup', function () {
                $(this).siblings('div[lay-event="province-select"]').remove();
                $(this).siblings('div[lay-event="key-select"]').remove();
            });
            $(this).on('mouseout', function () {
                provinceTimer = setTimeout(() => {
                    $(this).siblings('div[lay-event="province-select"]').remove();
                }, 100);
            });
        }
    })
})(jQuery);