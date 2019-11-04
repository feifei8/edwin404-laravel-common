<div class="ex-field-item-manage" v-if="moduleItem.type=='Text'">
    <i-button type="ghost" @click="{{$appModule}}editItem(moduleIndex)">单行文本 - @{{ moduleItem.title }}</i-button>
    <i-button type="ghost" icon="close" @click="{{$appModule}}deleteItem(moduleIndex)"></i-button>
</div>