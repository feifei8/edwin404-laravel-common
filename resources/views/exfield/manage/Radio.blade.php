<div class="ex-field-item-manage" v-if="moduleItem.type=='Radio'">
    <i-button type="ghost" @click="{{$appModule}}editItem(moduleIndex)">单选 - @{{ moduleItem.title }}</i-button>
    <i-button type="ghost" icon="close" @click="{{$appModule}}deleteItem(moduleIndex)"></i-button>
</div>