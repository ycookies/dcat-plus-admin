<?php

namespace Dcat\Admin\Exception;

use Exception;

class ApiException extends Exception
{
    protected $code = 422;
    protected $data = [];

    public function __construct($message = "", $code = 422, $data = [])
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    public function render($request)
    {
        $datas = $this->data;
        if(empty($this->data)){
            $datas = (object)[];
        }
        return response()->json([
            'code'    => 422,
            'status'  => 'error',
            'message' => $this->getMessage(),
            'data'    => $this->data,
        ], 422);
    }
}