<?php

namespace Dcat\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Dedoc\Scramble\Attributes\SchemaName;

#[SchemaName('统一接口响应格式')]
class ApiResource extends JsonResource
{
    protected $status = 'success';
    protected $msg = 'ok';
    protected $code = 0;
    protected $pagination;
    protected $data;

    public function __construct( $data = [],$code = 0, $status = 'success', $msg = 'ok',)
    {

        $this->status = $status;
        $this->msg = $msg;
        $this->code = $code;
        $this->data = $data;
    }
    public function toArray(Request $request): array
    {
        return [
            /**
             * 响应码
             */
            'code' => $this->code,
            /**
             * 状态
             */
            'status' => $this->status,
            /**
             * 提示信息
             */
            'msg' => $this->msg,
            /**
             * 数据集
             */
            'data' => $this->data,
        ];
    }
}
