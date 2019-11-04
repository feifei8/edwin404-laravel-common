@if(!empty($data['list']))
    <div class="ex-page-item">
        <div class="ex-page-item-box ex-page-item-NavV">
            <div class="list">
                @foreach($data['list'] as $item)
                    <a class="item" href="{{$item['url'] or ''}}">
                        <i class="angle uk-icon-angle-right"></i>
                        <span class="text">{{$item['text'] or ''}}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif