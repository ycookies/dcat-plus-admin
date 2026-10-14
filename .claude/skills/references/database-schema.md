# MySQL 数据结构与建表规范

## 适用范围

- 本规则适用于本项目中所有新建数据表、修改表结构的 Laravel migration 与原生 SQL。
- 建表目标不止是"能存数据"，还要让 Dcat Plus Admin **智能脚手架**（`/admin/helpers/scaffold`）能从字段名、数据库类型、字段注释自动生成合适的 Grid / Form / Show，减少生成后的人工补充。
- 已知本项目数据库表已安装完成，**禁止主动执行** `php artisan migrate`、`migrate:fresh`、`db:wipe`、`db:seed` 等修改库结构的命令，除非用户明确要求。

## 一、通用约定

- 引擎：`InnoDB`；字符集：`utf8mb4`；排序规则：`utf8mb4_unicode_ci`。
- 每张表必须有 `id`（无符号大整型主键，自增）和 `created_at` / `updated_at`（`timestamp NULL`），软删除场景加 `deleted_at` 并建索引。
- 表名 `snake_case`、复数或业务实体名（如 `fishing_ponds`、`competition_orders`）；字段名 `snake_case`。
- 每张表必须有**表注释**（`COMMENT='...'`），说明业务实体；每个字段必须有**字段注释**，说明业务含义。
- 字段命名见下方"字段语义适配"，便于脚手架识别；不要依赖模糊简称（如 `stat`、`flg`）。

## 二、字段语义适配（脚手架识别规则）

建表时字段名 / 类型 / 注释应能帮助脚手架自动生成 CRUD，**优先级**从高到低：

1. Scaffold 页面"语义类型"人工选择；
2. 字段注释中的 `@scaffold:type` 指令；
3. 字段注释中的 `@scaffold:options` 状态/枚举选项；
4. 数据库类型；
5. 高置信度的字段命名；
6. 普通文本字段回退。

| 业务含义 | 推荐字段名 / 类型 | 注释建议 |
| --- | --- | --- |
| 主键 | `id` bigint unsigned | 主键 |
| 图片 | `avatar`、`cover_image`、`banner_img`、`logo`、`thumbnail`，或以 `_img` / `_image` 结尾；`varchar(191)` | 头像 / 封面图 / Banner 图 |
| 文件 | `attachment_file`、`document`、`file`，或以 `_file` 结尾；`varchar(191)` | 附件 / 文档 |
| 邮箱 | `email`；`varchar(191)` | 邮箱 |
| 手机/电话 | `mobile`、`phone`、`tel`；`varchar(20)` | 手机号 |
| 链接 | `url`、`website`、`homepage`、`link`；`varchar(191)` | 个人主页 |
| 密码 | `password`、`passwd`、`pwd`；`varchar(191)` | 密码 |
| 颜色 | `color`；`varchar(20)` | 主题色 |
| 布尔值 | `is_enabled`、`is_visible`、`active`、`has_xxx`；`tinyint(1)` 默认 0/1 | 是否启用 |
| 日期 | `date` 类型，或 `*_date` | 发布日期 |
| 日期时间 | `datetime` / `timestamp`，或 `*_at` | 事件时间 |
| 时间 | `time` 类型，或 `*_time` | 工作时间 |
| 整数 | `int`；如 `view_count` | 浏览量 |
| 金额/小数 | `decimal(M,2)`；如 `amount`、`price`、`rate` | 价格 / 金额（金额建议 `decimal(12,2)`，单价 `decimal(10,2)`） |
| JSON | `json`；如 `metadata`、`settings`、`extra`、`payload` | 元数据 / 配置 |
| 长文本 | `text` / `mediumText` / `longText`；如 `content`、`description`、`remark`、`body` | 正文内容 / 描述 |
| 普通文本 | `varchar(191)` | 按业务命名（如 `nickname` 昵称） |

> `status` / `state` **本身不会**被自动解释为"启用/禁用"。只有配置 `@scaffold:options` 时才会生成 `select` + `using(...)->label()`。

## 三、状态与枚举字段

- 有固定选项的状态字段，必须在注释中写明 `@scaffold:options`（结构化 JSON 对象）：

```sql
`publish_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '发布状态 @scaffold:options={"0":"草稿","1":"已发布","2":"已下架"}',
`audit_status`  tinyint(4) NOT NULL DEFAULT '0' COMMENT '审核状态 @scaffold:options={"0":"待审核","1":"通过","2":"拒绝"}',
```

- 字段名不符合约定时，用 `@scaffold:type` 强制指定控件类型：

```sql
`promo_asset` varchar(191) DEFAULT NULL COMMENT '宣传素材 @scaffold:type=image',
```

- 支持的 type：`auto, text, long_text, integer, decimal, image, file, url, email, phone, password, color, status, boolean, date, datetime, time, json`。
- 指令前的文字保留为字段说明 / 标签；业务说明在前，`@scaffold:` 指令在后。
- **禁止**把 HTML / PHP / JavaScript / SQL 片段放进字段名、注释或枚举标签。
- **不要**只写 `0禁用1启用`、`状态` 等非结构化描述——脚手架不会猜测枚举含义。

## 四、标准建表示例（全量字段对照样板）

下面这张 `scaffold_test` 表覆盖了脚手架可识别的全部语义字段，**新建表时按需挑字段对照**，类型 / 长度 / 注释格式保持一致。

```sql
CREATE TABLE `scaffold_test` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  -- 图片类（varchar(191)，脚手架生成 image 控件）
  `avatar`          varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '头像',
  `cover_image`     varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面图',
  `banner_img`      varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Banner 图',
  `logo`            varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Logo',
  `thumbnail`       varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '缩略图',
  `promo_asset`     varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '宣传素材 @scaffold:type=image',
  -- 文件类
  `attachment_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '附件',
  `document`        varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文档',
  -- 格式化输入类
  `email`           varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `mobile`          varchar(20)  COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '手机号',
  `website`         varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '个人主页',
  `password`        varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '密码',
  `color`           varchar(20)  COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '主题色',
  -- 布尔类（tinyint(1)，默认 0/1，生成 switch）
  `is_enabled`      tinyint(1)   NOT NULL DEFAULT '1' COMMENT '是否启用',
  `is_visible`      tinyint(1)   NOT NULL DEFAULT '0' COMMENT '是否可见',
  `active`          tinyint(1)   NOT NULL DEFAULT '1' COMMENT '是否激活',
  -- 状态 / 枚举类（必须写 @scaffold:options，否则不会被识别为下拉）
  `publish_status`  tinyint(4)   NOT NULL DEFAULT '0' COMMENT '发布状态 @scaffold:options={"0":"草稿","1":"已发布","2":"已下架"}',
  `audit_status`    tinyint(4)   NOT NULL DEFAULT '0' COMMENT '审核状态 @scaffold:options={"0":"待审核","1":"通过","2":"拒绝"}',
  -- 日期 / 时间类
  `publish_date`    date     DEFAULT NULL COMMENT '发布日期',
  `event_at`        datetime DEFAULT NULL COMMENT '事件时间',
  `work_time`       time     DEFAULT NULL COMMENT '工作时间',
  -- 数值类（可排序）
  `view_count`      int(11)      NOT NULL DEFAULT '0' COMMENT '浏览量',
  `price`           decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `amount`          decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  -- JSON 类
  `metadata`        json DEFAULT NULL COMMENT '元数据',
  `settings`        json DEFAULT NULL COMMENT '配置',
  -- 长文本类
  `content`         text COLLATE utf8mb4_unicode_ci COMMENT '正文内容',
  `description`     text COLLATE utf8mb4_unicode_ci COMMENT '描述',
  -- 普通文本（脚手架回退为普通输入框）
  `nickname`        varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '昵称',
  `custom_field`    varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '自定义字段',
  -- 时间戳
  `created_at`      timestamp NULL DEFAULT NULL,
  `updated_at`      timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='脚手架字段识别全量测试表';
```

## 五、Laravel migration 写法

本项目统一用 migration，**禁止使用匿名类**（`return new class extends Migration`）。必须定义为具名类并继承 `Migration`，类名与文件名保持一致。字段注释用 `->comment()` 承载业务说明与 `@scaffold:` 指令。

**一个 migration 文件只对应一张表**：`up()` 内只允许出现一张表的 `Schema::create` 或针对单张表的字段变更，**禁止把多张表的建表语句写进同一文件**。文件名按 `create_<表名>_table` / `add_<表名>_<说明>_fields` 命名，类名对应（如 `CreateFishingPondsTable`），目的是让 AI 和维护者能通过表名直接定位到文件、快速查看字段定义。

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFishingPondsTable extends Migration
{
    public function up(): void
    {
        Schema::create('fishing_ponds', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('钓场名称');
            $table->string('cover_image')->nullable()->comment('封面图');
            $table->string('mobile', 20)->nullable()->comment('联系电话');
            $table->boolean('is_enabled')->default(1)->comment('是否启用');
            $table->tinyInteger('audit_status')->default(0)
                ->comment('审核状态 @scaffold:options={"0":"待审核","1":"通过","2":"拒绝"}');
            $table->decimal('amount', 12, 2)->default(0)->comment('金额');
            $table->json('settings')->nullable()->comment('配置');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fishing_ponds');
    }
}
```

要点：
- 主键用 `$table->id()`；时间戳用 `$table->timestamps()`；软删除用 `$table->softDeletes()`。
- 可空字段显式 `->nullable()`，禁止依赖 MySQL 隐式默认。
- 金额、库存等业务字段必须给 `->default()`；金额用 `decimal`，**禁止用 float / double 存金额**。
- 布尔语义字段用 `$table->boolean()`（映射 `tinyint(1)`），不要用 `tinyInteger(4)` 冒充布尔——除非是枚举状态。
- 历史数据回填、兼容旧数据、不可逆操作另建独立迁移，注释标注是否可重复执行（幂等）。
- 迁移只做结构变更；种子数据走 seeder，不混在 migration 里。

## 六、索引、外键与安全

- 高频查询条件（`tenant_id`、`status`、`*_at`、外键列）必须建索引；组合查询用联合索引，注意最左前缀顺序。
- 字符串索引注意长度：本项目曾出现索引长度限制问题，`varchar` 索引控制在 `191` 或显式指定前缀长度。
- 谨慎使用物理外键：多租户、分库场景优先应用层校验，不用 `FOREIGN KEY` 约束，避免迁移与分表受阻。
- 排序字段若会暴露给 API，必须在模型 `getSortableFields()` 白名单中登记，禁止任意字段 `orderBy`。

## 七、生成后仍需人工补充

以下无法从表结构可靠推断，须在生成的控制器 / 模型中手工实现：

```php
// 关联：user_id 不会自动生成下拉，需显式 options
$form->select('user_id')->options(User::query()->pluck('name', 'id'));

// 上传目录 / 磁盘
$form->image('cover_image')->disk('public')->dir('ponds');

// 富文本
$form->editor('content');

// 多图：需模型 cast + 存储格式
$form->multipleImage('gallery')->removable();
```
