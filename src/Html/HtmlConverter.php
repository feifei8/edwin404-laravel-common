<?php

namespace Edwin404\Html;

use Edwin404\Base\Support\HtmlHelper;

class HtmlConverter
{
    public static function convertToHtml($contentType,
                                         $content,
                                         $interceptors = null)
    {
        switch ($contentType) {
            case HtmlType::RICH_TEXT:
                $html = HtmlHelper::filter2($content);
                break;
            case HtmlType::MARKDOWN:
                $parsedown = new \Parsedown();
                $html = $parsedown->setBreaksEnabled(true)->text($content);
                $html = HtmlHelper::filter($html);
                break;
            case HtmlType::SIMPLE_TEXT:
                $html = HtmlHelper::text2html($content);
                break;
            default:
                throw new \Exception('HtmlConverter.convertToHtml contentType error');
        }
        if (!empty($interceptors)) {
            if (is_array($interceptors)) {
                foreach ($interceptors as $interceptor) {
                    $ins = new $interceptor();
                    $html = $ins->convert($html);
                }
            } else {
                $ins = new $interceptors();
                $html = $ins->convert($html);
            }

        }
        return $html;
    }
}