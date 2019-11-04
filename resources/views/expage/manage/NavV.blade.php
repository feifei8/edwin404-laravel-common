<div class="ex-page-item" v-if="moduleItem.type=='NavV'" v-bind:class="{active:moduleIndexEditing===moduleIndex}">
    <div class="ex-page-item-box ex-page-item-NavV">
        <div class="list">
            <a class="item" href="javascript:;" v-for="navVItem in moduleItem.data.list">
                <i class="angle uk-icon-angle-right"></i>
                @{{ navVItem.text }}
            </a>
        </div>
    </div>
    <div class="ex-page-item-action">
        <div class="mask"></div>
        <div class="btn">
            <a href="javascript:;" v-on:click="addAfter(moduleItem,moduleIndex)">在之后增加</a>
            <a href="javascript:;" v-on:click="edit(moduleItem,moduleIndex)">编辑</a>
            <a href="javascript:;" v-on:click="remove(moduleItem,moduleIndex)">删除</a>
            <a href="javascript:;" v-on:click="moveUp(moduleItem,moduleIndex)" v-if="moduleIndex>0">上移</a>
            <a href="javascript:;" v-on:click="moveDown(moduleItem,moduleIndex)" v-if="moduleIndex<module.length-1">下移</a>
        </div>
    </div>
</div>