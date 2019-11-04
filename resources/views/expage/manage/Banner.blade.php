<div class="ex-page-item" v-if="moduleItem.type=='Banner'" v-bind:class="{active:moduleIndexEditing===moduleIndex}">
    <div class="ex-page-item-box ex-page-item-Banner">
        <div class="image">
            <img v-if="moduleItem.data.image==''" v-bind:src="'/assets/lib/img/none.png'" />
            <img v-if="moduleItem.data.image!=''" v-bind:src="moduleItem.data.image" />
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