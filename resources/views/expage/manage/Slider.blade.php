<div class="ex-page-item" v-if="moduleItem.type=='Slider'" v-bind:class="{active:moduleIndexEditing===moduleIndex}">
    <div class="ex-page-item-box ex-page-item-Slider">
        <div class="slider">
            <div v-if="moduleItem.data.list.length>0 && moduleItem.data.list[0].image">
                <div class="image" :style="'height:'+moduleItem.data.height+'px;background-image:url('+moduleItem.data.list[0].image+')'"></div>
            </div>
            <div v-if="moduleItem.data.list.length==0 || !moduleItem.data.list[0].image">
                <div class="image" :style="'height:'+moduleItem.data.height+'px;background-image:url(@assets('assets/lib/img/none.png'))'"></div>
            </div>
            <div class="dot">
                <i></i>
                <i></i>
                <i></i>
            </div>
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