<div class="ex-page-view">

    <div v-show="module.length==0">
        <div class="ex-page-add" v-on:click="moduleAddDialogShow=true">
            <i class="uk-icon-plus"></i> 增加模块
        </div>
    </div>

    <div v-for="(moduleItem,moduleIndex) in module">
        {!! $manages !!}
    </div>

</div>

<div class="ex-page-edit" v-show="moduleItemEditing!=null">

    {!! $edits !!}

</div>

<Modal v-model="moduleAddDialogShow" title="选择模块" width="670" :styles="{top: '0px'}">


    <div class="ex-page-module">

        @foreach($groupModules as $g=>$ms)
            <div class="group">
                <div class="title">
                    {{$g}}
                </div>
                <div class="list">
                    {!! $ms['_modules'] !!}
                </div>
            </div>
        @endforeach

    </div>
    <div slot="footer">
        <i-button v-on:click="moduleAddDialogShow=false">关闭</i-button>
    </div>
</Modal>