@if(!empty($data['image']))
    <div class="ex-page-item">
        <div class="ex-page-item-box ex-page-item-Banner">
            <div class="image">
                <a href="{{$data['url'] or 'javascript:;'}}">
                    <img src="{{\Edwin404\SmartAssets\Helper\AssetsHelper::fix($data['image'])}}" />
                </a>
            </div>
        </div>
    </div>
@endif