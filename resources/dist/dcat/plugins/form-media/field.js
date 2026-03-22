/**
 * LakeFormMedia-field.js v1.0.18
 *
 * @create 2020-11-28
 * @author deatil
 */
$(function () {
    var LakeFormMedia = {
        init: function() {
            var thiz = this;
            
            // 刷新预览
            this.onEvent('change', '.lake-form-media-input', function() {
                thiz.refreshInputPreview(this);
            });
            
            // 拖拽排序
            this.onEvent('mouseenter', '.js-dragsort', function() {
                var showRowCont = $(this).parents(".lake-form-media-img-show-row");
                if (showRowCont.hasClass('bind-dragsort')) {
                    return ;
                }
                
                var mediaCont = $(this).parents('.lake-form-media');
                var name = mediaCont.data('name');
                
                showRowCont.dragsort({
                    itemSelector: 'div.lake-form-media-preview-item',
                    dragSelector: ".js-dragsort",
                    dragEnd: function () {
                        thiz.refreshInputString(name);
                    },
                    placeHolderTemplate: $('<div class="lake-form-media-preview-item" />'),
                    scrollSpeed: 15
                });
                showRowCont.addClass('bind-dragsort')
            });
            
            // 删除
            this.onEvent("click", ".lake-form-media-img-show-item-delete", function(){
                var $this = $(this);
                
                var mediaCont = $this.parents('.lake-form-media');
                var name = mediaCont.data('name');
                
                var mediaShowCont = mediaCont.find('.lake-form-media-img-show');
                
                var options = mediaCont.data('options');
                
                var limit = options.limit;
                
                // 可多选时
                var multiplechoice = options.multiplechoice
                
                var itemurl = $this.data('url');
                
                layer.confirm(thiz.lang("remove_tip", {
                    data: itemurl,
                }), {
                    icon: 3,
                    title: thiz.lang("system_tip"),
                }, function(index) {

                    // 不是多选时
                    if (multiplechoice != 1 && limit > 1) {
                        var itemIndex = mediaCont
                            .find('.lake-form-media-preview-item')
                            .index($this.parents('.lake-form-media-preview-item'));
                        
                        mediaCont.find('.lake-form-media-preview-item').eq(itemIndex).remove();
                    } else {
                        mediaCont.find('.lake-form-media-preview-item[data-src="' + itemurl + '"]').remove();
                    }
                    
                    thiz.refreshInputString(name);
                    
                    if (mediaShowCont.find('.lake-form-media-preview-item').length < 1) {
                        mediaShowCont.hide();
                    }
                    
                    // 关闭提示框
                    layer.close(index);
                });
                
                return 1;
            });
            
            // 弹出选择器
            this.onEvent('click', '.lake-form-media-btn-file', function (event) {
                event.preventDefault();
                event.stopPropagation();
                
                var modal = $(this);
                var modalId = modal.data("target");
                
                $("#" + modalId).remove();
                $("#" + modalId + "Body")
                    .clone(true, true)
                    .find(".modal")
                    .attr("id", modalId)
                    .appendTo('body');
                
                $("#" + modalId).modal({
                    show: true,
                    backdrop: 'static', 
                    keyboard: false
                });
                
                var title = modal.data('title');
                
                var mediaCont = $(this).parents('.lake-form-media');
                var name = mediaCont.data('name');
                
                var options = mediaCont.data('options');
                options = $.extend({}, options);
                
                var path = options.path;
                var uploadUrl = options.upload_url;
                var createFolderUrl = options.create_folder_url;
                
                var mediaModalCont = $("#" + modalId);
                var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
                mediaModalPageCont.data('current-page', 1);
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                mediaModalNavOlCont.data('current-path', path);
                
                if (uploadUrl.length <= 0) {
                    mediaModalCont.find('.lake-form-media-upload-label').addClass('hidden');
                }
                if (createFolderUrl.length <= 0) {
                    mediaModalCont.find('.lake-form-media-create-folder-label').addClass('hidden');
                }
                
                mediaModalCont.find('.modal-title').text(thiz.lang("select_type", {
                    "title": title,
                }));
                
                thiz.getdata(name, path, options);
            });
            
            // 关闭弹出的选择器
            this.onEvent('click', '.lake-form-media-close', function (event) {
                var modalId = $(this).data("modal");
                $("#" + modalId).modal("hide");
            });
            
            // 点击排序切换
            this.onEvent('click', ".lake-form-media-modal-order", function() {
                var order = $(this).data('order');
                
                if (order == 'name') {
                    $(this).data('order', 'time');
                    
                    $(this).find('.fa')
                        .removeClass('fa-sort-alpha-asc')
                        .addClass('fa-calendar-times-o');
                } else {
                    $(this).data('order', 'name');
                    
                    $(this).find('.fa')
                        .removeClass('fa-calendar-times-o')
                        .addClass('fa-sort-alpha-asc');
                }
                
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                var name = mediaCont.data('name');
                var path = mediaModalNavOlCont.data('current-path');
                var options = mediaCont.data('options');
                
                thiz.getdata(name, path, options)
            });
            
            // 双击复制文件名
            this.onEvent("dblclick", '.lake-form-media-row-col .row-title', function() {
                var data = $(this).text();
                
                thiz.copyData(data);
                
                return false;
            });
            
            // 点击文件夹
            this.onEvent('click', ".lake-form-media-dir-op", function() {
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                var path = $(this).data('path');
                
                var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
                mediaModalPageCont.data('current-page', 1);
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                mediaModalNavOlCont.data('current-path', path);
                
                var options = mediaCont.data('options');
                
                thiz.getdata(name, path, options)
            });
            
            // 点击 nav
            this.onEvent("click", ".lake-form-media-nav-li", function(){
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                var path = $(this).data('path');
                
                var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
                mediaModalPageCont.data('current-page', 1);
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                mediaModalNavOlCont.data('current-path', path);
                
                var options = mediaCont.data('options');
                
                thiz.getdata(name, path, options)
            });
            
            // 分页 - 上一页
            this.onEvent("click", '.lake-form-media-modal-prev-page', function() {
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                
                var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
                var currentPage = mediaModalPageCont.data('current-page');
                
                currentPage = parseInt(currentPage);
                if (currentPage > 1) {
                    currentPage -= 1;
                    mediaModalPageCont.data('current-page', currentPage);
                } else {
                    mediaModalPageCont.data('current-page', 1);
                }
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                var path = mediaModalNavOlCont.data('current-path');
                
                var options = mediaCont.data('options');
                
                thiz.getdata(name, path, options)
            });
            
            // 分页 - 下一页
            this.onEvent("click", '.lake-form-media-modal-next-page', function() {
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                
                var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
                var currentPage = mediaModalPageCont.data('current-page');
                var totalPage = mediaModalPageCont.data('total-page');
                
                currentPage = parseInt(currentPage);
                totalPage = parseInt(totalPage);
                if (currentPage < totalPage) {
                    currentPage += 1;
                    mediaModalPageCont.data('current-page', currentPage);
                } else {
                    mediaModalPageCont.data('current-page', totalPage);
                }
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                var path = mediaModalNavOlCont.data('current-path');
                
                var options = mediaCont.data('options');
                
                thiz.getdata(name, path, options)
            });
            
            // 页码提示
            this.onEvent('mouseover', '.lake-form-media-modal-prev-page,.lake-form-media-modal-next-page', function () {
                var pageCont = $(this).parents('.lake-form-media-modal-page');
                
                var currentPage = pageCont.data('current-page');
                var totalPage = pageCont.data('total-page');
                var pageSize = pageCont.data('page-size');
                var title = thiz.lang("page_render", {
                    page: currentPage,
                    total: totalPage,
                    perpage: pageSize,
                });
                var idx = layer.tips(title, this, {
                  tips: [1, '#586cb1'],
                  time: 0,
                  maxWidth: 210,
                });
                
                $(this).attr('layer-idx', idx);
            });
            this.onEvent('mouseleave', '.lake-form-media-modal-prev-page,.lake-form-media-modal-next-page', function () {
                layer.close($(this).attr('layer-idx'));
                
                $(this).attr('layer-idx', '');
            });
            
            // 新建文件夹
            this.onEvent('click', ".lake-form-media-dir-button", function(res){
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                var currentPath = mediaModalNavOlCont.data('current-path');
                
                var options = mediaCont.data('options');
                
                var obj = mediaModalCont.find(".lake-form-media-dir-input");
                var dir = obj.val();
                
                if (dir == "") {
                    toastr.error(thiz.lang("dir_not_empty"));
                    return false;
                }

                var form = new FormData();
                form.append("name", dir);
                form.append("dir", currentPath);
                form.append("disk", options.disk);
                form.append("_token", Dcat.token);
                $.ajax({
                    type: 'post',
                    url: options.create_folder_url,
                    data: form,
                    processData: false,
                    contentType : false,
                    success: function(data){
                        if (data['code'] == 200) {
                            toastr.success(data['msg']);
                            obj.val('');
                            thiz.getdata(name, currentPath, options)
                        } else {
                            toastr.error(data['msg']);
                        }
                    },
                    error: function(XmlHttpRequest, textStatus, errorThrown){
                        toastr.error(thiz.lang("create_dir_error"));
                    }
                });
            });

            // 上传图片
            this.onEvent('change', '.lake-form-media-upload', function() {
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                
                var currentPath = mediaModalNavOlCont.data('current-path');
                var options = mediaCont.data('options');
                
                var files = $(this).prop('files');
                
                var form = new FormData();
                for (var i = 0; i < files.length; i++) {
                    form.append("files[]", files[i]);
                }
               
                form.append("path", currentPath);
                form.append("type", options.type);
                form.append("disk", options.disk);
                form.append("nametype", options.nametype);
                form.append("resize", options.resize);
                form.append("_token", Dcat.token);
                $.ajax({
                    type: 'post', 
                    url: options.upload_url,
                    data: form,
                    processData: false,
                    contentType : false,
                    success: function(data){
                        if (data['code'] == 200) {
                            toastr.success(data['msg']);
                            thiz.getdata(name, currentPath, options)
                        } else {
                            toastr.error(data['msg']);
                        }
                    },
                    error: function(XmlHttpRequest, textStatus, errorThrown){
                        toastr.error(thiz.lang("upload_error"));
                    }
                });
            });
            
            // 提交
            this.onEvent('click', '.lake-form-media-submit', function(res){
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var name = mediaCont.data('name');
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                
                var currentPath = mediaModalNavOlCont.data('current-path');
                var options = mediaCont.data('options');
                
                var inputCont = mediaCont.find('.lake-form-media-input');
                
                var limit = options.limit;
                var type = options.type
                
                var rootpath = options.rootpath
                var saveFullUrl = options.saveFullUrl
                
                // 可多选时
                var multiplechoice = options.multiplechoice
                
                // 列表
                var urlList = [];
                var urlListStr = inputCont.val();
                if (urlListStr == '[]') {
                    urlListStr = '';
                }
                
                if (urlListStr) {
                    if (limit == 1) {
                        // 去掉预览
                        thiz.refreshPreview(name, [], options)
                    } else {
                        urlList = thiz.isJSON( urlListStr );
                    }
                }
                
                // 不是多选时
                if (multiplechoice != 1 && limit > 1) {
                    $('.lake-form-media-close').trigger("click");
                    return false;
                }
                
                if (type == 'blend') {
                    select_true_list = mediaModalCont
                        .find('.lake-form-media-selected');
                } else {
                    select_true_list = mediaModalCont
                        .find('.lake-form-media-selected[data-type="'+type+'"]');
                }
                
                for (var i = 0; i < select_true_list.length; i++) {
                    var url = $(select_true_list[i]).data('url');
                    
                    if (saveFullUrl == 1) {
                        url = rootpath + url
                    }
                    
                    urlList.push(url);
                }
                
                urlList = thiz.unique(urlList);
                
                if (limit == 1) {
                    inputCont.val(urlList[0]);
                    inputCont.attr("value", urlList[0]);
                } else {
                    // 提交限制数量
                    var newUrlList = [];
                    
                    if (urlList.length < limit) {
                        limit = urlList.length;
                    }
                    
                    for (var i = 0; i < limit; i++) {
                        newUrlList.push(urlList[i]);
                    }
                    urlList = newUrlList;
                    
                    urlListJson = JSON.stringify( urlList );
                    if (urlListJson == '[]') {
                        $('#LakeFormMediaModel'+name).modal('hide');
                        return null;
                    }
                    inputCont.val(urlListJson);
                    inputCont.attr("value", urlListJson);
                }
                
                thiz.refreshPreview(name, urlList, options)
                $('#LakeFormMediaModel'+name).modal('hide');
            });
            
            // 选中点击
            this.onEvent("click", ".lake-form-media-field-item-op", function(){
                var mediaModalCont = $(this).parents('.lake-form-media-modal');
                var mediaId = mediaModalCont.data('media');
                var mediaCont = $("." + mediaId);
                
                var itemType = $(this).data('type');
                
                var name = mediaCont.data('name');
                var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
                
                var currentPath = mediaModalNavOlCont.data('current-path');
                var options = mediaCont.data('options');
                
                var inputCont = mediaCont.find('.lake-form-media-input');
                
                var type = options.type;
                var limit = options.limit;
                
                // 可多选时
                var multiplechoice = options.multiplechoice
                
                if (type != 'blend') {
                    if (type != itemType) {
                        return false;
                    }
                }

                // 现有多少张
                var nowNumVal = inputCont.val();
                if (nowNumVal == '[]') {
                    nowNumVal = '';
                }
                var nowNumArr = [];
                if (nowNumVal) {
                    if (limit == 1) {
                        nowNumArr.push(nowNumVal)
                    } else {
                        nowNumArr = thiz.isJSON( nowNumVal );
                    }
                }
                
                // 不是多选时
                if (multiplechoice != 1 && limit > 1) {
                    // 添加当前选中
                    nowNumArr.push($(this).data('url'));
                    
                    if (nowNumArr.length > limit) {
                        toastr.error(thiz.lang("selected_error", {
                            num: limit,
                        }));
                        return 1;
                    }
                    
                    urlListJson = JSON.stringify(nowNumArr);
                    if (urlListJson == '[]') {
                        $('#LakeFormMediaModel'+name).modal('hide');
                        return null;
                    }
                    inputCont.val(urlListJson);
                    inputCont.attr("value", urlListJson);
                
                    thiz.refreshPreview(name, nowNumArr, options)
                    $('#LakeFormMediaModel'+name).modal('hide');
                    
                    return false;
                }
                
                var noNeedSelectArr = [];
                if (type != 'blend') {
                    var imgItem = mediaModalCont.find('.lake-form-media-field-item[data-type="'+itemType+'"]');
                } else {
                    var imgItem = mediaModalCont.find('.lake-form-media-field-item');
                }
                for (var i = 0; i < imgItem.length; i++) {
                    var itemUrl = $(imgItem[i]).data('url');
                    if ($.inArray(itemUrl, nowNumArr) != -1) {
                        noNeedSelectArr.push(itemUrl);
                    }
                }
                var selectedItem = mediaModalCont.find('.lake-form-media-selected');
                
                var selectNum = nowNumArr.length - noNeedSelectArr.length + selectedItem.length;
                
                var tag = $(this).hasClass('lake-form-media-selected');

                if (tag) {
                    // 取消选中
                    $(this).removeClass('lake-form-media-selected');
                } else {
                    // 选中
                    if (limit == 1) {
                        // 取消之前选中的
                        mediaModalCont
                            .find('.lake-form-media-selected')
                            .removeClass('lake-form-media-selected')
                    } else {
                        if (selectNum >= limit) {
                            toastr.error(thiz.lang("selected_error", {
                                num: limit,
                            }));
                            return 1;
                        }
                    }
                    
                    $(this).addClass('lake-form-media-selected');
                }
                
                return 1;
            });
            
            // 图片/视频预览
            this.onEvent('click', ".lake-form-media-img-show-item-preview", function() {
                var type = $(this).data('type');
                var url = $(this).data('url');
                
                var preview = '';
                var height = '85%';
                if (type == 'image') {
                    preview = '<img height="100%" src="' + url + '" />';
                } else if (type == 'video') {
                    preview = '<video height="100%" controls src="' + url + '"></video>';
                } else if (type == 'audio') {
                    height = 'auto';
                    preview = '<audio controls src="' + url + '"></audio>';
                }
                
                layer.open({
                    type: 1,
                    area: ['auto', height],
                    title: thiz.lang("preview_title"),
                    end: function(index, layero) {
                        return false;
                    },
                    content: '<div style="display: flex;align-items: center;justify-content: center;text-align: justify;height: 100%;">'+preview+'</div>',
                });
            });
        },
        
        onEvent: function(bind, elements, callback) {
            return $("body").off(bind, elements)
                .on(bind, elements, callback);
        },
        
        getdata: function(name, path = '/', options = []) {
            var mediaCont = $('.lake-form-media-' + name);
            
            var type = options.type;
            var disk = options.disk;
            var limit = options.limit;
            var remove = options.remove;
            var pageSize = options.pagesize;
            
            var mediaModalCont = $('#LakeFormMediaModel' + name);
            var mediaModalTableCont = mediaModalCont.find('.lake-form-media-body-table');
            var mediaModalNavOlCont = mediaModalCont.find('.lake-form-media-nav-ol');
            var mediaModalPageCont = mediaModalCont.find('.lake-form-media-modal-page');
            var mediaModalOrderCont = mediaModalCont.find('.lake-form-media-modal-order');
            
            var inputCont = mediaCont.find('.lake-form-media-input');
            
            var order = mediaModalOrderCont.data('order');
            var currentPath = mediaModalNavOlCont.data('current-path');
            var currentPage = mediaModalPageCont.data('current-page');
            var pageSize = mediaModalPageCont.data('page-size');
            
            var thiz = this;
            
            var baseUrl = options.get_files_url;
            if (baseUrl.indexOf("?") == -1) {
                baseUrl = baseUrl + "?";
            } else {
                baseUrl = baseUrl + "&";
            }
            
            $.ajax({
                url: baseUrl 
                    + "path=" + path
                    + "&type=" + type
                    + "&disk=" + disk
                    + "&order=" + order
                    + "&page=" + currentPage
                    + "&pageSize=" + pageSize,
                method: 'GET',
                datatype:'json',
                async: true,
                success: function (res) {
                    var list = res['data']['list'];
                    var nav = res['data']['nav'];
                    
                    mediaModalTableCont.html('');
                    if (JSON.stringify(list) != '[]') {
                        for (var i in list) {
                            if (list[i]['isDir']) {
                                var htmltemp = '';
                                htmltemp += '<div class="col-xs-4 col-md-2">';
                                htmltemp +=     '<div class="thumbnail lake-form-media-field-item lake-form-media-dir-op" data-type="'+list[i]['type']+'" data-path="/'+list[i]['name']+'" title="'+list[i]['name']+'（'+list[i]['time']+'）">';
                                htmltemp +=         list[i]['preview'];
                                htmltemp +=         '<div class="file-info">';
                                htmltemp +=             '<a href="javascript:;" class="file-name">'+list[i]['namesmall']+'</a>';
                                htmltemp +=         '</div>';
                                htmltemp +=     '</div>';
                                htmltemp += '</div>';
                                mediaModalTableCont.append(htmltemp);
                            } else {
                                var htmltemp = '';
                                htmltemp += '<div class="col-xs-4 col-md-2">';
                                
                                htmltemp +=     '<div class="thumbnail lake-form-media-field-item lake-form-media-field-item-op" data-type="'+list[i]['type']+'" data-url="'+list[i]['name']+'" title="'+list[i]['name']+'（'+list[i]['time']+'）">';
                                htmltemp +=         list[i]['preview'];
                                htmltemp +=         '<div class="file-info">';
                                htmltemp +=             '<a href="javascript:;" class="file-name">'+list[i]['namesmall']+'</a>';
                                htmltemp +=         '</div>';
                                htmltemp +=     '</div>';
                                htmltemp += '</div>';

                                mediaModalTableCont.append(htmltemp);
                            }
                        }
                        
                    } else {
                        var htmltemp = '<div class="col-12"><div class="lake-form-media-empty">' + thiz.lang("empty") + '</div></div>';
                        mediaModalTableCont.append(htmltemp);
                    }
                    
                    mediaModalNavOlCont.html('<li class="breadcrumb-item lake-form-media-nav-li" data-path="/"><a href="javascript:;"><i class="fa fa-th-large"></i> </a></li>');
                    mediaModalNavOlCont.data('current-path', '/');
                    for (var i = 0; i < nav.length; i++) {
                        mediaModalNavOlCont.append('<li class="breadcrumb-item"><a class="lake-form-media-nav-li" href="javascript:;" data-path="'+nav[i]['url']+'"> '+nav[i]['name']+'</a></li>');
                        mediaModalNavOlCont.data('current-path', nav[i]['url']);
                    }
                    
                    var urlListStr = inputCont.val();
                    var urlList = [];
                    if (limit == 1) {
                        var urlLists = thiz.isJSON(urlListStr);
                        if (urlLists.length > 0) {
                            mediaModalTableCont.find('[data-url="'+urlLists[0]+'"]')
                                .addClass('lake-form-media-selected');
                        }
                    } else {
                        urlList = thiz.isJSON( urlListStr );
                        for (var index in urlList) {
                            mediaModalTableCont.find('[data-url="'+urlList[index]+'"]')
                                .addClass('lake-form-media-selected');
                        }
                    }
                    
                    var totalPage = parseInt(res['data']['total_page']);
                    var currentPage = parseInt(res['data']['current_page']);
                    var perPage = parseInt(res['data']['per_page']);
                    
                    mediaModalPageCont.data('current-page', currentPage);
                    mediaModalPageCont.data('total-page', totalPage);
                    
                    if (totalPage > 1) {
                        if (currentPage > 1) {
                            mediaModalPageCont.find('.lake-form-media-modal-prev-page').removeClass('hidden');
                        } else {
                            mediaModalPageCont.find('.lake-form-media-modal-prev-page').addClass('hidden');
                        }
                        
                        if (currentPage < totalPage) {
                            mediaModalPageCont.find('.lake-form-media-modal-next-page').removeClass('hidden');
                        } else {
                            mediaModalPageCont.find('.lake-form-media-modal-next-page').addClass('hidden');
                        }
                    } else {
                        mediaModalPageCont.find('.lake-form-media-modal-prev-page').addClass('hidden');
                        mediaModalPageCont.find('.lake-form-media-modal-next-page').addClass('hidden');
                    }

                },
                error: function(XmlHttpRequest, textStatus, errorThrown){
                    toastr.error(thiz.lang("getdata_error"));
                },
                cache: false,
                contentType: false,
                processData: false
            });
        },
        
        // 刷新表单预览
        refreshInputPreview: function(cont) {
            var mediaCont = $(cont).parents('.lake-form-media');
            var name = mediaCont.data('name');
            
            var mediaModalCont = $('#LakeFormMediaModel' + name);
            
            var value = $(cont).val();
            
            var options = mediaCont.data('options');
            options = $.extend({}, options);
            
            var limit = options.limit;
            
            var valueArr = [];
            if (limit > 1) {
                if (value != "") {
                    valueArr = this.isJSON(value);
                }
            } else {
                if (value != '[]' && value != '') {
                    valueArr.push(value)
                }
            }
            this.refreshPreview(name, valueArr, options);
            
            if (value == "") {
                mediaModalCont
                    .find('.lake-form-media-img-show')
                    .hide();
            }
        },
        
        // 刷新表单数据
        refreshInputString: function(name) {
            var mediaCont = $('.lake-form-media-'+name);
            var inputCont = mediaCont.find('.lake-form-media-input');
            
            var urlList = [];
            mediaCont.find('.lake-form-media-preview-item')
                .each(function(i, cont) {
                    urlList.push($(cont).data('src'));
                });
            
            var inputString = JSON.stringify( urlList );
            if (inputString == '[]' || inputString  == '[""]') {
                inputString = '';
            }
            
            inputCont.val(inputString);
        },
        
        // 刷新/显示 预览
        refreshPreview: function(name, urlList, options = []) {
            var thiz = this;
            
            var limit = options.limit;
            var remove = options.remove;
            var rootpath = options.rootpath;
            var showtitle = options.showtitle;
            var showicon = options.showicon;
            
            var saveFullUrl = options.saveFullUrl;
            
            var mediaCont = $('.lake-form-media-'+name);
            var imgShowCont = mediaCont.find('.lake-form-media-img-show');
            var imgShowRowCont = mediaCont.find('.lake-form-media-img-show-row');
            
            imgShowRowCont.html('');
            if (urlList.length > 0) {
                imgShowCont.show();
            } else {
                imgShowCont.hide();
            }
            var fieldIconimg = mediaCont.attr('data-field');
            for (var i = 0; i < urlList.length; i++) {
                var src = urlList[i];
                if (! this.isUrl(src)) {
                    if (saveFullUrl != 1) {
                        src = rootpath + urlList[i];
                    }
                }
                if(fieldIconimg == 'iconimg'){
                    var html = '<div class="lake-form-media-preview-item" data-src="'+urlList[i]+'">';
                    html += '<div class="thumbnail lake-form-media-row-col">';
                }else{
                    var html = '<div class="col-xs-6 col-sm-6 col-md-4 col-lg-3 lake-form-media-preview-item" data-src="'+urlList[i]+'">';
                    html += '<div class="thumbnail lake-form-media-row-col">';
                }

                
                html += '<div class="lake-form-media-row-img" title="' + urlList[i] + '">';
                html += this.getFileDisplay(src);
                html += '</div>';
                
                var suffix = this.getFileSuffix(src);
                var showType = this.getFileShowType(src);
                
                // 显示类型
                if (showicon) {
                    html += '<span class="row-icon">';
                    html += showType;
                    html += '</span>';
                }
                
                // 文件名
                if (showtitle) {
                    html += '<div class="row-title" title="' + urlList[i] + '">';
                    html += urlList[i];
                    html += '</div>';
                }
                
                html += '<div class="caption">';
                if (suffix == 'image' || suffix == 'video' || suffix == 'audio') {
                    html += '<span class="btn btn-default lake-form-media-img-show-item-preview" data-type="'+suffix+'" data-url="'+src+'" title="' + thiz.lang("preview") + '"><i class="fa fa-search-plus"></i></span>';
                }
                if (remove) {
                    html += '<span class="btn btn-default file-delete-multiple lake-form-media-img-show-item-delete" data-url="'+urlList[i]+'" title="' + thiz.lang("remove") + '"><i class="fa fa-trash-o"></i></span>';
                }
                if (limit > 1) {
                    html += '<span class="btn btn-default lake-form-media-img-show-item-dragsort js-dragsort" title="' + thiz.lang("dragsort") + '"><i class="fa fa-arrows"></i></span>';
                }
                html += '</div>';
                
                html += '</div>';
                html += '</div>';
                
                imgShowRowCont.append(html);
            }
        },
        
        unique: function (arr){
            var hash = [];
            for (var i = 0; i < arr.length; i++) {
                if(hash.indexOf(arr[i])==-1){
                    hash.push(arr[i]);
                }
            }
            
            return hash;
        },
    
        isJSON: function(str) {
            if (typeof str == 'string') {
                try {
                    return JSON.parse(str);
                } catch(e) {
                    return [str];
                }
            }
            return [];  
        },
    
        isUrl: function(url) {
            if (url.substr(0, 7).toLowerCase() == "http://" 
                || url.substr(0, 8).toLowerCase() == "https://"
                || url.substr(0, 2).toLowerCase() == "//"
            ) {
                return true;
            }
            
            return false;
        },
        
        // 判断是否是 object
        isObj: function(object) {
            return object 
                && typeof (object) == 'object' 
                && Object.prototype
                    .toString.call(object)
                    .toLowerCase() == "[object object]";
        },
        
        // 判断是否是 array
        isArray: function(object) {
            return Object.prototype
                .toString.call(object)
                .toLowerCase() === '[object array]';
        },
        
        getFileSuffix: function (src) {
            try {
                var srcArr = src.split('.');
                var suffix = srcArr[srcArr.length - 1];
            } catch(err) {
                var suffix = '';
            }
            
            if (suffix) {
                var type = this.getFileType(suffix.toLocaleLowerCase());
            } else {
                var type = '';
            }
            
            return type;
        },
        
        getFileExt: function (filename) {
            try {
                var srcArr = filename.split('.');
                var ext = srcArr[srcArr.length - 1];
            } catch(err) {
                var ext = '';
            }
            
            return ext;
        },

        getFileDisplay: function (src) {
            var type = this.getFileSuffix(src);
            
            var html = '';
            if (type === 'image') {
                html += '<img width="100%" src="' + src + '" alt="'+src+'"/>';
            } else if (type === 'video') {
                // html += '<video width="100%" height="100%" src="' + src + '"></video>';
                html += '<i class="fa fa-file-video-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'audio') {
                html += '<i class="fa fa-file-audio-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'pdf') {
                html += '<i class="fa fa-file-pdf-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'word') {
                html += '<i class="fa fa-file-word-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'ppt') {
                html += '<i class="fa fa-file-powerpoint-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'xls') {
                html += '<i class="fa fa-file-excel-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'text') {
                html += '<i class="fa fa-file-text-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'code') {
                html += '<i class="fa fa-file-code-o fa-fw lake-form-media-preview-fa"></i>';
            } else if (type === 'zip') {
                html += '<i class="fa fa-file-zip-o fa-fw lake-form-media-preview-fa"></i>';
            } else {
                html += '<i class="fa fa-file fa-fw lake-form-media-preview-fa"></i>';
            }
            
            return html;
        },
        
        getFileShowType: function (src) {
            var type = this.getFileSuffix(src);
            
            var html = '';
            if (type === 'image') {
                html += '<i class="fa fa-file-image-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'video') {
                html += '<i class="fa fa-file-video-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'audio') {
                html += '<i class="fa fa-file-audio-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'pdf') {
                html += '<i class="fa fa-file-pdf-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'word') {
                html += '<i class="fa fa-file-word-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'ppt') {
                html += '<i class="fa fa-file-powerpoint-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'xls') {
                html += '<i class="fa fa-file-excel-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'text') {
                html += '<i class="fa fa-file-text-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'code') {
                html += '<i class="fa fa-file-code-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else if (type === 'zip') {
                html += '<i class="fa fa-file-zip-o fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            } else {
                html += '<i class="fa fa-file fa-fw lake-form-media-show-icon" title="' + type + '"></i>';
            }
            
            return html;
        },
        
        getFileType: function (suffix) {
            // 匹配图片
            var image = [
                'jpeg', 'jpg', 'bmp', 'png', 'svg', 'wbmp', 'pic', 
                'cgm', 'djv', 'djvu', 'gif', 'ico', 'ief', 'jp2', 
                'jpe', 'mac', 'pbm', 'pct', 'pgm', 'pict', 'pnm', 
                'pnt', 'pntg', 'ppm', 'qti', 'qtif', 'ras', 'rgb', 
                'tif', 'tiff', 'xbm', 'xpm', 'xwd', 'avif'
            ];

            // 匹配视频
            var video = [
                'mkv', 'avi', 'mp4', 'rmvb', 'rm', 
                'flv', 'wmv', 'asf', 'mpeg', 'mov'
            ];

            // 匹配音频
            var audio = [
                'mp3', 'wav', 'flac', '3pg', 'aa', 'aac', 'ape', 
                'au', 'm4a', 'mpc', 'ogg'
            ];

            // 匹配 pdf
            var pdf = [
                'pdf'
            ];

            // 匹配 word
            var word = [
                'doc', 'dot', 'docx', 'dotx', 'docm', 'dotm', 'wps'
            ];

            // 匹配 ppt
            var ppt = [
                'ppt', 'pptx', 'pptm', 'pot', 'pps', 'ppa', 'pptx', 
                'potx', 'ppsx', 'ppam', 'potm', 'ppsm'
            ];

            // 匹配 xls
            var xls = [
                'xls', 'xlt', 'xla', 'xlsx', 'xltx', 'xlsm', 
                'xltm', 'xlam', 'xlsb'
            ];

            // 匹配文本
            var text = [
                'txt', 'pac', 'log', 'md'
            ];

            // 匹配代码
            var code = [
                'html', 'htm', 'js', 'css', 'vue', 'json', 
                'php', 'java', 'go', 'py', 'ruby', 'rb', 
                'aspx', 'asp', 'c', 'cpp', 'sql', 'm', 'h', 
                'python', 'ruby', 'rs', 'zig', 'v'
            ];

            // 匹配压缩包
            var zip = [
                'zip', 'tar', 'gz', 'rar', 'rpm'
            ];
            
            var list = {
                'image': image,
                'video': video,
                'audio': audio,
                'pdf': pdf,
                'word': word,
                'ppt': ppt,
                'xls': xls,
                'text': text,
                'code': code,
                'zip': zip,
            }
            
            for (var key in list) {
                if (list[key].indexOf(suffix) != -1) {
                    return key;
                }
            };
            
            return "other";
        },
        
        // 复制
        copyData: function(data) {
            let target = document.createElement('div');
            target.id = 'tempTarget';
            target.style.opacity = '0';
            target.innerText = data;
            document.body.appendChild(target);

            try {
                let range = document.createRange();
                range.selectNode(target);
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                window.getSelection().removeAllRanges();

                layer.msg(this.lang("copy_success"));
            } catch (e) {
                layer.msg(this.lang("copy_error"));
            }

            target.parentElement.removeChild(target);
        },
        
        // 翻译
        lang: function () {
            var args = arguments,
                string = args[0],
                i = 1;
            
            var thiz = this;
            
            // 语言包
            var Lang = window.LakeFormMediaLang;
            
            string = string.toLowerCase();
            if (typeof Lang !== 'undefined' && typeof Lang[string] !== 'undefined') {
                if (typeof Lang[string] == 'object') {
                    return Lang[string];
                }
                
                string = Lang[string];
            } else if (string.indexOf('.') !== -1 && false) {
                var arr = string.split('.');
                var current = Lang[arr[0]];
                
                for (var i = 1; i < arr.length; i++) {
                    current = typeof current[arr[i]] != 'undefined' 
                        ? current[arr[i]] 
                        : '';
                    if (typeof current != 'object') {
                        break;
                    }
                }
                
                if (typeof current == 'object') {
                    return current;
                }
                
                string = current;
            } else {
                string = args[0];
            }
            
            // 原始按序替换
            string = string.replace(/%((%)|s|d)/g, function (m) {
                // m 是匹配到的格式, e.g. %s, %d
                var val = null;
                
                if (m[2]) {
                    val = m[2];
                } else {
                    val = args[i];
                    // 默认是 %s
                    switch (m) {
                        case '%d':
                            val = parseFloat(val);
                            if (isNaN(val)) {
                                val = 0;
                            }
                            break;
                    }
                    i++;
                }
                
                return val;
            });
            
            // 键值翻译
            string = string.replace(/:([a-zA-Z0-9:\-\_]+)/g, function (m) {
                if (args.length < 2) {
                    return m;
                }
                
                if (m[1] && m[1] == ":") {
                    return m.substr(1);
                }
                
                // 默认
                var val = null;
                
                // 翻译数据
                var data = args[1];
                
                // 对象判断
                if (! thiz.isObj(data)) {
                    return m;
                }
                
                // 键值
                var key = m.substr(1);
                
                // 键值判断
                if (! (key in data)) {
                    return m;
                }
                
                // 设置值
                val = data[key];

                return val;
            });
            
            return string;
        },
    }
    
    LakeFormMedia.init();
    
    window.LakeFormMedia = LakeFormMedia;
});

