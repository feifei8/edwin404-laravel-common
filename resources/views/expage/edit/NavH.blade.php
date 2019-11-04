<div class="ex-page-item" data-type="NavH" v-if="moduleItemEditing && moduleItemEditing.type=='NavH'" style="min-width:320px;">
    <i-form v-bind:label-width="80">
        <div v-for="(sliderItem,sliderIndex) in moduleItemEditing.data.list" style="border:1px solid #EEE;border-radius:3px;padding:10px;margin:10px 0;">
            <Form-item v-bind:label="'图片'+(sliderIndex+1)">
                <div class="ex-page-image-selector">
                    <span v-if="sliderItem.image==''" class="image" v-bind:style="{backgroundImage:'url(/assets/lib/img/none.png)'}"></span>
                    <span v-if="sliderItem.image!=''" class="image" v-bind:style="{backgroundImage:'url('+sliderItem.image+')'}"></span>
                    <span class="add" v-on:click="selectImage(function(image){sliderItem.image=image;})"><i class="uk-icon-plus"></i></span>
                    <span class="delete" v-on:click="sliderItem.image=''"><i class="uk-icon-remove"></i></span>
                </div>
            </Form-item>
            <Form-item v-bind:label="'文字'+(sliderIndex+1)">
                <i-input v-model="sliderItem.text"></i-input>
            </Form-item>
            <Form-item v-bind:label="'链接'+(sliderIndex+1)">
                <i-button style="max-width:20em;overflow:hidden;" v-if="sliderItem.url==''" v-on:click="selectUrl(function(url){sliderItem.url=url;})">[选择链接]</i-button>
                <i-button style="max-width:20em;overflow:hidden;" v-if="sliderItem.url!=''" v-on:click="selectUrl(function(url){sliderItem.url=url;})">@{{ sliderItem.url }}</i-button>
            </Form-item>
        </div>
        <Form-item>
            <i-button type="primary" html-type="button" v-on:click="save()">确定</i-button>
            <i-button html-type="button" v-on:click="cancel()">取消</i-button>
        </Form-item>
    </i-form>
</div>