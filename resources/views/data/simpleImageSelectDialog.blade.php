@extends('common::data.dialogFrame')

@section('pageTitle','请选择图片')

@section('headAppend')
    @parent
    <link rel="stylesheet" href="@assets('assets/lib/css/pager.css')"/>
    <style type="text/css">
        #list{overflow: hidden;padding: 10px 0;}
        #list .empty {line-height:150px;text-align:center;color:#999;}
        #list .item {width: 120px;margin:10px;float: left;position: relative;}
        #list .item .image{background-size: cover;background-repeat: no-repeat;width: 120px;height: 120px;border: 1px solid #EEE;position: relative;}
        #list .item .image .checked{position: absolute;display: none;right: 0;bottom: 0;border: 14px solid #07d;border-left-color: transparent;border-top-color: transparent;}
        #list .item .image .checked i{color: #FFF;font-size: 14px;right: -14px;top: -2px;position: absolute;display: block;}
        #list .item .name{text-align:center;height:20px;line-height:20px;text-overflow:ellipsis;white-space:nowrap;overflow:hidden;width:100%;color:#999;}
        #list .item.checked .image {border-color: #07d;}
        #list .item.checked .image .checked{display:block;}
        #upload .upload-button{width:200px;margin:50px auto;}
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
    <script src="@assets('assets/basic/js/simpleDataImageSelectDialog.js')"></script>
@endsection

@section('bodyContent')

    <ul class="uk-tab" data-uk-switcher="{connect:'#pages'}">
        <li class="uk-active"><a href="#">上传文件</a></li>
        <li><a href="#">图片库</a></li>
    </ul>

    <div id="pages" class="uk-switcher">
        <div class="uk-active">
            <div id="upload">
                <div class="upload-button"></div>
            </div>
        </div>
        <div>
            <div id="list">
            </div>
            <div id="page" class="page-container">
            </div>
        </div>
    </div>


    <div style="height:51px;"></div>

@endsection