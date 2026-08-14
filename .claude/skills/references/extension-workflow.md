# 扩展包开发工作流（AI 引导文档）

> ## 🎯 这份文档是干什么的（先读我）
>
> 这是 **AI 用一句话指令开发 dcat-plus-admin 扩展包的入口文档**。当用户说出类似下面这句话时，你（AI）就按本文档执行：
>
> ```
> 开发 <系统名>，讨论确认功能后，按 extension-workflow.md 工作流完成开发上线。
> ```
>
> **你的执行顺序**：
> 1. 不要急着写代码——先做「阶段 0 · 业务讨论」，和用户对齐功能模块与数据表，**等用户确认后**再动手。
> 2. 从阶段 1 起逐阶段推进：建骨架+建表 → 后台 CRUD → 业务逻辑 → 接口 → 上线。
> 3. 每完成一阶段，勾选该扩展包的 `docs/tasks/PLAN.md` 对应项；**自检不过不进下一阶段**。
> 4. 每个阶段都有「产物」「自检」「该查哪份 references」，按图索骥，不要自己发明约定。
>
> **换业务只需改一句话里的 `<系统名>`**，流程不变。

---

> 本文档是 **AI（如 seed-code）按一句话指令开发 dcat-plus-admin 扩展包的标准流程**。
> 一句话指令示例：`开发 内容管理系统，讨论确认功能后，按 extension-workflow.md 工作流完成开发上线。`
> 你的任务：执行下方的「阶段 0：业务讨论」→ 逐阶段推进 → 每完成一阶段更新该扩展包的 `docs/tasks/PLAN.md` 勾选项 → 全部完成后上线。
> **每个阶段都有「产物」和「自检」**，自检不通过不要进入下一阶段。

---

## 开始之前：定位本流程在项目中的角色

- 本流程**只规定"做什么、按什么顺序、产物落哪、怎么自检"**，具体怎么写代码请查对应的 references：
  - 建表 → [database-schema.md](database-schema.md)
  - 后台 CRUD（Grid/Form/Show） → [form-fields.md](form-fields.md)、[grid-system.md](grid-system.md)
  - 接口 → [api-development.md](api-development.md)
  - 扩展机制（ServiceProvider/路由/菜单/迁移登记） → [extension-development.md](extension-development.md)
  - 异步动作 → [action-system.md](action-system.md)
  - 脚手架命令用法（`admin:scaffold`） → [scaffold-command.md](../../../../vendor/dcat-plus/laravel-admin/docs/scaffold-command.md)
- 扩展包用 `admin:ext-make-pro` 命令创建（见阶段 1）。命令会自动生成 `docs/BLUEPRINT.md`（总纲）和 `docs/tasks/PLAN.md`（进度看板）。**本流程与 PLAN.md 的阶段一一对应，完成后必须同步勾选 PLAN.md**。
- **两把生成器分工**：`admin:ext-make-pro` 负责「建包 + 建表 + 文档蓝图」（阶段 1，只在开包时用一次）；`admin:scaffold --extension=` 负责「写业务代码」（阶段 2 后台 CRUD、阶段 4 接口，按表反复用）。**写业务代码时一律走 `admin:scaffold`，不要手写 Controller/Resource 骨架。**

---

## 阶段 0 · 业务讨论（开发前必做，不要跳过）

**目标**：和用户对齐"开发什么"，形成可追溯的文字记录，避免边写边改。

1. 向用户确认以下信息（一次问清，不要挤牙膏）：
   - 系统/扩展的中文名、用途、目标用户。
   - 核心功能模块清单（每个模块对应一张或多张表）。
   - 是否需要移动端接口（C 端 member-api / 管理端 admin-api）。
   - 是否要发布到应用市场（决定是否生成落地页与介绍）。
2. **产物**：在对话里形成一份「功能模块 + 数据表」草案（模块名 → 表名 → 关键字段），并复述给用户确认。用户确认后才进入阶段 1。
3. **自检**：用户明确说"可以开始"或"确认"，否则继续讨论。

> 例如用户说"开发内容管理系统"，你应产出："文章管理→articles 表(title/content/cover_image/status)；分类管理→categories 表(name/sort)；是否需要小程序端文章接口？" 然后等用户确认。

---

## 阶段 1 · 创建扩展骨架 + 数据层

### 1.1 创建扩展包

根据阶段 0 的结论拼命令。常用形式：

```bash
# 基础（带后台）
php artisan admin:ext-make-pro <vendor>/<name> \
  --models=<已有表名，逗号分隔> \
  --seed \
  --blueprint // 带移动端接口
  --marketplace  // 要发布市场

php artisan admin:ext-make-pro <vendor>/<name> --api --models=... --seed --blueprint --marketplace
```

说明：
- `--models`：表已存在于库中就**反推**生成 Model + Migration；表不存在就生成**占位**骨架（之后在阶段 1.2 补字段）。新系统通常表还不存在，可只给表名走占位模式，或先不传 `--models`、手写 migration。
- `--seed`：生成幂等 Seeder（带 mock 数据）。
- `--blueprint`：生成 `docs/` 蓝图文档；传了 `--models` 会自动开。
- `--table-prefix=`：**新建表**的表名前缀（默认从包名自动缩写，如 `miniapp_manager` → `miniapp_`）。反推的已存在表不加前缀。多扩展共存时靠前缀避免撞名、识别归属。
- 创建后执行 `php artisan admin:ext-update <vendor>.<name>` 让迁移生效。

> **表名约定**：本扩展所有**新建表**必须带统一前缀（见生成产物的 `docs/database/schema.md`「表名前缀约定」）。Model 类名用逻辑名（不带前缀），`$table` 属性用带前缀的物理表名。反推的已存在表保持原名。

### 1.2 完善数据层

打开 `docs/database/schema.md`，按 [database-schema.md](database-schema.md) 约定把每张表的字段补全：
- 每个字段加 `->comment('业务说明')`；状态/枚举字段加 `@scaffold:options={"0":"禁用","1":"启用"}`。
- 金额用 `decimal`（禁 float）；布尔用 `boolean`；图片/文件/邮箱/手机等按命名约定。
- 一个 migration 文件只对应一张表；**具名类、无 namespace、无时间戳前缀**。
- **每新增/修改一个迁移文件，必须登记进 `version.php`**，否则不执行。
- 建立关联（`user_id` 等外键）与索引。

### 产物
- `src/Models/*.php`、`updates/create_*_table.php`、`updates/seed_*_data.php`、`version.php`（已登记）。
- `docs/database/schema.md` 字段表齐全。

### 自检
```bash
php artisan admin:ext-update <vendor>.<name>   # 迁移执行成功，无报错
# 进数据库确认表已创建、字段/注释正确、seeder 幂等（跑两次第二次应跳过）
```
- [ ] PLAN.md「阶段 1」全部勾选后才进阶段 2。

---

## 阶段 2 · 后台 CRUD

> **铁律：本阶段所有 Controller / Grid / Form / Show 代码必须用命令行脚手架 `admin:scaffold --extension=` 生成，禁止手写从零起。** 这能保证命名空间、路由注册、菜单权限全部按扩展包约定正确落位，避免人工出错。

### 2.1 用脚手架生成后台 CRUD

对 `docs/database/schema.md` 里每张业务表，执行（可一次传多表）：

```bash
php artisan admin:scaffold \
  --table=<表名1>,<表名2> \
  --extension=<vendor>/<name> \
  --force
```

命令会自动完成（无需手工干预）：
- 在 `src/Models/` 生成 Model（含 fillable、时间戳/软删除、`$table`）。
- 在 `src/Http/Controllers/` 生成后台 Controller（Grid/Form/Show，继承 `AdminController`）。
- 生成 Lang 语言包（`labels`/`fields`/`options`/`permissions`）。
- 把 `Route::resource(...)` 追加到 `src/Http/routes.php`。
- 写入后台菜单和权限（按表名单数生成 URI）。

> 字段语义识别优先级：字段注释里的 `@scaffold:type` / `@scaffold:options` > 数据库类型 > 字段命名。所以**阶段 1 的字段注释写得好，本阶段生成的控件就准**。详见 [form-fields.md](form-fields.md)、[grid-system.md](grid-system.md)。

### 2.2 补充脚手架生成不了的人工项

脚手架只能按字段类型生成基础控件，以下需手工在生成的 Controller 里补充（见 [database-schema.md](database-schema.md) 第七节）：
- 关联下拉：`$form->select('user_id')->options(User::pluck('name','id'))`。
- 上传配置：`->disk('public')->dir('xxx')`。
- 富文本：`$form->editor('content')`；多图：`->multipleImage()`。
- Grid 调优：筛选器、快捷搜索、列显示器（`->image()`、`->using()->label()`、`->bool()`）。

### 产物
- `src/Models/*.php`、`src/Http/Controllers/*Controller.php`、`src/Http/routes.php` 已注册、菜单可点进。

### 自检
- 后台能访问每个模块的列表/新增/编辑/详情/删除，权限正常。
- [ ] PLAN.md「阶段 2」勾选。

---

## 阶段 3 · 业务逻辑

按需实现（不是每个扩展都要）：
- 表单 `saving`/`saved` 事件钩子（如密码加密、库存扣减）。
- Repository、模型作用域（`scopeXxx`）。
- 自定义 Action（批量操作、行操作，见 [action-system.md](action-system.md)）。
- 中间件（见 [extension-development.md](extension-development.md) 第六节）。

### 自检
- 跑一遍核心业务流程（下单/审核/导出等），结果符合预期。
- [ ] PLAN.md「阶段 3」勾选。

---

## 阶段 4 · 接口开发（带 --api 时）

> **铁律：member-api / admin-api 控制器与 Resource 类必须用 `admin:scaffold --extension= --api/--admin-api` 生成，禁止手写从零起。** 生成器会自动接好 `BaseApiController` 继承、`getValidationRules()` 骨架、Resource 字段注释、API 路由注册。

### 一、用脚手架生成接口层代码

```bash
# member-api（C 端）+ Resource
php artisan admin:scaffold \
  --table=<表名> \
  --extension=<vendor>/<name> \
  --api --force

# admin-api（管理端）+ Resource
php artisan admin:scaffold \
  --table=<表名> \
  --extension=<vendor>/<name> \
  --admin-api --force

# 一次生成后台 + 双端 API + Resource（最常用）
php artisan admin:scaffold \
  --table=<表名1>,<表名2> \
  --extension=<vendor>/<name> \
  --api --admin-api --resource --force
```

生成产物：
- `src/Http/Api/Controllers/*Controller.php`（member-api，继承 `App\Api\Controllers\BaseApiController`）。
- `src/Http/AdminApi/Controllers/*Controller.php`（admin-api，继承 `App\Admin\Api\Controllers\BaseApiController`）。
- `src/Http/Resources/*Resource.php`（每个 model 一个，`toArray()` 已按字段填好 `/** 注释 @var 类型 */`）。
- API 路由追加到 `src/Http/Api/routes.php`、`src/Http/AdminApi/routes.php`。

### 二、生成后必须人工补充

遵循 [api-development.md](api-development.md)：
1. **补全 `getValidationRules()`**——脚手架留的是空实现，必须按业务填 `store`/`update` 的字段校验规则。
2. Scramble 注释：方法 PHPDoc（接口名 + 描述）、validate 字段行内注释（`/** 字段 @default 示例 */`）。
3. 按业务裁剪默认生成的列表/批量/导入导出接口——不是每个表都需要全套 RESTful。
4. **同步维护 `docs/delivery/api_delivery.md`**——这是移动端开发的唯一数据源，写清 base URL（`/member-api`、`/admin-api`）、请求/响应契约、枚举字典。移动端 AI 只读这一个文件就能开发，不需要读控制器。

### 自检
- 访问 `/docs/admin-api`、`/docs/member-api`，接口文档与代码一致。
- 用 curl/Postman 跑通核心接口（登录→拿 token→调业务接口）。
- [ ] PLAN.md「阶段 4」勾选。

---

## 阶段 4.1 · 前端小程序的页面开发（带 --api 时）

**前置**：阶段 4 已完成，`docs/delivery/api_delivery.md` 是当前接口的唯一事实来源。

> **铁律：前端 AI 只读 `api_delivery.md` 这一个文件对接接口，不读后端控制器、不猜接口。** 接口契约以 `api_delivery.md` 为准；若发现文档与实际不符，回阶段 4 修文档、再开发前端，不要在前端硬编码绕过。

本阶段基于 `web_tpl/association`（unibest 骨架：uni-app + Vue3 + TypeScript + wot-ui + pnpm）。开发前先把对应业务扩展的 `association` 复制为独立前端工程，`cd` 进去用 `pnpm install` 装依赖。

### 一、契约到前端的三层映射（务必先理解，再动手）

后端 `api_delivery.md` → 前端骨架有三层映射，AI 必须分清：

| 层 | 后端来源 | 前端落点 | 谁生成 |
|----|----------|----------|--------|
| **请求底层** | 统一响应 `{code,status,msg,data}`、Bearer 鉴权 | `src/http/http.ts`（已就绪，**禁改**） | 骨架自带 |
| **请求地址（baseUrl）** | `api_delivery.md` 的 base URL（如 `/member-api`） | `.env` 的 `VITE_SERVER_BASEURL` | AI 配置 |
| **接口调用** | `api_delivery.md` 接口清单 | 后端有 openapi.json → `src/service/`（`pnpm openapi` 自动生成）；否则 → `src/api/<模块>.ts`（AI 手写） | 工具 / AI |

两条调用通道都复用同一个 `http`，拦截器（`src/http/interceptor.ts`）会自动拼 baseUrl、注入 `Authorization: Bearer <token>`、处理 401 无感刷新。**所以 AI 永远不要重写请求底层，只在上层选通道。**

### 二、骨架约定（违反即坏，逐条遵守）

1. **地址只改 `.env`**：把 `VITE_SERVER_BASEURL` 指向 `api_delivery.md` 的 member-api 完整地址（含统一前缀，如 `https://your-host/member-api`）。禁止在代码里硬编码任何 `http://`/`https://` 域名。微信小程序可分版本配置 `VITE_SERVER_BASEURL__WEIXIN_DEVELOP/TRIAL/RELEASE`。
2. **页面放 `src/pages/<模块>/`**：路由由 `pages.config.ts` 约定式自动扫描（`@uni-helper/vite-plugin-uni-pages`），**不要手写路由表**。tabBar 在 `src/tabbar/config.ts` 配。
3. **请求层二选一**：
   - 后端 Scramble 产出 `openapi.json`（访问 `/docs/openapi.json`）→ 配好 `openapi-ts-request.config.ts` 的 `schemaPath` 后跑 `pnpm openapi`，生成带类型的 `src/service/`，页面直接 import 用。
   - 无 openapi → 按 `api_delivery.md` 接口清单在 `src/api/<模块>.ts` 手写（`http.get/post/put/delete`），TS 类型放 `src/api/types/`。范例见 `src/api/login.ts`。
4. **UI 用 wot-ui**：组件前缀 `wd-xxx`，easycom 自动引入，**无需 import**。长列表分页用 `z-paging`（已在 easycom 注册）。
5. **鉴权**：登录成功调 `useTokenStore`（`src/store/token`）存 token；受保护页**不需要手动带 token**，拦截器自动注入。

### 三、标准执行序列（按顺序做，不跳步）

**关键原则：按 `api_delivery.md` 的「用户流程」驱动页面开发，不要按接口逐个堆。** 一个页面常调多个接口，按流程组织才不乱。

1. **通读契约**：完整读 `api_delivery.md`，吃透三段——①通用约定（base URL、鉴权方式、响应格式、分页结构 `{list, page_info}`）、②业务上下文（实体关系、用户流程、鉴权边界：哪些接口要登录、哪些公开）、③接口清单。列出「页面清单」：每个用户流程步骤对应一个页面，每个页面需要哪些接口。
2. **配置环境**：改 `.env` 的 `VITE_SERVER_BASEURL`。确认 H5 代理（`VITE_APP_PROXY_ENABLE`）按需开关。
3. **建立请求层**：按骨架约定二之 3 选通道，把 `api_delivery.md` 接口清单全部落成可调用的函数/类型。这是后续所有页面的地基。
4. **按用户流程逐页开发**：每个页面在 `src/pages/<模块>/<页>.vue` 创建，遵循「拉数据 → wot-ui 渲染 → 交互」三步。列表页注意分页（`page_info`）、下拉刷新、上拉加载。先做核心主流程，再做支线。
5. **接入鉴权**：登录页调登录接口存 token；公开页无需处理；受保护页靠拦截器自动鉴权，401 自动刷新或跳登录（骨架已实现，不要重复实现）。
6. **联调自检**：`pnpm dev:h5`（浏览器）或 `pnpm dev:mp`（微信开发者工具），**跑通一条完整主流程**（如 登录→首页列表→详情→下单→我的），而非逐接口单独测。

### 四、常见坑（务必避开）

- **接口前缀**：`api_delivery.md` 的接口路径若已含 `/member-api`，且 `VITE_SERVER_BASEURL` 也带 `/member-api`，会重复。约定：**baseUrl 带统一前缀，接口路径写前缀之后的相对路径**，与 `src/api/login.ts` 风格一致。
- **分页字段**：后端是 `{list, page_info:{pagesize,page,total}}`，不是 `total/limit`。分页组件按此对接。
- **枚举字典**：`api_delivery.md` 的枚举（如状态码）要在前端建常量映射，禁止页面里写魔法数字。
- **类型同步**：若走手写 api 通道，TS 类型必须与 `api_delivery.md` 响应字段一一对应，改动接口要同步改类型。

### 自检
- [ ] `.env` 的 `VITE_SERVER_BASEURL` 指向正确的 member-api 地址，无前缀重复。
- [ ] `api_delivery.md` 接口清单全部在前端可调用（`src/service/` 或 `src/api/`）。
- [ ] 至少一条核心用户流程在 `pnpm dev:h5` 或 `dev:mp` 下端到端跑通。
- [ ] 鉴权正常：登录后受保护接口能调通，token 失效能正确处理。
- [ ] PLAN.md「阶段 4.1」勾选。

---

## 阶段 4.2 · 落地页开发（带 --marketplace 时）

**前置**：`admin:ext-make-pro` 带 `--marketplace` 时已生成空壳 `resources/views/landing.blade.php`（统一 HTML 结构 + CDN 基线，body 为空）。

> **这不是填空题，是创作题。** 空壳只提供统一技术基线（独立 HTML、Tailwind v4 浏览器版 CDN、Lucide 图标、CSS 变量换肤），**不预设任何区块结构**。AI 必须根据本扩展的**具体业务功能**，自主设计落地页的结构与内容，不要套用其他扩展的固定模板。

### 一、目标效果（硬指标，逐条达到）

落地页是面向潜在客户的营销首屏，必须同时满足：

1. **美观**：有统一的品牌视觉（主色 + 强调色，写在 `:root` CSS 变量里，贴合业务气质——如渔业用自然绿、医疗用洁净蓝、餐饮用暖橙）。配色克制、留白充分、字体层级清晰（标题/正文/辅助文字大小拉开）。
2. **内容丰富**：覆盖完整营销链路，至少讲清——这是什么（定位）、解决什么问题（痛点）、怎么解决（方案/功能）、谁在用（场景/案例）、怎么开始（行为召唤/联系）。**具体讲哪些、讲几个，由业务决定**，不要凑数也不要漏。
3. **有层次感**：区块之间有明确的视觉节奏（明暗交替、留白与密集交替、图文左右交替），首屏（Hero）有冲击力，往下逐层深入，最后落到转化。不要平铺直叙一大块。
4. **有动态感**：至少包含滚动渐入（IntersectionObserver）、导航栏滚动变形、hover 微交互三类动效之一；数据指标用计数动画。**必须带 `prefers-reduced-motion` 无障碍降级**（关闭动画）。
5. **自带所需图片**：落地页所需图片（Hero 背景、方案配图、场景图等）**全部由 AI 生图填充**，不留空、不放占位灰块。每处图片先用 `<!-- AI_PROMPT: 详细生图描述 -->` 注释写清要什么图，再调用 AI 生图工具按提示生成，把图片放到 `public/images/{extensionName}/` 下并回填 `<img>` 路径。

### 二、按业务定制（核心，不要照搬）

落地页的**结构、文案、区块数量**必须从本扩展的实际功能出发，而不是套固定框架：

- **读 `docs/BLUEPRINT.md` 的功能模块清单 + `docs/database/schema.md` 的表结构**，提炼「这个系统到底做什么、卖什么、客户为什么买单」。
- 痛点/方案/功能/场景的数量与内容，**对应真实的功能模块**：后台有几个核心模块，落地页就讲几个核心方案；业务有哪些使用场景，落地页就展示哪些场景。不要为了凑够某个固定数量硬编。
- 文案要具体、要有行业语言，不要写「提升效率、降本增效」这类放之四海皆准的废话。把 `{pluginDesc}` 展开成有说服力的价值主张。
- 统计数据先用合理的示意值，标注 `TODO: 换成真实经营数据`，不要伪造过于具体的虚假案例。

### 三、技术约束（必须遵守）

1. **独立 HTML**：不继承后台布局（不用 `@extends('admin::app')`），面向访客，需配置独立路由访问（如在扩展 ServiceProvider 或路由文件注册）。
2. **CDN 自包含**：沿用空壳里的 Tailwind v4 浏览器版 + Lucide CDN，**不要引入 npm 构建依赖**，保证部署即用。
3. **响应式**：移动端/平板/桌面三档断点都要可用（参考 `@media (max-width: 768px)` 等）。
4. **图片路径**：统一放 `public/images/{extensionName}/`，`<img>` 用相对路径 `/images/{extensionName}/xxx.jpg`。
5. **Blade 安全**：CSS 的 `@media`/`@keyframes` 等 at-rule 在 Blade 下原样保留可用；但避免在模板里用 `{{ $var }}` 输出未定义变量（落地页是静态页，数据写死即可）。

### 四、参考标杆

`vendor/dcat-plus/laravel-admin/resources/views/pages/fishing_web.blade.php`（垂钓文旅落地页）是质量标杆：设计令牌 + 暗色模式、9 区块完整营销链路、三种动态效果、响应式、无障碍降级、真实场景图。**学习它的质量水准和动效手法，但结构按本扩展业务重新设计，不照抄它的区块。**

### 自检
- [ ] 落地页独立可访问（路由已注册），不依赖后台登录。
- [ ] 五项目标效果全部达到（美观/丰富/层次/动态/自带图片）。
- [ ] 所有图片已 AI 生图填充，无占位灰块，`AI_PROMPT` 注释已清理或保留为变更提示。
- [ ] 响应式三档断点可用，`prefers-reduced-motion` 降级生效。
- [ ] 内容对应本扩展真实功能模块，无凑数区块、无通用废话文案。
- [ ] PLAN.md「阶段 4.2」勾选。

---

## 阶段 5 · 发布上线

1. 完善 `README.md`（安装、配置、使用说明）。
2. 若 `--marketplace`：完善 `docs/MARKETPLACE.md`（卖点/场景/功能清单）。落地页已在阶段 4.2 完成，本步只做最终核对。
3. `composer.json` 元信息（name/description/authors）准确。
4. 交付检查（对照 `docs/tasks/PLAN.md` 末尾的「交付检查清单」）：
   - 所有迁移在 `version.php` 登记，`ext-update` 干净执行。
   - 后台 CRUD 全部可访问、权限正确。
   - 接口交付文档与实际一致。
   - seeder 幂等。
5. 打 tag 发布。

### 自检
- 在一个干净环境 `php artisan admin:ext-install <vendor>.<name>` 能从零跑通。
- [ ] PLAN.md 全部勾选 + 交付检查清单全绿。

---

## 全程铁律（每阶段都要遵守）

1. **迁移**：`updates/` 下具名类、无 namespace、无时间戳前缀，必须登记 `version.php`。Seeder 必须幂等。
2. **禁用** `php artisan migrate` / `migrate:fresh` / `db:seed` 改库结构；扩展迁移走 `admin:ext-update` / `admin:ext-install`。
3. **业务代码用脚手架生成**：后台 CRUD（阶段 2）、member-api/admin-api/Resource（阶段 4）一律用 `admin:scaffold --extension=<vendor>/<name>` 生成骨架，**禁止手写 Controller/Resource 从零起**。生成后只做"补语义、调 Grid/Form、填校验、裁剪接口"的人工打磨。详见 [scaffold-command.md](../../../../vendor/dcat-plus/laravel-admin/docs/scaffold-command.md)。
4. **每完成一阶段**：更新 `docs/tasks/PLAN.md` 勾选项，并简述「当前阶段 / 上一步完成 / 下一步」。这是"做到哪了"的唯一事实来源。
5. **遇阻**：优先查对应 references；字段语义/接口契约/迁移机制都有明文约定，不要自己发明。
