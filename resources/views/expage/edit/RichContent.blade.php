<div class="ex-page-item" data-type="RichContent" v-show="moduleItemEditing && moduleItemEditing.type=='RichContent'">
    <div>
        <script type="text/plain" data-content style="width:320px;"></script>
    </div>
    <i-form label-position="top">
        <Form-item>
            <i-button type="primary" html-type="button" v-on:click="save()">确定</i-button>
            <i-button html-type="button" v-on:click="cancel()">取消</i-button>
        </Form-item>
    </i-form>
</div>