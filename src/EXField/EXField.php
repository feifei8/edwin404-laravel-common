<?php

namespace Edwin404\EXField;


use Illuminate\Support\Facades\View;

class EXField
{
    private $appModule = '';

    private $viewBases = [];
//    private $dynamicGenerators = [];
//
    public function __construct($viewBases = [])
    {
        if (empty($viewBases)) {
            $viewBases = [];
        } else {
            if (!is_array($viewBases)) {
                $viewBases = [$viewBases];
            }
        }
        if (!in_array('common::exfield', $viewBases)) {
            $viewBases[] = 'common::exfield';
        }
        $this->viewBases = $viewBases;
    }

    public function setAppModule($appModule)
    {
        $this->appModule = $appModule;
        return $this;
    }
//
//    public function setDynamicGenerator($modules, $callback)
//    {
//        if (!is_array($modules)) {
//            $modules = [$modules];
//        }
//        foreach ($modules as $module) {
//            $this->dynamicGenerators[$module] = $callback;
//        }
//    }


    private function renderEditorEdit($modules = [])
    {
        $html = [];
        foreach ($modules as $module => $moduleName) {
            foreach ($this->viewBases as $viewBase) {
                if (view()->exists($view = $viewBase . '.edit.' . $module)) {
                    $html[] = View::make($view, [
                        'appModule' => $this->appModule,
                    ])->render();
                    break;
                }
            }
        }
        return join("", $html);
    }

    private function renderEditorManage($modules = [])
    {
        $html = [];
        foreach ($modules as $module => $moduleName) {
            foreach ($this->viewBases as $viewBase) {
                if (view()->exists($view = $viewBase . '.manage.' . $module)) {
                    $html[] = View::make($view, [
                        'appModule' => $this->appModule,
                    ])->render();
                    break;
                }
            }
        }
        return join("", $html);
    }

//    private function renderEditorModule($modules = [])
//    {
//        $html = [];
//        foreach ($modules as $module) {
//            foreach ($this->viewBases as $viewBase) {
//                if (view()->exists($view = $viewBase . '.module.' . $module)) {
//                    $html[] = View::make($view, [])->render();
//                    break;
//                }
//            }
//        }
//        return join("", $html);
//    }
//
    /**
     * 渲染后台可视化编辑器
     */
    public function renderEditor($modules = [])
    {
        return View::make('common::exfield.view', [
            'edits' => $this->renderEditorEdit($modules),
            'manages' => $this->renderEditorManage($modules),
            'modules' => $modules,
            'appModule' => $this->appModule,
        ]);
    }

    public function render($keyModuleMap)
    {
        if (empty($keyModuleMap) || !is_array($keyModuleMap)) {
            return null;
        }
        $html = [];
        foreach ($keyModuleMap as $key => $field) {
            if (empty($field['type'])) {
                continue;
            }
            $type = $field['type'];
            $html[] = '<!-- ' . $type . '-' . $key . ' start -->';
            foreach ($this->viewBases as $viewBase) {
                if (view()->exists($view = $viewBase . '.view.' . $type)) {
                    $data = empty($field['data']) ? null : $field['data'];
                    $title = empty($field['title']) ? null : $field['title'];
                    $html[] = View::make($view, [
                        'title' => $title,
                        'key' => $key,
                        'data' => $data,
                    ])->render();
                    break;
                }
            }
            $html[] = '<!-- ' . $type . '-' . $key . ' end -->';
        }
        return join("\n", $html);
    }

}