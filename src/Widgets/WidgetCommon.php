<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

trait WidgetCommon{

    public function ajax($apiurl,$param = [],$method = 'POST',$headers = []){
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $apiurl,[
            'verify' => false,
            'timeout' => 10,
            'headers' => $headers,
            'form_params'=> $param,
        ]);
        $res = $response->getBody()->getContents();
        return json_decode($res,true);
    }
}
