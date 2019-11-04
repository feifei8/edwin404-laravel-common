<!doctype html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta http-equiv="Cache-Control" content="no-siteapp"/>
    <script type="text/javascript">var __env={"token":"{{csrf_token()}}"};</script>
    <script src="@assets('assets/init.js')"></script>
    <title>@yield('pageTitle')</title>
    <link rel="stylesheet" href="@assets('assets/uikit/css/ui.css')">
    @section('headAppend')@show
    <style type="text/css">
        #_frameBox {
            padding:10px;
            background: #FFF;
            font-size: 13px;
        }
        #_frameTool {
            position: fixed;
            bottom: 0px;
            left: 0px;
            right: 0px;
            background: #FFF;
            border-top: 1px solid #CCC;
            padding: 10px;
            text-align: right;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            width:100%;
            z-index:9999;
        }
    </style>
</head>
<body>
<div id="_frameBox">
    @section('bodyContent')@show
</div>
<div id="_frameTool">
    <a href="javascript:;" class="h-submit uk-button uk-button-primary">确定</a>
    <a href="#" class="h-close uk-button uk-button-default">关闭</a>
</div>
@section('bodyAppend')@show
</body>
</html>
