@if(!empty($data['list']))
    <div class="ex-page-item">
        <div class="ex-page-item-box ex-page-item-Slider">
            <div class="slider-box">
                <div class="swiper-container" style="height:{{$data['height'] or '100'}}px;">
                    <div class="swiper-wrapper">
                        @foreach($data['list'] as $item)
                            <a class="swiper-slide" href="{{$item['url'] or 'javascript:;'}}" style="background-image:url({{\Edwin404\SmartAssets\Helper\AssetsHelper::fix($item['image'])}});height:{{$data['height'] or '100'}}px;"></a>
                        @endforeach
                    </div>
                    <div class="swiper-pagination swiper-pagination-white"></div>
                </div>
            </div>
        </div>
    </div>
@endif