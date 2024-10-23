<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Exception\AdminException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Database updater.
 *
 * Executes database migration and seed scripts based on their filename.
 *
 * @author Alexey Bobkov, Samuel Georges
 */
class DatabaseUpdater
{
    /**
     * Sets up a migration or seed file.
     */
    public function setUp($file, \Closure $callback = null)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Model::unguard();

        // 开始数据库事务
        \DB::beginTransaction();

        try {
            if ($object instanceof Migration) {
                $object->up();
            } elseif ($object instanceof Seeder) {
                $object->run();
            }

            $callback && $callback();

            // 提交事务
            \DB::commit();
        } catch (\Exception $e) {
            // 回滚事务并记录错误
            \DB::rollBack();
            info($e->getMessage());
            return false;
        }

        Model::reguard();

        return true;
    }

    /**
     * Packs down a migration or seed file.
     */
    public function packDown($file, \Closure $callback = null)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Model::unguard();

        /*$this->transaction(function () use ($object, $callback) {
            if ($object instanceof Migration) {
                $object->down();
            }

            $callback && $callback();
        });

        Model::reguard();*/

        // 开始数据库事务
        \DB::beginTransaction();
        try {
            if ($object instanceof Migration) {
                $object->down();
            }
            $callback && $callback();
            // 提交事务
            \DB::commit();
        } catch (\Exception $e) {
            // 回滚事务并记录错误
            \DB::rollBack();
            info($e->getMessage());
            return false;
        }

        Model::reguard();

        return true;
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        if (is_object($file)) {
            return $file;
        }
    
        if (!is_file($file)) {
            return;
        }
    
        // 捕获 require_once 的返回值
        $result = require_once $file;
    
        // 如果返回的是对象（如匿名类的实例），直接返回
        if (is_object($result)) {
            return $result;
        }
    
        // 如果返回值不是对象，尝试通过 getClassFromFile 获取类名并实例化
        if ($class = $this->getClassFromFile($file)) {
            return new $class;
        }
    
        // 如果无法解析，返回 null 或抛出异常
        return null;
    }

    /**
     * Checks if the object is a valid update script.
     */
    protected function isValidScript($object)
    {
        if ($object instanceof Migration) {
            return true;
        } elseif ($object instanceof Seeder) {
            return true;
        }

        throw new AdminException(sprintf(
            'Database script [%s] must inherit %s or %s classes',
            get_class($object),
            Migration::class,
            Seeder::class
        ));
    }

    /**
     * Extracts the namespace and class name from a file.
     *
     * @param  string  $file
     * @return string
     */
    public function getClassFromFile($file)
    {
        $fileParser = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;

        while (! $class) {
            if (feof($fileParser)) {
                break;
            }

            $buffer .= fread($fileParser, 512);

            // Prefix and suffix string to prevent unterminated comment warning
            $tokens = token_get_all('/**/'.$buffer.'/**/');

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                /*
                 * Namespace opening
                 */
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === ';') {
                            break;
                        }

                        $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    }
                }

                /*
                 * Class opening
                 */
                if ($tokens[$i][0] === T_CLASS && $tokens[$i - 1][1] !== '::') {
                    $class = $tokens[$i + 2][1];
                    break;
                }
            }
        }

        if (! strlen(trim($namespace)) && ! strlen(trim($class))) {
            return false;
        }

        return trim($namespace).'\\'.trim($class);
    }

    public function transaction($callback)
    {
        return DB::connection($this->connection())->transaction($callback);
    }

    public function connection()
    {
        return config('admin.database.connection') ?: config('database.default');
    }
}
