@if(!empty($data['list']))
    <div class="ex-page-item">
        <div class="ex-page-item-box ex-page-item-NavH3">
            <div class="list">
                @foreach($data['list'] as $item)
                    <a class="item" href="{{$item['url'] or ''}}">
                        <img src="{{\Edwin404\SmartAssets\Helper\AssetsHelper::fix($item['image'])}}" />
                        <br>
                        <span class="text">{{$item['text'] or ''}}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif