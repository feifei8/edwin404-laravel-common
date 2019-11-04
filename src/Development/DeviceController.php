<?php

namespace Edwin404\Development;


use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;

class DeviceController extends Controller
{

    public function index()
    {
        $headers = [];
        foreach (Request::header() as $name => $value) {
            if (count($value) == 1) {
                $headers[$name] = $value[0];
            } else {
                $headers[$name] = $value;
            }
        }
        echo '<pre>';
        echo htmlspecialchars(print_r([
            'method' => Request::method(),
            'headers' => $headers
        ]));
        echo '</pre>';
    }

}