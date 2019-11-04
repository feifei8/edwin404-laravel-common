<?php

namespace Edwin404\EXField;


use Edwin404\Base\Support\Response;

class EXFieldUtil
{
    public static function buildTableFieldRow(&$data, $fieldModules, $prefix = 'fieldCustom', $fieldCount = 5)
    {
        if (empty($fieldModules)) {
            $fieldModules = [];
        }
        if (count($fieldModules) > $fieldCount) {
            return Response::generate(-1, '最多' . $fieldCount . '个自定义字段');
        }
        for ($i = 1; $i <= $fieldCount; $i++) {
            if (empty($fieldModules[$i - 1])) {
                $data[$prefix . $i] = '';
            } else {
                $data[$prefix . $i] = json_encode($fieldModules[$i - 1]);
            }
        }
        return Response::generate(0, 'ok');
    }

    public static function unbuildTableFieldRow(&$data, $prefix = 'fieldCustom', $fieldCount = 5)
    {
        $fieldModules = [];
        for ($i = 1; $i <= $fieldCount; $i++) {
            if (empty($data[$prefix . $i])) {
                continue;
            }
            $module = @json_decode($data[$prefix . $i], true);
            if (empty($module)) {
                continue;
            }
            $fieldModules[] = $module;
        }
        $data['_' . $prefix] = $fieldModules;
        return Response::generate(0, 'ok');
    }

    public static function pair($KeyData, $ValueData, $prefix = 'fieldCustom', $fieldCount = 5)
    {
        $pairs = [];
        for ($i = 1; $i <= $fieldCount; $i++) {
            if (empty($KeyData[$prefix . $i])) {
                continue;
            }
            $module = @json_decode($KeyData[$prefix . $i], true);
            if (empty($module)) {
                continue;
            }
            $pairs[] = [
                'name' => $module['title'],
                'value' => $ValueData[$prefix . $i],
            ];
        }
        return $pairs;
    }

    public static function checkAndReturnFieldArray($fields, $limit = 5)
    {
        if (empty($fields)) {
            return Response::generate(0, null, []);
        }
        if (count($fields) > 5) {
            return Response::generate(-1, '自定义字段最多' . $limit . '个');
        }
        foreach ($fields as $index => &$field) {
            if (empty($field) || empty($field['type'])) {
                return Response::generate(-1, "第" . ($index + 1) . '个自定义字段设置错误');
            }
            if (empty($field['title'])) {
                return Response::generate(-1, "第" . ($index + 1) . '个自定义字段标题为空');
            }
            $field['title'] = trim($field['title']);
            switch ($field['type']) {
                case 'Text':
                    break;
                case 'Radio':
                    $option = [];
                    if (empty($field['data']['option'])) {
                        $field['data']['option'] = [];
                    }
                    foreach ($field['data']['option'] as $item) {
                        if (empty($item)) {
                            continue;
                        }
                        $option[] = $item;
                    }
                    $field['data']['option'] = $option;
                    if (empty($field['data']['option'])) {
                        return Response::generate(-1, '自定义字段' . $field['title'] . '选项为空');
                    }
                    break;
                default:
                    return Response::generate(-1, "第" . ($index + 1) . '个自定义字段标题为空');
            }
        }
        return Response::generate(0, null, $fields);
    }

}