/*!
 *  Document   : form.js
 *  Author     : caiweiming <314013107@qq.com>
 *  Description: 表单构建器
 */
jQuery(document).ready(function() {
    // 文件上传集合
    var webuploader = [];
    // 当前上传对象
    var curr_uploader = {};
    // editordm编辑器集合
    var editormds   = {};
    // ueditor编辑器集合
    var ueditors    = {};
    // wangeditor编辑器集合
    var wangeditors = {};
    // 当前图标选择器
    var curr_icon_picker;
    var layer_icon;

    // 打开图标选择器
    $('.js-icon-picker').click(function(){
        curr_icon_picker = $(this);
        var icon_input = curr_icon_picker.find('.icon_input');
        if (icon_input.is(':disabled')) {
            return;
        }
        layer_icon = layer.open({
            type: 1,
            title: '图标选择器',
            area: ['90%', '90%'],
            scrollbar: false,
            content: $('#icon_tab')
        });
    });

    // 开启图标搜索
    Dolphin.iconSearch();

    // 选择图标
    $('.js-icon-content li').click(function () {
        var icon = $(this).find('i').attr('class');
        curr_icon_picker.find('.input-group-addon.icon').html('<i class="'+icon+'"></i>');
        curr_icon_picker.find('.icon_input').val(icon);
        layer.close(layer_icon);
    });

    // 清空图标
    $('.delete-icon').click(function(event){
        event.stopPropagation();
        if ($(this).prev().is(':disabled')) {
            return;
        }
        $(this).prev().val('');
        $(this).prev().prev().html('<i class="fa fa-fw fa-plus-circle"></i>');
    });

    // 百度地图
    $('.js-bmap').each(function () {
        var $self         = $(this);
        var map_canvas    = $self.find('.bmap').attr('id');
        var address       = $self.find('.bmap-address');
        var address_id    = address.attr('id');
        var map_point     = $self.find('.bmap-point');
        var search_result = $self.find('.searchResultPanel');
        var point_lng     = 116.331398;
        var point_lat     = 39.897445;
        var map_level     = $self.data('level');

        // 百度地图API功能
        var map = new BMap.Map(map_canvas);
        //开启鼠标滚轮缩放
        map.enableScrollWheelZoom(true);
        // 左上角，添加比例尺
        var top_left_control = new BMap.ScaleControl({anchor: BMAP_ANCHOR_TOP_LEFT});
        // 左上角，添加默认缩放平移控件
        var top_left_navigation = new BMap.NavigationControl();
        map.addControl(top_left_control);
        map.addControl(top_left_navigation);

        // 智能搜索
        var local = new BMap.LocalSearch(map, {
            onSearchComplete: function () {
                var point = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
                map.centerAndZoom(point, map_level);
                // 创建标注
                create_mark(point);
            }
        });

        // 创建标注
        var create_mark = function (point) {
            // 清空所有标注
            map.clearOverlays();
            var marker = new BMap.Marker(point);  // 创建标注
            map.addOverlay(marker);    //添加标注
            marker.setAnimation(BMAP_ANIMATION_BOUNCE); //跳动的动画
            // 写入坐标
            map_point.val(point.lng + "," + point.lat);
        };

        // 建立一个自动完成的对象
        var ac = new BMap.Autocomplete({
            "input" : address_id,
            "location" : map
        });
        // 鼠标放在下拉列表上的事件
        ac.addEventListener("onhighlight", function(e) {
            var str = "";
            var _value = e.fromitem.value;
            var value = "";
            if (e.fromitem.index > -1) {
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }
            str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;

            value = "";
            if (e.toitem.index > -1) {
                _value = e.toitem.value;
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }
            str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
            search_result.html(str);
        });


        // 鼠标点击下拉列表后的事件
        var myValue;
        ac.addEventListener("onconfirm", function(e) {
            var _value = e.item.value;
            myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            search_result.html("onconfirm<br />index = " + e.item.index + "<br />myValue = " + myValue);

            local.search(myValue);
        });

        // 监听点击地图时间
        map.addEventListener("click", function (e) {
            // 创建标注
            create_mark(e.point);
        });

        if (map_point.val() != '') {
            var curr_point = map_point.val().split(',');
            point_lng = curr_point[0];
            point_lat = curr_point[1];
        } else if(address.val() != '') {
            local.search(address.val());
        } else {
            // 根据ip获取当前城市，并定位到当前城市
            var myCity = new BMap.LocalCity();
            myCity.get(function (result) {
                var cityName = result.name;
                map.setCenter(cityName);
            });
        }

        // 初始化地图,设置中心点坐标和地图级别
        var point = new BMap.Point(point_lng, point_lat);
        map.centerAndZoom(point, map_level);
        if (map_point.val() != '') {
            // 创建标注
            create_mark(point);
        }
        if(address.val()!=''){
            ac.setInputValue(address.val())
        }
    });

    // 图片裁剪
    $('.js-jcrop-interface').each(function () {
        var jcrop_api         = '';
        var $self             = $(this);
        var $jcrop            = $self.find('.js-jcrop');
        var $options          = $jcrop.data('options') || {};
        var $thumb            = $jcrop.data('thumb');
        var $watermark        = $jcrop.data('watermark');
        var $jcrop_cut_btn    = $self.find('.js-jcrop-cut-btn');
        var $jcrop_upload_btn = $self.find('.js-jcrop-upload-btn');
        var $jcrop_file       = $self.find('.js-jcrop-file');
        var $jcrop_cut_info   = $self.find('.js-jcrop-cut-info');
        var $jcrop_preview    = $self.find('.jcrop-preview');
        var $jcrop_input      = $self.find('.js-jcrop-input');
        var $remove_picture   = $self.find('.remove-picture');
        var $thumbnail        = $self.find('.thumbnail');
        var $modal            = $self.find('.modal-popin');
        var $pic_height       = '';

        // 设置预览图监听
        $options.onChange    = showPreview;
        $options.onSelect    = showPreview;
        $options.boxWidth    = 750;
        $options.boxHeight   = 750;
        $options.saveWidth   = $options.saveWidth || null;
        $options.saveHeight  = $options.saveHeight || null;
        $options.aspectRatio = $options.aspectRatio || ($options.saveWidth / $options.saveHeight);

        // 点击上传按钮，选择图片
        $jcrop_upload_btn.click(function () {
            $jcrop_file.trigger('click');
        });

        // 加载图片（用于判断图片是否加载完毕）
        function loadImage(url, callback) {
            var img = new Image(); //创建一个Image对象，实现图片的预下载
            img.src = url;

            if(img.complete) { // 如果图片已经存在于浏览器缓存，直接调用回调函数
                callback.call(img);
                return; // 直接返回，不用再处理onload事件
            }
            img.onload = function () { //图片下载完毕时异步调用callback函数。
                callback.call(img);//将回调函数的this替换为Image对象
            };
        }

        // 实时显示预览图
        function showPreview(coords)
        {
            var ratio = coords.w / coords.h; // 选区比例
            var rx,ry;
            var preview_width  = '';
            var preview_height = '';

            if ((100 / ratio) > $pic_height) {
                preview_width  = $pic_height * ratio;
                preview_height = $pic_height;
            } else {
                preview_width  = 100;
                preview_height = 100 / ratio;
            }

            rx = preview_width / coords.w;
            ry = (preview_width / ratio) / coords.h;

            if (jcrop_api) {
                $jcrop_preview.css({
                    width: Math.round(rx * jcrop_api.ui.stage.width) + 'px',
                    height: Math.round(ry * jcrop_api.ui.stage.height) + 'px',
                    marginLeft: '-' + Math.round(rx * coords.x) + 'px',
                    marginTop: '-' + Math.round(ry * coords.y) + 'px'
                }).parent().css({
                    width: preview_width + 'px',
                    height: preview_height + 'px'
                });
            }

            var jcrop_info = [coords.w, coords.h, coords.x, coords.y, $options.saveWidth, $options.saveHeight];
            $jcrop_cut_info.val(jcrop_info.join(','));
        }

        // 选择图片后
        $jcrop_file.change(function () {
            var files = this.files;
            var file;
            if (files && files.length) {
                file = files[0];
                if (/^image\/\w+$/.test(file.type)) {
                    // 创建FormData对象
                    var data = new FormData();
                    // 为FormData对象添加数据
                    data.append('file', file);
                    Dolphin.loading();
                    // 上传图片
                    $.ajax({
                        url: dolphin.jcrop_upload_url,
                        type: 'POST',
                        cache: false,
                        contentType: false,    //不可缺
                        processData: false,    //不可缺
                        data: data,
                        success: function (res) {
                            if (res.code == 1) {
                                $jcrop.attr('src', res.src).data('id', res.id).show();
                                $jcrop_preview.attr('src', res.src).parent().show();
                                loadImage(res.src, function () {
                                    Dolphin.loading('hide');
                                    if (jcrop_api != '') {
                                        jcrop_api.destroy();
                                        $.Jcrop.component.DragState.prototype.touch = null;
                                    }
                                    $jcrop.Jcrop($options, function () {
                                        jcrop_api   = this;
                                        $pic_height = Math.round(jcrop_api.getContainerSize()[1]);
                                        $modal.modal('show');
                                    });
                                });
                            } else {
                                Dolphin.notify('上传失败，请重新上传', 'warning');
                            }
                        }
                    }).fail(function(res) {
                        Dolphin.loading('hide');
                        Dolphin.notify($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                    });
                    $jcrop_file.val('');
                } else {
                    Dolphin.notify('请选择一张图片', 'warning');
                }
            }
        });

        // 关闭裁剪框
        $modal.on('hidden.bs.modal', function (e) {
            $jcrop_cut_info.val('');
        });

        // 删除图片
        $remove_picture.click(function () {
            $(this).parent().hide();
            $jcrop_input.val('');
        });

        // 裁剪图片
        $jcrop_cut_btn.click(function () {
            var $cut_value = $jcrop_cut_info.val();
            if ($jcrop.attr('src') == '') {
                Dolphin.notify('请上传图片', 'danger');
                return false;
            }
            if ($cut_value != '') {
                var $data = {
                    path: $jcrop_preview.attr('src'),
                    cut: $cut_value,
                    thumb: $thumb,
                    watermark: $watermark
                };
                Dolphin.loading();
                $.ajax({
                    url: dolphin.jcrop_upload_url,
                    type: 'POST',
                    dataType: 'json',
                    data: $data
                })
                .done(function(res) {
                    Dolphin.loading('hide');
                    if (res.code == '1') {
                        $thumbnail.show().find('img').attr('src', res.thumb || res.src).attr('data-original', res.src);
                        $jcrop_input.val(res.id);
                        $jcrop_cut_info.val('');
                        $modal.modal('hide');
                    } else {
                        Dolphin.notify(res.msg, 'danger');
                    }
                })
                .fail(function(res) {
                    Dolphin.loading('hide');
                    Dolphin.notify($(res.responseText).find('h1').text() || '请求失败~', 'danger');
                });
            } else {
                Dolphin.notify('请选择要裁剪的大小', 'warning');
            }
        });

        // 查看大图
        Dolphin.viewer();
    });

    // editormd编辑器
    $('.js-editormd').each(function () {
        var editormd_name = $(this).attr('name');
        var image_formats = $(this).data('image-formats') || [];
        var watch         = $(this).data('watch');

        editormds[editormd_name] = editormd(editormd_name, {
            height: 500, // 高度
            placeholder: '海豚PHP，为提升开发效率而生！！',
            watch : watch,
            searchReplace : true,
            toolbarAutoFixed: false, // 取消工具栏固定
            path : dolphin.editormd_mudule_path, // 用于自动加载其他模块
            codeFold: true, // 开启代码折叠
            dialogLockScreen : false, // 设置弹出层对话框不锁屏
            imageUpload : true, // 开启图片上传
            imageFormats : image_formats, // 允许上传的图片后缀
            imageUploadURL : dolphin.editormd_upload_url,
            toolbarIcons : function() {
                return [
                    "undo", "redo", "|",
                    "bold", "del", "italic", "quote", "|",
                    "h1", "h2", "h3", "h4", "h5", "h6", "|",
                    "list-ul", "list-ol", "hr", "|",
                    "link", "reference-link", "image", "code", "preformatted-text", "code-block", "datetime", "html-entities", "pagebreak", "|",
                    "goto-line", "watch", "preview", "fullscreen", "clear", "search", "|",
                    "help", "info"
                ]
            }
        });
    });

    // ueditor编辑器
    $('.js-ueditor').each(function () {
        var ueditor_name = $(this).attr('name');
        ueditors[ueditor_name] = UE.getEditor(ueditor_name, {
            initialFrameHeight:400,  //初始化编辑器高度,默认320
            autoHeightEnabled:false,  //是否自动长高
            maximumWords: 50000, //允许的最大字符数
            serverUrl: dolphin.ueditor_upload_url
        });
    });

    // wangeditor编辑器
    $('.js-wangeditor').each(function () {
        var wangeditor_name = $(this).attr('name');
        var imgExt = $(this).data('img-ext') || '';

        // 关闭调试信息
        wangEditor.config.printLog = false;
        // 实例化编辑器
        wangeditors[wangeditor_name] = new wangEditor(wangeditor_name);
        // 上传图片地址
        wangeditors[wangeditor_name].config.uploadImgUrl = dolphin.wangeditor_upload_url;
        // 允许上传图片后缀
        wangeditors[wangeditor_name].config.imgExt = imgExt;
        // 配置文件名
        wangeditors[wangeditor_name].config.uploadImgFileName = 'file';
        // 去掉地图
        wangeditors[wangeditor_name].config.menus = $.map(wangEditor.config.menus, function(item, key) {
            if (item === 'location') {
                return null;
            }
            return item;
        });
        // 添加表情
        wangeditors[wangeditor_name].config.emotions = {
            'default': {
                title: '默认',
                data: dolphin.wangeditor_emotions
            }
        };
        wangeditors[wangeditor_name].create();
    });

    // 注册WebUploader事件，实现秒传
    if (window.WebUploader) {
        WebUploader.Uploader.register({
            "before-send-file": "beforeSendFile" // 整个文件上传前
        }, {
            beforeSendFile:function(file){
                var $li = $( '#'+file.id );
                var deferred = WebUploader.Deferred();
                var owner = this.owner;

                owner.md5File(file).then(function(val){
                    $.ajax({
                        type: "POST",
                        url: dolphin.upload_check_url,
                        data: {
                            md5: val
                        },
                        cache: false,
                        timeout: 10000, // 超时的话，只能认为该文件不曾上传过
                        dataType: "json"
                    }).then(function(res, textStatus, jqXHR){
                        if(res.code){
                            // 已上传，触发上传完成事件，实现秒传
                            deferred.reject();
                            curr_uploader.trigger('uploadSuccess', file, res);
                            curr_uploader.trigger('uploadComplete', file);
                        }else{
                            // 文件不存在，触发上传
                            deferred.resolve();
                            $li.find('.file-state').html('<span class="text-info">正在上传...</span>');
                            $li.find('.img-state').html('<div class="bg-info">正在上传...</div>');
                            $li.find('.progress').show();
                        }
                    }, function(jqXHR, textStatus, errorThrown){
                        // 任何形式的验证失败，都触发重新上传
                        deferred.resolve();
                        $li.find('.file-state').html('<span class="text-info">正在上传...</span>');
                        $li.find('.img-state').html('<div class="bg-info">正在上传...</div>');
                        $li.find('.progress').show();
                    });
                });
                return deferred.promise();
            }
        });
    }

    // 文件上传
    $('.js-upload-file,.js-upload-files').each(function () {
        var $input_file       = $(this).find('input');
        var $input_file_name  = $input_file.attr('name');
        // 是否多文件上传
        var $multiple         = $input_file.data('multiple');
        // 允许上传的后缀
        var $ext              = $input_file.data('ext');
        // 文件限制大小
        var $size             = $input_file.data('size');
        // 文件列表
        var $file_list        = $('#file_list_' + $input_file_name);

        // 实例化上传
        var uploader = WebUploader.create({
            // 选完文件后，是否自动上传。
            auto: true,
            // 去重
            duplicate: true,
            // swf文件路径
            swf: dolphin.WebUploader_swf,
            // 文件接收服务端。
            server: dolphin.file_upload_url,
            // 选择文件的按钮。可选。
            // 内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: {
                id: '#picker_' + $input_file_name,
                multiple: $multiple
            },
            // 文件限制大小
            fileSingleSizeLimit: $size,
            // 只允许选择文件文件。
            accept: {
                title: 'Files',
                extensions: $ext
            }
        });

        // 当有文件添加进来的时候
        uploader.on( 'fileQueued', function( file ) {
            var $li = '<li id="' + file.id + '" class="list-group-item file-item">' +
                '<span class="pull-right file-state"><span class="text-info"><i class="fa fa-sun-o fa-spin"></i> 正在读取文件信息...</span></span>' +
                '<i class="fa fa-file"></i> ' +
                file.name +
                ' [<a href="javascript:void(0);" class="download-file">下载</a>] [<a href="javascript:void(0);" class="remove-file">删除</a>]' +
                '<div class="progress progress-mini remove-margin active" style="display: none"><div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div></div>'+
                '</li>';

            if ($multiple) {
                $file_list.append($li);
            } else {
                $file_list.html($li);
                // 清空原来的数据
                $input_file.val('');
            }

            // 设置当前上传对象
            curr_uploader = uploader;
        });

        // 文件上传过程中创建进度条实时显示。
        uploader.on( 'uploadProgress', function( file, percentage ) {
            var $percent = $( '#'+file.id ).find('.progress-bar');
            $percent.css( 'width', percentage * 100 + '%' );
        });

        // 文件上传成功
        uploader.on( 'uploadSuccess', function( file, response ) {
            var $li = $( '#'+file.id );
            if (response.code) {
                if ($multiple) {
                    if ($input_file.val()) {
                        $input_file.val($input_file.val() + ',' + response.id);
                    } else {
                        $input_file.val(response.id);
                    }
                    $li.find('.remove-file').attr('data-id', response.id);
                } else {
                    $input_file.val(response.id);
                }
            }
            // 加入提示信息
            $li.find('.file-state').html('<span class="text-'+ response.class +'">'+ response.info +'</span>');
            // 添加下载链接
            $li.find('.download-file').attr('href', response.path);

            // 文件上传成功后的自定义回调函数
            if (window['dp_file_upload_success'] !== undefined) window['dp_file_upload_success']();
            // 文件上传成功后的自定义回调函数
            if (window['dp_file_upload_success_'+$input_file_name] !== undefined) window['dp_file_upload_success_'+$input_file_name]();
        });

        // 文件上传失败，显示上传出错。
        uploader.on( 'uploadError', function( file ) {
            var $li = $( '#'+file.id );
            $li.find('.file-state').html('<span class="text-danger">服务器发生错误~</span>');

            // 文件上传出错后的自定义回调函数
            if (window['dp_file_upload_error'] !== undefined) window['dp_file_upload_error']();
            // 文件上传出错后的自定义回调函数
            if (window['dp_file_upload_error_'+$input_file_name] !== undefined) window['dp_file_upload_error_'+$input_file_name]();
        });

        // 文件验证不通过
        uploader.on('error', function (type) {
            switch (type) {
                case 'Q_TYPE_DENIED':
                    Dolphin.notify('文件类型不正确，只允许上传后缀名为：'+$ext+'，请重新上传！', 'danger');
                    break;
                case 'F_EXCEED_SIZE':
                    Dolphin.notify('文件不得超过'+ ($size/1024) +'kb，请重新上传！', 'danger');
                    break;
            }
        });

        // 完成上传完了，成功或者失败，先删除进度条。
        uploader.on( 'uploadComplete', function( file ) {
            setTimeout(function(){
                $('#'+file.id).find('.progress').remove();
            }, 500);

            // 文件上传完成后的自定义回调函数
            if (window['dp_file_upload_complete'] !== undefined) window['dp_file_upload_complete']();
            // 文件上传完成后的自定义回调函数
            if (window['dp_file_upload_complete_'+$input_file_name] !== undefined) window['dp_file_upload_complete_'+$input_file_name]();
        });

        // 删除文件
        $file_list.delegate('.remove-file', 'click', function(){
            if ($multiple) {
                var id  = $(this).data('id'),
                    ids = $input_file.val().split(',');

                if (id) {
                    for (var i = 0; i < ids.length; i++) {
                        if (ids[i] == id) {
                            ids.splice(i, 1);
                            break;
                        }
                    }
                    $input_file.val(ids.join(','));
                }
            } else {
                $input_file.val('');
            }
            $(this).closest('.file-item').remove();
        });

        // 将上传实例存起来
        webuploader.push(uploader);
    });

    // 图片上传
    $('.js-upload-image,.js-upload-images').each(function () {
        var $input_file       = $(this).find('input');
        var $input_file_name  = $input_file.attr('name');
        // 是否多图片上传
        var $multiple         = $input_file.data('multiple');
        // 允许上传的后缀
        var $ext              = $input_file.data('ext');
        // 图片限制大小
        var $size             = $input_file.data('size');
        // 缩略图参数
        var $thumb            = $input_file.data('thumb');
        // 水印参数
        var $watermark        = $input_file.data('watermark');
        // 图片列表
        var $file_list        = $('#file_list_' + $input_file_name);
        // 优化retina, 在retina下这个值是2
        var ratio             = window.devicePixelRatio || 1;
        // 缩略图大小
        var thumbnailWidth    = 100 * ratio;
        var thumbnailHeight   = 100 * ratio;
        // 实例化上传
        var uploader = WebUploader.create({
            // 选完图片后，是否自动上传。
            auto: true,
            // 去重
            duplicate: true,
            // 不压缩图片
            resize: false,
            compress: false,
            // swf图片路径
            swf: dolphin.WebUploader_swf,
            // 图片接收服务端。
            server: dolphin.image_upload_url,
            // 选择图片的按钮。可选。
            // 内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: {
                id: '#picker_' + $input_file_name,
                multiple: $multiple
            },
            // 图片限制大小
            fileSingleSizeLimit: $size,
            // 只允许选择图片文件。
            accept: {
                title: 'Images',
                extensions: $ext,
                mimeTypes: 'image/jpg,image/jpeg,image/bmp,image/png,image/gif'
            },
            // 自定义参数
            formData: {
                thumb: $thumb,
                watermark: $watermark
            }
        });

        // 当有文件添加进来的时候
        uploader.on( 'fileQueued', function( file ) {
            var $li = $(
                    '<div id="' + file.id + '" class="file-item js-gallery thumbnail">' +
                    '<img>' +
                    '<div class="info">' + file.name + '</div>' +
                    '<i class="fa fa-times-circle remove-picture"></i>' +
                    ($multiple ? '<i class="fa fa-fw fa-arrows move-picture"></i>' : '') +
                    '<div class="progress progress-mini remove-margin active" style="display: none">' +
                    '<div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>' +
                    '</div>' +
                    '<div class="file-state img-state"><div class="bg-info">正在读取...</div>' +
                    '</div>'
                ),
                $img = $li.find('img');

            if ($multiple) {
                $file_list.append( $li );
            } else {
                $file_list.html( $li );
                $input_file.val('');
            }

            // 创建缩略图
            // 如果为非图片文件，可以不用调用此方法。
            // thumbnailWidth x thumbnailHeight 为 100 x 100
            uploader.makeThumb( file, function( error, src ) {
                if ( error ) {
                    $img.replaceWith('<span>不能预览</span>');
                    return;
                }
                $img.attr( 'src', src );
            }, thumbnailWidth, thumbnailHeight );

            // 设置当前上传对象
            curr_uploader = uploader;
        });

        // 文件上传过程中创建进度条实时显示。
        uploader.on( 'uploadProgress', function( file, percentage ) {
            var $percent = $( '#'+file.id ).find('.progress-bar');
            $percent.css( 'width', percentage * 100 + '%' );
        });

        // 文件上传成功
        uploader.on( 'uploadSuccess', function( file, response ) {
            var $li = $( '#'+file.id );

            if (response.code) {
                if ($multiple) {
                    if ($input_file.val()) {
                        $input_file.val($input_file.val() + ',' + response.id);
                    } else {
                        $input_file.val(response.id);
                    }
                    $li.find('.remove-picture').attr('data-id', response.id);
                } else {
                    $input_file.val(response.id);
                }
            }

            $li.find('.file-state').html('<div class="bg-'+response.class+'">'+response.info+'</div>');
            $li.find('img').attr('data-original', response.path);
            // 上传成功后，再次初始化图片查看功能
            Dolphin.viewer();

            // 文件上传成功后的自定义回调函数
            if (window['dp_image_upload_success'] !== undefined) window['dp_image_upload_success']();
            // 文件上传成功后的自定义回调函数
            if (window['dp_image_upload_success_'+$input_file_name] !== undefined) window['dp_image_upload_success_'+$input_file_name]();
        });

        // 文件上传失败，显示上传出错。
        uploader.on( 'uploadError', function( file ) {
            var $li = $( '#'+file.id );
            $li.find('.file-state').html('<div class="bg-danger">服务器错误</div>');

            // 文件上传出错后的自定义回调函数
            if (window['dp_image_upload_error'] !== undefined) window['dp_image_upload_error']();
            // 文件上传出错后的自定义回调函数
            if (window['dp_image_upload_error_'+$input_file_name] !== undefined) window['dp_image_upload_error_'+$input_file_name]();
        });

        // 文件验证不通过
        uploader.on('error', function (type) {
            switch (type) {
                case 'Q_TYPE_DENIED':
                    Dolphin.notify('图片类型不正确，只允许上传后缀名为：'+$ext+'，请重新上传！', 'danger');
                    break;
                case 'F_EXCEED_SIZE':
                    Dolphin.notify('图片不得超过'+ ($size/1024) +'kb，请重新上传！', 'danger');
                    break;
            }
        });

        // 完成上传完了，成功或者失败，先删除进度条。
        uploader.on( 'uploadComplete', function( file ) {
            setTimeout(function(){
                $( '#'+file.id ).find('.progress').remove();
            }, 500);

            // 文件上传完成后的自定义回调函数
            if (window['dp_image_upload_complete'] !== undefined) window['dp_image_upload_complete']();
            // 文件上传完成后的自定义回调函数
            if (window['dp_image_upload_complete_'+$input_file_name] !== undefined) window['dp_image_upload_complete_'+$input_file_name]();
        });

        // 删除图片
        $file_list.delegate('.remove-picture', 'click', function(){
            $(this).closest('.file-item').remove();
            if ($multiple) {
                var ids = [];
                $file_list.find('.remove-picture').each(function () {
                    ids.push($(this).data('id'));
                });
                $input_file.val(ids.join(','));
            } else {
                $input_file.val('');
            }
            // 删除后，再次初始化图片查看功能
            Dolphin.viewer();
        });

        // 将上传实例存起来
        webuploader.push(uploader);

        // 如果是多图上传，则实例化拖拽
        if ($multiple) {
            $file_list.sortable({
                connectWith: ".uploader-list",
                handle: '.move-picture',
                stop: function () {
                    var ids = [];
                    $file_list.find('.remove-picture').each(function () {
                        ids.push($(this).data('id'));
                    });
                    $input_file.val(ids.join(','));
                    // 拖拽排序后，重新初始化图片查看功能
                    Dolphin.viewer();
                }
            }).disableSelection();
        }
    });

    // 图片相册
    Dolphin.viewer();

    // 排序
    $('.nestable').each(function () {
        $(this).nestable({maxDepth:1}).on('change', function(){
            var $items = $(this).nestable('serialize');
            var name = $(this).data('name');
            var value = [];
            for (var item in $items) {
                value.push($items[item].id);
            }
            if (value.length) {
                $('input[name='+name+']').val(value.join(','));
            }
        });
    });

    // 格式文本
    $('.js-masked').each(function () {
        var $format = $(this).data('format') || '';
        $(this).mask(String($format));
    });

    // 联动下拉框
    $('.select-linkage').change(function(){
        var self       = $(this), // 下拉框
            value      = self.val(), // 下拉框选中值
            ajax_url   = self.data('url'), // 异步请求地址
            param      = self.data('param'), // 参数名称
            next_items = self.data('next-items').split(','), // 下级下拉框的表单名数组
            next_item  = next_items[0]; // 下一级下拉框表单名

        // 下级联动菜单恢复默认
        if (next_items.length > 0) {
            for (var i = 0; i < next_items.length; i++) {
                $('select[name="'+ next_items[i] +'"]').html('<option value="">请选择：</option>');
            }
        }

        if (value != '') {
            Dolphin.loading();
            // 获取数据
            $.ajax({
                url: ajax_url,
                type: 'POST',
                dataType: 'json',
                data: param + "=" + value
            })
            .done(function(res) {
                Dolphin.loading('hide');
                if (res.code == '1') {
                    var list = res.list;
                    if (list) {
                        for (var item in list) {
                            var option = $('<option></option>');
                            option.val(list[item].key).html(list[item].value);
                            $('select[name="'+ next_item +'"]').append(option);
                        }
                    }
                } else {
                    Dolphin.notify(res.msg, 'danger');
                }
            })
            .fail(function(res) {
                Dolphin.loading('hide');
                Dolphin.notify($(res.responseText).find('h1').text() || '数据请求失败~', 'danger');
            });
        }
    });

    // 多级联动下拉框
    $('.select-linkages').change(function () {
        var self       = $(this), // 下拉框
            value      = self.val(), // 下拉框选中值
            token      = self.data('token'), // token
            pidkey     = self.data('pidkey') || 'pid',
            next_level = self.data('next-level'), // 下一级别
            next_level_id = self.data('next-level-id') || ''; // 下一级别的下拉框id

        // 下级联动菜单恢复默认
        if (next_level_id != '') {
            $('#' + next_level_id).html('<option value="">请选择：</option>');
            var has_next_level = $('#' + next_level_id).data('next-level-id');
            if (has_next_level) {
                $('#' + has_next_level).html('<option value="">请选择：</option>');
                has_next_level = $('#' + has_next_level).data('next-level-id');
                if (has_next_level) {
                    $('#' + has_next_level).html('<option value="">请选择：</option>');
                }
            }
        }

        if (value != '') {
            Dolphin.loading();
            // 获取数据
            $.ajax({
                url: dolphin.get_level_data,
                type: 'POST',
                dataType: 'json',
                data: {
                    token: token,
                    level: next_level,
                    pid: value,
                    pidkey: pidkey
                }
            })
            .done(function(res) {
                Dolphin.loading('hide');
                if (res.code == '1') {
                    var list = res.list;
                    if (list) {
                        for (var item in list) {
                            var option = $('<option></option>');
                            option.val(list[item].key).text(list[item].value);
                            $('#' + next_level_id).append(option);
                        }
                    }
                } else {
                    Dolphin.loading('hide');
                    Dolphin.notify(res.msg, 'danger');
                }
            })
            .fail(function(res) {
                Dolphin.loading('hide');
                Dolphin.notify($(res.responseText).find('h1').text() || '数据请求失败~', 'danger');
            });
        }
    });

    // 表单项依赖触发
    if (dolphin.triggers != '') {
        /* 依赖显示 */
        // 先隐藏依赖项
        var $field_hide   = dolphin.field_hide.split(',') || [];
        var $field_values = dolphin.field_values.split(',') || [];
        for (var index in $field_hide) {
            $('#form_group_'+$field_hide[index]).addClass('form_group_hide');
        }

        var $form_builder = $('.form-builder');

        $.each(dolphin.triggers, function (trigger, content) {
            $form_builder.delegate('[name='+ trigger +']', 'change', function (event, init) {
                var $trigger = $(this);
                var $value   = $trigger.val();

                $(content).each(function () {
                    var $self = $(this);
                    var $values  = $self[0].split(',') || [];
                    var $targets = $self[1].split(',') || [];

                    // 如果触发的元素是单选，且没有选中则设置值为空
                    if ($trigger.attr('type') == 'radio' && $trigger.is(':checked') == false) {
                        $value = '';
                    }

                    if ($.inArray($value, $values) >= 0) {
                        // 符合指定的值，显示对应的表单项
                        for (var index in $targets) {
                            // 如果不是该对象自身直接创建的属性（也就是该属//性是原型中的属性），则跳过显示
                            if (!$targets.hasOwnProperty(index)) {
                                continue;
                            }
                            $('#form_group_'+$targets[index]).removeClass('form_group_hide');
                        }
                    } else {
                        for (var item in $targets) {
                            if (!$targets.hasOwnProperty(item)) {
                                continue;
                            }

                            // 隐藏表单项
                            var $form_item = $('#form_group_'+$targets[item]).addClass('form_group_hide');

                            if (dolphin._field_clear[trigger] !== undefined && dolphin._field_clear[trigger] === 1) {
                                if ($.type(ueditors) == 'object' && ueditors[$targets[item]] != undefined) {
                                    // 清除百度编辑器内容
                                    ueditors[$targets[item]].ready(function(){
                                        ueditors[$targets[item]].execCommand("cleardoc");
                                    });
                                } else if ($.type(wangeditors) == 'object' && wangeditors[$targets[item]] != undefined) {
                                    // 清除wang编辑器内容
                                    wangeditors[$targets[item]].clear();
                                } else if ($.type(editormds) == 'object' && editormds[$targets[item]] != undefined) {
                                    // 清除markdown编辑器内容
                                    if (init) {
                                        continue;
                                    } else {
                                        editormds[$targets[item]].clear();
                                    }
                                }

                                // 清除表单内容
                                if ($form_item.find("[name='"+$targets[item]+"']").attr('type') == 'radio') {
                                    $form_item.find("[name='"+$targets[item]+"']:checked").prop('checked', false).trigger("change");
                                } else if ($form_item.find("[name='"+$targets[item]+"[]']").attr('type') == 'checkbox') {
                                    $form_item.find("[name='"+$targets[item]+"[]']:checked").prop('checked', false).trigger("change");
                                } else if ($form_item.find("[name='"+$targets[item]+"']").attr('data-ext') != undefined) {
                                    $form_item.find("[name='"+$targets[item]+"']").val(null);
                                } else {
                                    $form_item.find("[name^='"+$targets[item]+"']").val(null).trigger("change");
                                }

                                // 清除上传文件
                                $form_item.find('#file_list_'+$targets[item]).empty();

                                // 清空标签
                                if ($form_item.find('.js-tags-input').length) {
                                    $form_item.find('.js-tags-input').importTags('');
                                }
                            }
                        }
                    }
                });
            });

            // 有默认值时触发
            var trigger_value = '';
            if ($form_builder.find('[name='+ trigger +']').attr('type') == 'radio') {
                trigger_value = $form_builder.find('[name='+ trigger +']:checked').val() || '';
                if (trigger_value != '' && $.inArray(trigger_value, $field_values) >= 0) {
                    var $radio_id = $('.form-builder [name='+ trigger +']:checked').attr('id');
                    $('.form-builder #'+$radio_id).trigger("change", ['1']);
                }
            } else {
                trigger_value = $form_builder.find('[name='+ trigger +']').val() || '';
                if (trigger_value != '' && $.inArray(trigger_value, $field_values) >= 0) {
                    $('.form-builder [name='+ trigger +']').trigger("change");
                }
            }
        });
    }

    // 切换分组时，重新初始化上传组件
    $('.nav-tabs a').click(function () {
        $.each(webuploader, function(index, el){
            el.refresh();
        });
    });

    // 关闭弹窗按钮
    $('#close-pop').click(function () {
        // 获取窗口索引
        var index = parent.layer.getFrameIndex(window.name);
        parent.layer.close(index);
    });
});