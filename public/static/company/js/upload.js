(function ($) {
    $.fn.extend({
        /**
         * 选择图片文件
         */
        selectImages: function (option) {
            $(this).on('click', function () {
                let $this = $(this);
                let layData = $this.attr('lay-data');
                if (layData) {
                    layData = eval('(' + layData + ')');
                } else {
                    layData = {field: $this.data('field')};
                }
                parent.layer.open({
                    type: 2,
                    title: '选择图片',
                    area: ['950px', '550px'],
                    content: selectUploadFileUrl + '?dir=' + layData.data.dir,
                    btn: ['确认', '取消'],
                    yes: function (index, layero) {
                        let uploadChooseData = $(layero).find("iframe")[0].contentWindow.uploadChooseData;
                        let data = [];

                        uploadChooseData.forEach(function (elem) {
                            data.push({id: elem.id, src: elem.file_path});
                        });
                        if (data.length <= 0) {
                            return parent.layer.msg('请选择图片');
                        }

                        if ("function" == typeof option.confirm) {
                            option.confirm(data, $this, layData);
                        }
                        parent.layer.close(index);
                    }
                });
            });
        }
    })
    // 在Jquery插件中使用FileLibrary对象
    $.fileLibrary = function (option) {
        parent.layer.open({
            type: 2,
            title: '选择图片',
            area: ['850px', '520px'],
            content: selectUploadFileUrl + '?dir=article',
            btn: ['确认', '取消'],
            yes: function (index, layero) {
                let uploadChooseData = $(layero).find("iframe")[0].contentWindow.uploadChooseData;
                let data = [];
                uploadChooseData.forEach(function (elem) {
                    data.push({id: elem.id, file_path: elem.file_path});
                });

                if (data.length <= 0) {
                    return parent.layer.msg('请选择图片');
                }

                if ("function" == typeof option.done) {
                    option.done(data);
                }
                parent.layer.close(index);
            }
        });
    };
})(jQuery);