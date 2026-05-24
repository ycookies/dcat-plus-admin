<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Traits\InteractsWithApi;
use Exception;
use Illuminate\Http\Request;

class ValueController
{
    /**
     * @param  Request  $request
     * @return mixed
     */
    public function handle(Request $request)
    {
        $instance = $this->resolve($request);

        if (! $instance->passesAuthorization()) {
            return $instance->failedAuthorization();
        }

        $response = $instance->handle($request);

        if ($response) {
            return $response;
        }

        if (method_exists($instance, 'valueResult')) {
            return $instance->valueResult();
        }
    }

    /**
     * @param  Request  $request
     * @return \Dcat\Admin\Traits\InteractsWithApi
     *
     * @throws Exception
     */
    protected function resolve(Request $request)
    {
        if (! $key = $request->input('_key')) {
            throw new Exception('Invalid request.');
        }

        if (! class_exists($key)) {
            throw new Exception("Class [{$key}] does not exist.");
        }

        // 安全检查：验证类必须使用 InteractsWithApi trait，防止任意类实例化
        if (! in_array(InteractsWithApi::class, class_uses_recursive($key))) {
            throw new AdminException("Class [{$key}] must use trait " . InteractsWithApi::class);
        }

        $instance = app($key);

        if (! method_exists($instance, 'handle')) {
            throw new Exception("The method '{$key}::handle()' does not exist.");
        }

        return $instance;
    }
}
