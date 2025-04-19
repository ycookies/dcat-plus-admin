<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Admin;
use Dcat\Admin\Models\Setting as Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;

class Setting extends Fluent
{
    /**
     * 获取配置，并转化为数组.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return array
     */
    public function getArray($key, $default = [])
    {
        $value = $this->get($key, $default);

        if (! $value) {
            return [];
        }

        return is_array($value) ? $value : (json_decode($value, true) ?: []);
    }

    /**
     * 获取配置.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * 设置配置信息.
     *
     * @param  array  $data
     * @return $this
     */
    public function set($key, $value = null)
    {
        $data = is_array($key) ? $key : [$key => $value];

        foreach ($data as $key => $value) {
            Arr::set($this->attributes, $key, $value);
        }

        return $this;
    }

    /**
     * 追加数据.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @param  mixed  $k
     * @return $this
     */
    public function add($key, $value, $k = null)
    {
        $results = $this->getArray($key);

        if ($k !== null) {
            $results[] = $value;
        } else {
            $results[$k] = $value;
        }

        return $this->set($key, $results);
    }

    /**
     * 批量追加数据.
     *
     * @param  string  $key
     * @param  array  $value
     * @return $this
     */
    public function addMany($key, array $value)
    {
        $results = $this->getArray($key);

        return $this->set($key, array_merge($results, $value));
    }

    /**
     * 保存配置到数据库.
     *
     * @param  array  $data
     * @return $this
     */
    public function save(array $data = [])
    {
        if ($data) {
            $this->set($data);
        }

        foreach ($this->attributes as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $model = Model::query()
                ->where('slug', $key)
                ->first() ?: new Model();

            $model->fill([
                'slug'  => $key,
                'value' => (string) $value,
            ])->save();
        }

        return $this;
    }

    /**
     * @return static
     */
    public static function fromDatabase()
    {
        $values = [];

        try {
            $values = Model::pluck('value', 'slug')->toArray();
        } catch (QueryException $e) {
            Admin::reportException($e);
        }

        return new static($values);
    }

    /**
     * 获取多个键的值，返回键值对数组
     *
     * @param  array  $keys  要获取的键名数组
     * @return array
     */
    public function getMultiple(array $keys)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * 根据分组名称获取设置项列表（键值对）
     *
     * @param  string  $groupName  分组名称
     * @return array
     */
    public function getByGroup($groupName)
    {
        try {
            return Model::query()
                ->where('group_name', $groupName)
                ->pluck('value', 'slug')
                ->toArray();
        } catch (QueryException $e) {
            Admin::reportException($e);
            return [];
        }
    }

    /**
     * 按分组批量保存设置数据
     *
     * @param string $groupName 分组名称
     * @param array $groupData 分组数据 [key => value]
     * @return bool
     */
    public function groupSave($groupName, array $groupData)
    {
        if (empty($groupName)) {
            return false;
        }

        try {
            return Model::query()->getConnection()->transaction(function () use ($groupName, $groupData) {
                // 获取该分组下现有的所有slug
                $existingSlugs = Model::query()
                    ->where('group_name', $groupName)
                    ->pluck('slug')
                    ->toArray();

                // 需要删除的旧记录
                $toDelete = array_diff($existingSlugs, array_keys($groupData));
                if (!empty($toDelete)) {
                    Model::query()
                        ->where('group_name', $groupName)
                        ->whereIn('slug', $toDelete)
                        ->delete();
                }

                // 批量更新或创建记录
                foreach ($groupData as $slug => $value) {
                    $value = is_array($value) ? json_encode($value) : (string) $value;

                    // 手动实现updateOrCreate逻辑以确保兼容性
                    $model = Model::query()->where('slug', $slug)->first();

                    if ($model) {
                        // 更新现有记录
                        $model->update([
                            'value' => $value,
                            'group_name' => $groupName
                        ]);
                    } else {
                        // 创建新记录
                        Model::create([
                            'slug' => $slug,
                            'value' => $value,
                            'group_name' => $groupName
                        ]);
                    }
                }

                return true;
            });
        } catch (QueryException $e) {
            Admin::reportException($e);
            return false;
        }
    }
}
