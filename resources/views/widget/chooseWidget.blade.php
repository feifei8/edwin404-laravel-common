@extends('base::dialogFrame')

@section('pageTitle','请选择')

@section('headAppend')
    @parent
    <script src="@assets('assets/init.js')"></script>
    <link rel="stylesheet" href="@assets('assets/uikit/css/ui.css')" />
    <link rel="stylesheet" href="@assets('assets/lib/css/lister.css')" />
    <link rel="stylesheet" href="@assets('assets/lib/css/pager.css')" />
    <style type="text/css">
        #dialogFrameBox{padding: 5px;}
        .uk-table tr th{background:#FFF;cursor:default;}
        .uk-table tr{cursor:pointer;}
        .uk-table tr td:first-child{border-radius:3px 0 0 3px;}
        .uk-table tr td:last-child{border-radius:0 3px 3px 0;}
        .uk-table tr.cur td{background:#07d;color:#FFF;}
        .uk-table tr.cur td:last-child{background:url("@assets('assets/lib/img/check_o.png')") 99% center no-repeat #07d;}
        .page-container .pages{padding:0;}
    </style>
@endsection

@section('bodyAppend')
    @parent
    <script src="@assets('assets/basic/widget/chooseWidget.js')"></script>
@endsection

@section('bodyContent')
    <div id="choosePanel" class="uk-panel uk-panel-header">
        <div class="lister-table-container">
            <table class="uk-table uk-table-hover"></table>
        </div>
        <div class="page-container"></div>
    </div>
@endsection