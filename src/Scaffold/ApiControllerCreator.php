<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Support\Helper;

class ApiControllerCreator
{
    use GridCreator, FormCreator, ShowCreator;

    /**
     * Controller full name.
     *
     * @var string
     */
    protected $name;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    protected $stub;

    /**
     * ControllerCreator constructor.
     *
     * @param  string  $name
     * @param  null  $files
     */
    public function __construct($name, $files = null)
    {
        $this->name = $name;

        $this->files = $files ?: app('files');
    }

    /**
     * Create a controller.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \Exception
     */
    public function create($model,$table = '')
    {
        $path = $this->getPath($this->name);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($path)) {
            throw new AdminException("Controller [$this->name] already exists!");
        }

        $stub = $this->files->get($this->getStub());

        $slug = str_replace('Controller', '', class_basename($this->name));

        $model = $model ?: 'App\Admin\Repositories\\'.$slug;

        $this->files->put($path, $this->replace($stub, $this->name, $model, $slug,$table));
        $this->files->chmod($path, 0777);

        return $path;
    }

    /**
     * @param  string  $stub
     * @param  string  $name
     * @param  string  $model
     * @return string
     */
    protected function replace($stub, $name, $model, $slug,$table)
    {
        $stub = $this->replaceClass($stub, $name);
        $rules_arr = $this->generateValidationRulesAndMessages($table);
        return str_replace(
            [
                'DummyModelNamespace',
                'DummyModel',
                'DummyTitle',
                '{controller}',
                'DummyRules',
                'DummyRuleMsg',
                'DummyUpdateRequest',
                'DummyUpdateRules',
                'DummyUpdateMsg',
            ],
            [
                $model,
                class_basename($model),
                class_basename($model),
                $slug,
                $rules_arr['rules'],
                $rules_arr['rules_msg'],
                $rules_arr['update_request'],
                $rules_arr['update_rules'],
                $rules_arr['update_msg'],
            ],
            $stub
        );
    }

    /**
     * Get controller namespace from giving name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(['DummyClass', 'DummyNamespace'], [$class, $this->getNamespace($name)], $stub);
    }

    /**
     * Get file path from giving controller name.
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
    {
        return Helper::guessClassFileName($name);
    }

    public function setMemberApiStub(){
        $this->stub = __DIR__.'/stubs/MemberApiController.stub';
        return $this;
    }

    /**
     * Get stub file path.
     *
     * @return string
     */
    public function getStub()
    {
        if(!empty($this->stub)){
            return $this->stub;
        }else{
            return __DIR__.'/stubs/apicontroller.stub';
        }
    }

    /**
     * 根据当前model表结构，自动生成表单验证规则和提示信息
     * @return array ['rules' => '', 'rules_msg' => '', 'update_request' => '', 'update_rules' => '', 'update_msg' => '']
     */
    public function generateValidationRulesAndMessages($table)
    {
        //$table = $this->model->getTable();
        $fields = \DB::select("SHOW FULL COLUMNS FROM `{$table}`");

        // 需要排除的字段
        $excludeFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

        $rulesStr = "[\n";
        $rulesMsgStr = "[\n";

        // 用于 update 场景
        $updateRequestArr = [];
        $updateRequestArr[] = "            /**\n             * ID\n             */\n            'id'=> \$id,";

        $updateRulesArr = [];
        $updateRulesArr[] = "            'id' => ['required', 'integer', 'min:1','exists:' . \$this->model->getTable() . ',id'],";

        $updateMsgArr = [];
        $updateMsgArr[] = "            'id.required' => 'ID不能为空', \n            'id.integer'  => 'ID必须为整数', \n            'id.exists'   => '此ID不存在', \n";

        foreach ($fields as $field) {
            $name = $field->Field;

            // 跳过排除的字段
            if (in_array($name, $excludeFields)) {
                continue;
            }

            $type = $field->Type;
            $nullable = $field->Null === 'YES' ? true : false;
            $default = $field->Default;
            $comment = $field->Comment;
            $comment_docs = empty($field->Comment) ? $name : $field->Comment;
            $fieldRules = [];
            $fieldMsg = [];

            // 生成注释
            $docComment = '';
            if ($comment_docs) {
                $docComment .= "    /**\n";
                $docComment .= "     * {$comment_docs}\n";
                if ($default !== null) {
                    $docComment .= "     * @default {$default}\n";
                }
                $docComment .= "     */\n";
            }

            // 必填判断
            if (!$nullable && $default === null) {
                $fieldRules[] = 'required';
                $fieldMsg[] = "    '{$name}.required' => '" . ($comment ? $comment : $name) . "不能为空',";
            }

            // 类型判断
            if (preg_match('/int|bigint|tinyint|smallint|mediumint/i', $type)) {
                $fieldRules[] = 'integer';
                $fieldMsg[] = "    '{$name}.integer' => '" . ($comment ? $comment : $name) . "必须为整数',";
            } elseif (preg_match('/float|double|decimal/i', $type)) {
                $fieldRules[] = 'numeric';
                $fieldMsg[] = "    '{$name}.numeric' => '" . ($comment ? $comment : $name) . "必须为数字',";
            } elseif (preg_match('/char|text|varchar/i', $type)) {
                $fieldRules[] = 'string';
                $fieldMsg[] = "    '{$name}.string' => '" . ($comment ? $comment : $name) . "必须为字符串',";
                if (preg_match('/\((\d+)\)/', $type, $matches)) {
                    $max = $matches[1];
                    $fieldRules[] = "max:{$max}";
                    $fieldMsg[] = "    '{$name}.max' => '" . ($comment ? $comment : $name) . "不能超过{$max}个字符',";
                }
            } elseif (preg_match('/date|time|timestamp/i', $type)) {
                $fieldRules[] = 'date';
                $fieldMsg[] = "    '{$name}.date' => '" . ($comment ? $comment : $name) . "必须为日期格式',";
            }

            // 唯一性判断
            if (strpos($field->Key, 'UNI') !== false) {
                $fieldRules[] = "unique:{$table}";
                $fieldMsg[] = "    '{$name}.unique' => '" . ($comment ? $comment : $name) . "已存在',";
            }

            // 允许为空
            if ($nullable) {
                $fieldRules[] = 'nullable';
            }

            // 组装
            if (!empty($fieldRules)) {
                // 拼接规则字符串
                if ($docComment) {
                    $rulesStr .= $docComment;
                }
                $rulesStr .= "    '{$name}' => ['" . implode("','", $fieldRules) . "'],\n";
                // 拼接规则消息字符串
                if (!empty($fieldMsg)) {
                    $rulesMsgStr .= implode("\n", $fieldMsg) . "\n";
                }
            }

            // update_request 示例
            $updateRequestArr[] = "            /**\n             * {$comment_docs}" . ($default !== null ? "\n             * @default {$default}" : "") . "\n             */\n            '{$name}' => \$request->get('{$name}'),";

            // update_rules 示例
            $updateRulesArr[] = "            '{$name}' => ['" . implode("','", $fieldRules) . "'],";
            // update_msg 示例
            foreach ($fieldMsg as $msg) {
                $updateMsgArr[] = "            " . trim($msg);
            }
        }

        $rulesStr .= "]";
        $rulesMsgStr .= "]";

        // update_request
        $updateRequestStr = "[\n" . implode("\n", $updateRequestArr) . "\n        ]";
        // update_rules
        $updateRulesStr = "[\n" . implode("\n", $updateRulesArr) . "\n        ]";
        // update_msg
        $updateMsgStr = "[\n" . implode("\n", $updateMsgArr) . "\n        ]";

        return [
            'rules' => $rulesStr,
            'rules_msg' => $rulesMsgStr,
            'update_request' => $updateRequestStr,
            'update_rules' => $updateRulesStr,
            'update_msg' => $updateMsgStr,
        ];
    }
}
