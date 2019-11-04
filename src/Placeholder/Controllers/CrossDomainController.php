<?php

namespace Edwin404\Placeholder\Controllers;


use Carbon\Carbon;
use Edwin404\Base\Support\CurlHelper;
use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\InputPackage;
use EdwinFound\Utils\FileUtil;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Intervention\Image\Facades\Image;

class CrossDomainController extends Controller
{
    public function base64($url)
    {
        $url = base64_decode($url);
        $tempPath = FileHelper::savePathToLocal($url);
        if (file_exists($tempPath)) {
            $content = file_get_contents($tempPath);
            $mine = mime_content_type($tempPath);
            $base64 = base64_encode($content);
        } else {
            $mine = 'data/none';
            $base64 = '';
        }
        $prefix = 'data:' . $mine . ';base64,';
        $body = "if(!('__cross_domain_data' in window)){window.__cross_domain_data={};};window.__cross_domain_data['$url']={prefix:'$prefix',data:'$base64'};";
        return Response::make($body)
            ->header('Content-Type', 'application/javascript')
            ->setSharedMaxAge(30 * 24 * 3600);
    }
}