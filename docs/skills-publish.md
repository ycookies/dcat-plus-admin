# AI 编码技能发布（admin:publish:skills）

> 适用版本：dcat-plus-admin 内置，无需额外安装。

dcat-plus-admin 内置了一份面向该框架的 **AI 编码技能**（skills），涵盖了安装、CRUD、表单扩展、Grid、API、扩展开发、配置等全流程知识。它能让 Claude Code、Cursor、GitHub Copilot、Windsurf、OpenAI Codex 等 AI 编码助手在写 `Dcat\Admin` 代码时"懂这个框架"，少写错、少翻文档。

通过一条命令即可把这份技能发布到当前项目的各平台规则目录中：

```bash
php artisan admin:publish:skills
```

---

## 一、它能解决什么问题

框架自带的技能源文件位于包内：

```
vendor/dcat-plus/laravel-admin/.claude/skills/
├── SKILL.md                    # 主技能文件（安装、CRUD、Grid、Form 等）
└── references/                 # 详细参考
    ├── form-fields.md          # 表单字段
    ├── grid-system.md          # Grid 系统
    ├── infrastructure.md       # 基础设施（菜单、权限、配置等）
    └── extension-development.md # 扩展开发
```

但各家 AI 编码平台读取规则的 **路径与格式各不相同**：

| 平台 | 读取路径 | 格式要求 |
|------|------|------|
| Claude Code | `.claude/skills/<name>/SKILL.md` | 目录结构 + YAML frontmatter |
| Cursor | `.cursor/rules/*.mdc` | `.mdc` + Cursor 专用 frontmatter |
| GitHub Copilot | `.github/copilot-instructions.md` | 纯 markdown |
| Windsurf | `.windsurf/rules/*.md` | markdown + frontmatter |
| OpenAI Codex | `AGENTS.md`（项目根） | 纯 markdown |

手动把这些文件搬到各平台既繁琐又容易出错。`admin:publish:skills` 会自动完成：

1. 读取包内技能源文件（SKILL.md + references）
2. **正文原样保留**，去掉源文件的 YAML frontmatter
3. 把 `references/*.md` 合并内联到主文档末尾
4. 按各平台约定 **落到正确路径**，并补上该平台需要的 frontmatter

源文件只维护一份，多平台自动适配。

---

## 二、命令用法

### 基本语法

```bash
php artisan admin:publish:skills [选项]
```

### 选项说明

| 选项 | 说明 |
|------|------|
| `--platform=名称` | 指定目标平台，多个用逗号分隔。可选值见下表 |
| `--all` | 发布到所有支持的平台 |
| `--force` | 覆盖项目里已存在的同名文件 |
| `--list` | 仅列出可发布平台与目标路径，不写任何文件 |

### 平台名称

| 名称 | 平台 |
|------|------|
| `claude` | Claude Code |
| `cursor` | Cursor |
| `copilot` | GitHub Copilot |
| `windsurf` | Windsurf |
| `codex` | OpenAI Codex |

### 常用示例

```bash
# 1. 查看支持哪些平台、会发布到哪里（推荐先跑一次）
php artisan admin:publish:skills --list

# 2. 发布到你正在用的那一个平台
php artisan admin:publish:skills --platform=cursor

# 3. 同时发布到多个平台（团队中多人用不同工具）
php artisan admin:publish:skills --platform=claude,cursor,copilot

# 4. 一次性发布到所有支持的平台
php artisan admin:publish:skills --all

# 5. 技能源更新后，强制覆盖旧文件
php artisan admin:publish:skills --all --force
```

---

## 三、各平台发布结果

### Claude Code
- 目标：`.claude/skills/dcat-plus-admin/`
- 方式：**原样复制整个目录**（含 `SKILL.md` 与 `references/`），并自动剔除 `.DS_Store` 等噪声文件
- 生效：在项目内打开 Claude Code 即自动加载

### Cursor
- 目标：`.cursor/rules/dcat-plus-admin.mdc`
- 方式：合并正文后，补上 Cursor 的 frontmatter（`description` / `globs` / `alwaysApply`），默认按 `**/*.php` 触发

### GitHub Copilot
- 目标：`.github/copilot-instructions.md`
- 方式：合并正文，文件头加一行生成注释
- 生效：在支持的 IDE（VS Code、JetBrains 等）中由 Copilot 自动读取

### Windsurf
- 目标：`.windsurf/rules/dcat-plus-admin.md`
- 方式：合并正文后，补上 Windsurf 的 frontmatter（`trigger` / `globs` / `description`）

### OpenAI Codex
- 目标：项目根目录 `AGENTS.md`
- 方式：纯 markdown，直接输出合并正文
- 生效：Codex CLI / ChatGPT 编码智能体读取项目级 `AGENTS.md`

> 注意：若项目里已存在 `AGENTS.md` 或 `.github/copilot-instructions.md`，命令默认 **不会覆盖**，需加 `--force`。

---

## 四、典型工作流

### 1. 新项目初始化

```bash
php artisan admin:install              # 安装框架
php artisan admin:publish:skills --all # 发布 AI 编码技能到所有平台
```

之后无论是用 Claude Code 写控制器、用 Cursor 调 Grid、还是让 Copilot 生成表单字段，AI 都能遵循 dcat-plus-admin 的正确写法。

### 2. 框架升级后刷新技能

```bash
composer update dcat-plus/laravel-admin
php artisan admin:publish:skills --all --force
```

### 3. 多人协作团队

团队成员使用不同 AI 工具时，可让每人各自运行命令发布到自己的平台；或由项目负责人执行 `--all` 后把 `.claude/`、`.cursor/`、`.github/` 等目录纳入版本库，团队拉取代码后即可共享同一份技能。

> 是否把这些目录提交到 Git 取决于团队约定。一般建议提交，保证全员 AI 输出风格一致。

---

## 五、关于技能源文件

- 技能源位于包内 `vendor/dcat-plus/laravel-admin/.claude/skills/`，随 Composer 分发。
- 升级包后技能源会自动更新，再次执行发布命令即可刷新到项目。
- 若想自定义技能内容，可在发布后直接编辑项目内的目标文件（如 `.cursor/rules/dcat-plus-admin.mdc`），但注意下次 `--force` 重新发布会覆盖你的改动。

---

## 六、常见问题

**Q：发布后 AI 没有按技能内容写代码？**
确认文件落在了正确路径，且该平台的规则功能已启用。例如 Cursor 需在设置中开启 Rules；Copilot 的 instructions 需 IDE 插件版本支持。

**Q：`AGENTS.md` / `copilot-instructions.md` 报"已存在，跳过"？**
这些是项目根级单文件，可能与项目已有的同名文件冲突。确认后加 `--force` 覆盖，或手动把技能内容合并进去。

**Q：支持其他平台（如 Zcode）吗？**
当前内置 5 个平台。后续会扩展，可在框架的 `Console\PublishSkillsCommand` 的 `platforms()` 方法中按相同格式新增平台配置。

**Q：能发布到全局（用户目录）而非项目级吗？**
不能。技能只发布到 **当前项目根目录** 下的各平台约定路径，确保技能与项目所用的框架版本严格对应。
