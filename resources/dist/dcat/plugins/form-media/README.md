# Dcat-admin 表单媒体拓展


## 预览

### 表单
![form](https://user-images.githubusercontent.com/24578855/105875109-5aa30400-6038-11eb-9b5c-1c833e0c6b92.jpg)

### 动态表单
![form-array](https://user-images.githubusercontent.com/24578855/145700810-e648aa6e-4753-42a4-9fef-d5f397536ba3.jpg)

### 弹出选择框
![form-modal](https://user-images.githubusercontent.com/24578855/104125985-1277b680-5395-11eb-835b-c20e7c7585f9.jpg)


## 环境
 - PHP >= 7.2.5
 - Dcat-admin ^2.0


## 安装

### composer 安装

```
composer require lake/form-media
```

### 安装扩展

在 `开发工具->扩展` 安装本扩展


## 使用

#### 单图 数据库结构 varchar

##### 可删除

```
$form->photo('photo','图片')
    ->nametype('datetime')
    ->remove(true)
    ->help('单图，可删除');
```

##### 不可删除

```
$form->photo('photo','图片')
    ->path('pic') 
    ->nametype('uniqid') 
    ->remove(false)
    ->help('单图，不可删除');

$form->photo('photo','图片')
    ->nametype('uniqid') 
    ->help('单图，不可删除');
```

#### 多图 数据库结构 json

```
$form->photos('photo', '图片')
    ->path('pic') 
    ->pageSize(16)
    ->nametype('uniqid') 
    ->limit(9)
    ->remove(true);  //可删除
```

#### 视频 数据库结构 json/varchar

```
$form->video('video','视频')
    ->path('video') 
    ->nametype('uniqid') 
    ->remove(true);  //可删除
```

### 参数说明
```
path(string)         ： 快速定位目录，默认为根目录
nametype(string)     ： 文件重命名方式 uniqid|datetime|sequence|original，默认 uniqid
accept(string)       ： 设置 input accept 属性, 自定义，默认类型不设置
pageSize(int)        ： 弹出层列表每页显示数量
limit(int)           ： 限制条数
remove(boolean)      :  是否有删除按钮
saveFullUrl(boolean) :  是否保存完整链接
disk(string)         :  文件存储的磁盘，具体信息可以查看 `config/filesystems.php`

photo 、 photos 、 video 的参数默认值不一样

photo  默认 limit = 1  remove = false

photos 默认 limit = 9  remove = true

video  默认 limit = 1  remove = true
```

##### 多图上传提交的数据为 json 字符串，如需输出数组，请在对应模型中加入下面代码
```
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demo extends Model
{
    
    public function getPicturesAttribute($pictures)
    {

        return json_decode($pictures, true);

    }

}
```


## 开源协议

*  本扩展遵循 `MIT` 开源协议发布，在保留本扩展版权的情况下提供个人及商业免费使用。 


## 版权

*  该系统所属版权归 deatil(https://github.com/deatil) 所有。
