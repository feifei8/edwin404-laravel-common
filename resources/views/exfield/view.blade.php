<div class="ex-field-view" v-if="{{$appModule}}module">
    <div v-for="(moduleItem,moduleIndex) in {{$appModule}}module">
        {!! $manages !!}
    </div>

    <div v-show="{{$appModule}}moduleLimit==0 || {{$appModule}}module.length<{{$appModule}}moduleLimit" class="ex-field-add" v-on:click="{{$appModule}}addItem()">
        <i-button type="ghost" icon="plus">增加字段</i-button>
    </div>
</div>

<Modal class="ex-field-edit" v-model="{{$appModule}}moduleEditing" :title="{{$appModule}}moduleIndexEditing>=0?'字段编辑':'字段增加'" @on-ok="{{$appModule}}editOK">
    <div v-if="{{$appModule}}moduleItemEditing!=null" style="text-align:center;padding:0 0 10px 0;">
        <Radio-group v-model="{{$appModule}}moduleItemEditing.type" type="button">
            @foreach($modules as $module=>$moduleName)
                <Radio label="{{$module}}">{{$moduleName}}</Radio>
            @endforeach
        </Radio-group>
    </div>
    <div v-if="{{$appModule}}moduleItemEditing!=null">
        {!! $edits !!}
    </div>
</Modal>
