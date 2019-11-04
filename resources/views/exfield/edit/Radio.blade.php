<div class="ex-page-item-editor" v-if="{{$appModule}}moduleItemEditing!=null && {{$appModule}}moduleItemEditing.type=='Radio'">
    <i-form v-bind:label-width="80">
        <Form-item label="名称">
            <i-input v-model="{{$appModule}}moduleItemEditing.title"></i-input>
        </Form-item>
        <Form-item label="选项">
            <div v-for="(listItem,listIndex) in {{$appModule}}moduleItemEditing.data.option">
                <i-input v-model="{{$appModule}}moduleItemEditing.data.option[listIndex]" style="width:200px;"></i-input>
                <i-button type="ghost" icon="close" @click="{{$appModule}}moduleItemEditing.data.option.splice(listIndex,1)"></i-button>
            </div>
            <i-button type="ghost" icon="plus" @click="{{$appModule}}moduleItemEditing.data={{$appModule}}safePushAndReturnData({{$appModule}}moduleItemEditing.data,'option','');"></i-button>
        </Form-item>
    </i-form>
</div>