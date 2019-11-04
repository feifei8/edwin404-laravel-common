<div class="ex-page-item" data-type="NavV" v-if="moduleItemEditing && moduleItemEditing.type=='NavV'" style="min-width:320px;">
    <i-form v-bind:label-width="80">
        <div v-for="(navVItem,navVIndex) in moduleItemEditing.data.list" style="border:1px solid #EEE;border-radius:3px;padding:10px;margin:10px 0;">
            <a v-show="navVIndex>0" href="javascript:;" v-on:click="moduleItemEditing.data.list.splice(navVIndex,1)"><i class="uk-icon-trash"></i></a>
            <Form-item v-bind:label="'文字'+(navVIndex+1)">
                <i-input v-model="navVItem.text"></i-input>
            </Form-item>
            <Form-item v-bind:label="'链接'+(navVIndex+1)">
                <i-button style="max-width:20em;overflow:hidden;" v-if="navVItem.url==''" v-on:click="selectUrl(function(url){navVItem.url=url;})">[选择链接]</i-button>
                <i-button style="max-width:20em;overflow:hidden;" v-if="navVItem.url!=''" v-on:click="selectUrl(function(url){navVItem.url=url;})">@{{ navVItem.url }}</i-button>
            </Form-item>
        </div>
        <Form-item>
            <a v-on:click="moduleItemEditing.data.list.push({image:'',url:''})"><i class="uk-icon-plus"></i> 新增</a>
        </Form-item>
        <Form-item>
            <i-button type="primary" html-type="button" v-on:click="save()">确定</i-button>
            <i-button html-type="button" v-on:click="cancel()">取消</i-button>
        </Form-item>
    </i-form>
</div>