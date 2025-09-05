layui.use('layer', function(){
    var $ = layui.jquery;
    $(document).on('click', '.checkPictureByImg', function () {
        let src = $(this).attr('src');
        parent.layer.photos({
            photos: {
                "title": "查看图片" //相册标题
                , "data": [{
                    "src": src //原图地址
                }]
            }
            , shade: 0.01
            , closeBtn: 1
            , anim: 5
        });
    });
});
