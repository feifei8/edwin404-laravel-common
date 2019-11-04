<div class="ex-page-item" data-type="Slider" v-if="moduleItemEditing && moduleItemEditing.type=='Banner'" style="min-width:320px;">
    <i-form v-bind:label-width="80">
        <Form-item label="图片">
            <div class="ex-page-image-selector">
                <span v-if="moduleItemEditing.data.image==''" class="image" v-bind:style="{backgroundImage:'url(/assets/lib/img/none.png)'}"></span>
                <span v-if="moduleItemEditing.data.image!=''" class="image" v-bind:style="{backgroundImage:'url('+moduleItemEditing.data.image+')'}"></span>
                <span class="add" v-on:click="selectImage(function(image){moduleItemEditing.data.image=image;})"><i class="uk-icon-plus"></i></span>
                <span class="delete" v-on:click="moduleItemEditing.data.image=''"><i class="uk-icon-remove"></i></span>
            </div>
        </Form-item>
        <Form-item label="链接">
            <i-button style="max-width:20em;overflow:hidden;" v-if="moduleItemEditing.data.url==''" v-on:click="selectUrl(function(url){moduleItemEditing.data.url=url;})">[选择链接]</i-button>
            <i-button style="max-width:20em;overflow:hidden;" v-if="moduleItemEditing.data.url!=''" v-on:click="selectUrl(function(url){moduleItemEditing.data.url=url;})">@{{ moduleItemEditing.data.url }}</i-button>
        </Form-item>
        <Form-item>
            <i-button type="primary" html-type="button" v-on:click="save()">确定</i-button>
            <i-button html-type="button" v-on:click="cancel()">取消</i-button>
        </Form-item>
    </i-form>
</div>