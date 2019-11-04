@extends('common::data.dialogFrame')

@section('pageTitle','请选择图片')

@section('headAppend')
    @parent
    <link rel="stylesheet" href="@assets('assets/lib/css/pager.css')"/>
    <style type="text/css">
        #list{overflow: hidden;padding: 10px 0;}
        #list .empty {line-height:150px;text-align:center;color:#999;}
        #list .item {width: 120px;margin:10px;float: left;position: relative;}
        #list .item .image{background-size:cover;background-repeat: no-repeat;width: 120px;height: 120px;border: 1px solid #EEE;position:relative;background-position:center;}
        #list .item .image .checked{position: absolute;display: none;right: 0;bottom: 0;border: 14px solid #07d;border-left-color: transparent;border-top-color: transparent;}
        #list .item .image .checked i{color: #FFF;font-size: 14px;right: -14px;top: -2px;position: absolute;display: block;}
        #list .item .name{text-align:center;height:20px;line-height:20px;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;width:100%;color:#999;}
        #list .item.checked .image {border-color: #07d;}
        #list .item.checked .image .checked{display:block;}
        #list .item .action{text-align:center;}
        #list .item .action a{color:#999;}
        #pageContainer{padding:0 0 50px 210px;}
        #pageContainer a{text-decoration:none;}
        #pageContainer #pageLeft{width:200px;float:left;margin:0 0 0 -210px;background:#F8F8F8;border-radius:3px;}
        #categoryMenu{padding:10px;background:#EEE;height:40px;box-sizing:border-box;}
        #categoryMenu a{display:inline-block;width:30%;text-align:center;color:#999;}
        #categoryMenu a.enable{color:#0666f3;}
        #categoryTree{padding:10px 0;margin:0;}
        #categoryTree ul{padding:0;}
        #categoryTree a:hover{text-decoration:none;}
        #categoryTree a.selected{color:#0666f3;}
        #pageContainer #pageRight{border-radius:3px;}
        #contentHead{padding:5px 10px;background:#FFF;border-bottom:1px solid #EEE;height:40px;box-sizing:border-box;}
        #imageUploadButton{width:100px;float:right;border-radius:4px;}
        #contentTitle{line-height:30px;}
        #contentTitle a{display:inline-block;margin:0 0 0 10px;font-size:12px;color:#999;}
        #contentTitle a.enable{color:#0666f3;}
    </style>
@endsection

@section('bodyAppend')
    @parent
    <script>
        var __uploadButton = {
            swf:'@assets('assets/webuploader/Uploader.swf')',
            chunkSize: <?php echo \Edwin404\Base\Support\FileHelper::formattedSizeToBytes(ini_get('upload_max_filesize'))-500*1024; ?>
        };
    </script>
    <script src="@assets('assets/basic/js/dataImageSelectDialog.js')"></script>
@endsection

@section('bodyContent')

    <div id="pageContainer">
        <div id="pageLeft">
            <div id="categoryMenu">
                <a href="javascript:;" data-category-add class="enable"><i class="uk-icon-plus"></i> 新建</a>
                <a href="javascript:;" data-category-edit><i class="uk-icon-edit"></i> 修改</a>
                <a href="javascript:;" data-category-delete><i class="uk-icon-trash"></i> 删除</a>
            </div>
            <ul id="categoryTree">
            </ul>
        </div>
        <div id="pageRight">
            <div id="contentHead">
                <div id="imageUploadButton"></div>
                <div id="contentTitle">
                    图片列表
                    <a href="javascript:;" data-image-edit><i class="uk-icon-edit"></i> 分类</a>
                    <a href="javascript:;" data-image-delete><i class="uk-icon-trash"></i> 删除</a>
                </div>
            </div>
            <div id="contentBody">
                <div id="list">
                </div>
                <div id="page" class="page-container">
                </div>
            </div>
        </div>
    </div>

    <div id="categoryEdit" class="uk-modal">
        <div class="uk-modal-dialog" style="width:300px;">
            <a class="uk-modal-close uk-close"></a>
            <div class="uk-modal-header">
                分类
            </div>
            <div class="uk-form">
                <div>
                    上级
                    <select name="categoryEditPid"></select>
                </div>
                <div style="padding:10px 0 0 0;">
                    名称
                    <input type="text" name="categoryEditTitle" />
                    <input type="hidden" name="categoryEditId" value="0" />
                </div>
            </div>
            <div class="uk-modal-footer">
                <a href="javascript:;" class="uk-button uk-button-primary" data-category-edit-save>确定</a>
            </div>
        </div>
    </div>

    <div id="imageEdit" class="uk-modal">
        <div class="uk-modal-dialog" style="width:300px;">
            <a class="uk-modal-close uk-close"></a>
            <div class="uk-modal-header">
                图片
            </div>
            <div class="uk-form">
                <div>
                    分类
                    <select name="imageEditCategoryId"></select>
                </div>
            </div>
            <div class="uk-modal-footer">
                <a href="javascript:;" class="uk-button uk-button-primary" data-image-edit-save>确定</a>
            </div>
        </div>
    </div>

@endsection