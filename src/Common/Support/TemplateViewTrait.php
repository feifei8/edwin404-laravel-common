<?php

namespace Edwin404\Common\Support;


use Edwin404\Common\Helpers\AgentHelper;
use Edwin404\Config\Facades\ConfigFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Jenssegers\Agent\Facades\Agent;

trait TemplateViewTrait
{
    public function detectDevice()
    {
        $userAgent = Request::header('User-Agent');
        echo 'UserAgent : ' . $userAgent . '<br />';
        echo 'isMobile : ' . (Agent::isMobile() ? 'true' : 'false') . '<br />';
        echo 'isTablet : ' . (Agent::isTablet() ? 'true' : 'false') . '<br />';
        echo 'AgentHelper.isMobile : ' . (AgentHelper::isMobile() ? 'true' : 'false') . '<br />';
        echo 'AgentHelper.isPC : ' . (AgentHelper::isPC() ? 'true' : 'false') . '<br />';
    }

    protected function _view($view, $viewData = [])
    {
        $template = ConfigFacade::get('siteTemplate', 'default');

        $mobileView = 'theme.' . $template . '.m.' . $view;
        $PCView = 'theme.' . $template . '.pc.' . $view;

        $defaultMobileView = 'theme.default.m.' . $view;
        $defaultPCView = 'theme.default.pc.' . $view;

        if ($this->isMobile()) {
            $frameLayoutView = 'theme.' . $template . '.m.frame';
            if (!view()->exists($frameLayoutView)) {
                $frameLayoutView = 'theme.default.m.frame';
            }
        } else {
            $frameLayoutView = 'theme.' . $template . '.pc.frame';
            if (!view()->exists($frameLayoutView)) {
                if (view()->exists('theme.' . $template . '.m.frame')) {
                    $frameLayoutView = 'theme.' . $template . '.m.frame';
                }
            }
        }
        if (!view()->exists($frameLayoutView)) {
            $frameLayoutView = 'theme.default.pc.frame';
        }
        View::share('_frameLayoutView', $frameLayoutView);

        if ($this->isMobile()) {
            if (view()->exists($mobileView)) {
                return view($mobileView, $viewData);
            }
            if (view()->exists($defaultMobileView)) {
                return view($defaultMobileView, $viewData);
            }
        }
        if (view()->exists($PCView)) {
            return view($PCView, $viewData);
        } else {
            if (view()->exists($mobileView)) {
                return view($mobileView, $viewData);
            }
            if (view()->exists($defaultMobileView)) {
                return view($defaultMobileView, $viewData);
            }
        }
        return view($defaultPCView, $viewData);
    }

    public function _viewRender($view, $viewData)
    {
        $template = ConfigFacade::get('siteTemplate', 'default');

        $mobileView = 'theme.' . $template . '.m.' . $view;
        $PCView = 'theme.' . $template . '.pc.' . $view;

        $defaultMobileView = 'theme.default.m.' . $view;
        $defaultPCView = 'theme.default.pc.' . $view;

        if ($this->isMobile()) {
            if (view()->exists($mobileView)) {
                return View::make($mobileView, $viewData)->render();
            }
            if (view()->exists($defaultMobileView)) {
                return View::make($defaultMobileView, $viewData)->render();
            }
        }
        if (view()->exists($PCView)) {
            return View::make($PCView, $viewData)->render();
        } else {
            if (view()->exists($mobileView)) {
                return View::make($mobileView, $viewData)->render();
            }
            if (view()->exists($defaultMobileView)) {
                return View::make($defaultMobileView, $viewData)->render();
            }
        }
        return View::make($defaultPCView, $viewData)->render();
    }

    public function isMobile()
    {
        return AgentHelper::isMobile();
    }

    public function isPC()
    {
        return AgentHelper::isPC();
    }

}