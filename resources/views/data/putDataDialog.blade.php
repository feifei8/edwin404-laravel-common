@extends('common::data.dialogFrame')

@section('pageTitle','请选择文件')

@section('headAppend')
    @parent
    <link rel="stylesheet" href="@assets('assets/lib/css/pager.css')"/>
@endsection

@section('bodyAppend')
    @parent
    <script>
        var __uploadButton = {
            swf:'@assets('assets/webuploader/Uploader.swf')',
            chunkSize: <?php echo \Edwin404\Base\Support\FileHelper::formattedSizeToBytes(ini_get('upload_max_filesize'))-500*1024; ?>,
            extensions:<?php echo json_encode(join(',',config('data.upload.'.$category.'.extensions'))); ?>,
            sizeLimit:<?php echo json_encode(config('data.upload.'.$category.'.maxSize')); ?>,
        };
    </script>
    <script src="@assets('assets/basic/js/putDataDialog.js')"></script>
@endsection

@section('bodyContent')

    <div id="uploadButton"></div>

@endsection