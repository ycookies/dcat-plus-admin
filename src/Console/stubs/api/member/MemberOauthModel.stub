<?php

namespace App\Models;

use Dcat\Admin\Traits\HasDateTimeFormatter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MemberOauth extends Model
{
	use HasDateTimeFormatter;
    protected $table = 'member_oauth';

    // 授权登录类型常量定义
    const WX = 'wx';               // 微信
    const WX_MINI = 'wx_mini';      // 微信小程序
    const ALIPAY = 'alipay';        // 支付宝
    const QQ = 'qq';                // QQ
    const WEIBO = 'weibo';          // 微博
    const BAIDU = 'baidu';          // 百度
    const DOUYIN = 'douyin';        // 抖音
    const TOUTIAO = 'toutiao';      // 今日头条
    const XIAOMI = 'xiaomi';        // 小米
    const HUAWEI = 'huawei';        // 华为
    const JD = 'jd';                // 京东
    const DINGTALK = 'dingtalk';    // 钉钉
    const FEISHU = 'feishu';        // 飞书
    const KUAISHOU = 'kuaishou';    // 快手
    const PDD = 'pdd';              // 拼多多
    const GITHUB = 'github';        // GitHub
    const GOOGLE = 'google';        // Google

    // 可选：提供一个映射数组，方便获取名称
    public static $typeMap = [
        self::WX        => '微信',
        self::WX_MINI   => '微信小程序',
        self::ALIPAY    => '支付宝',
        self::QQ        => 'QQ',
        self::WEIBO     => '微博',
        self::BAIDU     => '百度',
        self::DOUYIN    => '抖音',
        self::TOUTIAO   => '今日头条',
        self::XIAOMI   => '小米',
        self::HUAWEI   => '华为',
        self::JD        => '京东',
        self::DINGTALK  => '钉钉',
        self::FEISHU    => '飞书',
        self::KUAISHOU => '快手',
        self::PDD       => '拼多多',
        self::GITHUB    => 'GitHub',
        self::GOOGLE   => 'Google',
    ];

    // 可以被批量赋值的属性 也方便查看表所有字段及注释
   /** protected $fillable = [
        'member_user_id', // 用户ID
        'type', // 类型
        'open_id', // OpenId
        'info_nick', // 昵称
        'info_avatar', // 头像
    ]; */

    protected $guarded = []; // 批量赋值的黑名单
    // protected $fillable = []; // 可以作为批量赋值的白名单
    // protected $appends = []; // 追加属性
    // protected $hidden = []; // 数组中的属性会被隐藏

    // 获取所有类型（可用于下拉选项）
    public static function getTypes()
    {
        return (new static)->type;
    }

    // 获取类型名称
    public function getTypeNameAttribute()
    {
        return $this->type[$this->attributes['type'] ?? '未知';
    }
}
