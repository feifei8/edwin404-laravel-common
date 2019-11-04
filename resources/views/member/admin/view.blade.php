@extends('admin::frameDialog')

@section('pageTitle','用户信息')

@section('dialogBody')

    <div class="admin-form">
        <form action="?" method="post" data-ajax-form class="uk-form">
            <input type="hidden" name="_id" value="{{\Illuminate\Support\Facades\Input::get('_id','')}}" />
            <table>
                <tbody>
                <tr>
                    <td>
                        <div class="line">
                            <div class="value uk-text-center">
                                <img style="height:100px;width:100px;border-radius:50%;border:1px solid #CCC;padding:2px;" src="{{\Edwin404\SmartAssets\Helper\AssetsHelper::fixOrDefault($memberUser['avatarBig'],'/assets/lib/img/none.png')}}" />
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="uk-grid">
                            <div class="uk-width-1-2">
                                <div class="line">
                                    <div class="label">
                                        用户名
                                    </div>
                                    <div class="value">
                                        <div>
                                            @if(empty($memberUser['username']))
                                                [空]
                                            @else
                                                {{$memberUser['username']}}
                                            @endif
                                            <a href="javascript:;" data-uk-tooltip title="修改" onclick="$(this).parent().hide().next().show();"><i class="uk-icon-edit"></i></a>
                                        </div>
                                        <input style="display:none;" type="text" name="username" value="{{$memberUser['username'] or ''}}" />
                                    </div>
                                </div>
                            </div>
                            <div class="uk-width-1-2">
                                <div class="line">
                                    <div class="label">
                                        密码
                                    </div>
                                    <div class="value">
                                        <input type="text" name="resetPassword" placeholder="留空表示不修改" />
                                        <a href="javascript:;" onclick="$(this).prev().val(100000+Math.round(Math.random()*900000));" data-uk-tooltip title="随机生成新密码"><i class="uk-icon-refresh"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="uk-grid">
                            <div class="uk-width-1-2">
                                <div class="line">
                                    <div class="label">
                                        手机
                                    </div>
                                    <div class="value">
                                        <div>
                                            @if(empty($memberUser['phone']))
                                                [无]
                                            @else
                                                {{$memberUser['phone']}}
                                            @endif
                                            <a href="javascript:;" data-uk-tooltip title="修改" onclick="$(this).parent().hide().next().show();"><i class="uk-icon-edit"></i></a>
                                        </div>
                                        <input style="display:none;" type="text" name="phone" value="{{$memberUser['phone'] or ''}}" />
                                    </div>
                                </div>
                            </div>
                            <div class="uk-width-1-2">
                                <div class="line">
                                    <div class="label">
                                        邮箱
                                    </div>
                                    <div class="value">
                                        <div>
                                            @if(empty($memberUser['email']))
                                                [无]
                                            @else
                                                {{$memberUser['email']}}
                                            @endif
                                            <a href="javascript:;" data-uk-tooltip title="修改" onclick="$(this).parent().hide().next().show();"><i class="uk-icon-edit"></i></a>
                                        </div>
                                        <input style="display:none;" type="text" name="email" value="{{$memberUser['email'] or ''}}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="uk-grid">
                            @if(isset($memberUser['gender']))
                                <div class="uk-width-1-2">
                                    <div class="line">
                                        <div class="label">
                                            性别
                                        </div>
                                        <div class="value">
                                            {{\Edwin404\Base\Support\TypeHelper::name(\Edwin404\Member\Types\Gender::class,$memberUser['gender'])}}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($memberUser['realname']))
                                <div class="uk-width-1-2">
                                    <div class="line">
                                        <div class="label">
                                            真实姓名
                                        </div>
                                        <div class="value">
                                            {{$memberUser['realname'] or '[空]'}}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if(isset($memberUser['signature']))
                                <div class="uk-width-1-1">
                                    <div class="line">
                                        <div class="label">
                                            个性签名
                                        </div>
                                        <div class="value">
                                            {{$memberUser['signature'] or '[空]'}}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>

@endsection
