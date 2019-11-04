<div class="line">
    <div class="label">{{$title}}</div>
    <div class="field">
        @if(!empty($data['option']))
            @foreach($data['option'] as $o)
                <label>
                    <input type="radio" name="{{$key}}" value="{{$o}}" /> {{$o}}
                </label>
            @endforeach
        @endif
    </div>
</div>