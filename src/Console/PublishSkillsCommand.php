<?php

namespace Dcat\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * 把 dcat-plus-admin 的 AI 编码技能 (skills) 发布到项目根目录，
 * 适配各主流 AI 编码平台的规则目录约定。
 *
 * 源：包内 `.claude/skills/` (SKILL.md + references/*.md)
 * 目标：按各平台约定，把正文原样合并后落到正确路径，并补上必要的 frontmatter。
 */
class PublishSkillsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:publish:skills
        {--platform= : 目标平台，逗号分隔 (claude,cursor,copilot,windsurf,codex)}
        {--all : 发布到所有支持的平台}
        {--force : 覆盖已存在的文件}
        {--list : 仅列出可发布平台，不写文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '把 dcat-plus-admin 的 AI 编码技能发布到 Claude / Cursor / GitHub Copilot / Windsurf / Codex 等平台的规则目录。';

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 技能源目录（包内）。
     *
     * @var string
     */
    protected $skillPath;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->skillPath = dirname(__DIR__, 2).'/.claude/skills';
    }

    public function handle()
    {
        if ($this->option('list')) {
            return $this->listPlatforms();
        }

        $platforms = $this->resolvePlatforms();
        if (empty($platforms)) {
            $this->error('未指定目标平台。使用 --platform=claude,cursor 或 --all，或 --list 查看可用平台。');

            return 1;
        }

        $content = $this->loadSkillContent();
        if ($content === null) {
            $this->error("找不到技能源文件：{$this->skillPath}/SKILL.md");

            return 1;
        }

        $this->info('开始发布 dcat-plus-admin 编码技能...');
        $published = 0;
        foreach ($platforms as $platform) {
            if ($this->publishFor($platform, $content)) {
                $published++;
            }
        }

        $this->newLine();
        $this->info("完成：已发布到 {$published} 个平台。");

        return 0;
    }

    /**
     * 列出所有支持的平台。
     */
    protected function listPlatforms()
    {
        $this->info('可发布平台：');
        $this->newLine();

        $rows = [];
        foreach ($this->platforms() as $key => $meta) {
            $rows[] = [$key, $meta['name'], $meta['target']];
        }

        $this->table(['平台', '说明', '目标路径'], $rows);

        return 0;
    }

    /**
     * 解析要发布到的平台列表。
     *
     * @return array
     */
    protected function resolvePlatforms()
    {
        if ($this->option('all')) {
            return array_keys($this->platforms());
        }

        $platform = (string) $this->option('platform');
        if ($platform === '') {
            return [];
        }

        $requested = array_filter(array_map('trim', explode(',', $platform)));
        $valid = array_keys($this->platforms());

        $resolved = [];
        foreach ($requested as $name) {
            $name = strtolower($name);
            if (! isset($this->platforms()[$name])) {
                $this->warn("忽略未知平台：{$name}（可用：".implode(', ', $valid).'）');
                continue;
            }
            $resolved[] = $name;
        }

        return array_values(array_unique($resolved));
    }

    /**
     * 发布到单个平台。
     *
     * @param  string  $platform
     * @param  string  $content 已合并的完整技能正文
     * @return bool
     */
    protected function publishFor($platform, $content)
    {
        $meta = $this->platforms()[$platform];
        $target = base_path($meta['target']);
        $body = isset($meta['builder']) ? call_user_func($meta['builder'], $content) : $content;

        if (! $this->option('force') && $this->files->exists($target)) {
            $this->warn("[{$meta['name']}] 已存在，跳过：{$meta['target']}（使用 --force 覆盖）");

            return false;
        }

        $this->makeDirectory(dirname($target));

        // Claude 平台保持目录结构 (SKILL.md + references/)。
        if ($platform === 'claude') {
            $this->publishClaudeDirectory($target);

            $this->line("<info>[{$meta['name']}]</info> 已发布目录 <comment>{$meta['target']}</comment>");

            return true;
        }

        $this->files->put($target, $body);
        $this->line("<info>[{$meta['name']}]</info> 已发布文件 <comment>{$meta['target']}</comment>");

        return true;
    }

    /**
     * 原样复制技能目录到 Claude 的 .claude/skills/<name>/。
     *
     * @param  string  $target
     * @return void
     */
    protected function publishClaudeDirectory($target)
    {
        if ($this->files->isDirectory($target)) {
            if ($this->option('force')) {
                $this->files->deleteDirectory($target);
            } else {
                // 已在 publishFor 判过存在性，这里安全复制。
            }
        }

        $this->files->copyDirectory($this->skillPath, $target);

        // 清理 macOS 噪声文件。
        foreach (['.DS_Store'] as $junk) {
            if ($this->files->exists($target.'/'.$junk)) {
                $this->files->delete($target.'/'.$junk);
            }
        }
    }

    /**
     * 读取并装配完整技能正文：SKILL.md（去掉 frontmatter）+ references/*.md。
     *
     * @return string|null
     */
    protected function loadSkillContent()
    {
        $skillFile = $this->skillPath.'/SKILL.md';
        if (! $this->files->exists($skillFile)) {
            return null;
        }

        $body = $this->stripFrontmatter($this->files->get($skillFile));

        $refsDir = $this->skillPath.'/references';
        if ($this->files->isDirectory($refsDir)) {
            foreach ($this->files->files($refsDir) as $file) {
                if ($file->getExtension() !== 'md') {
                    continue;
                }
                $name = $file->getFilename();
                $refBody = $this->stripFrontmatter($this->files->get($file->getRealPath()));
                $refBody = trim($refBody);

                $body .= "\n\n---\n\n# 参考：{$name}\n\n".$refBody."\n";
            }
        }

        return trim($body)."\n";
    }

    /**
     * 去掉 YAML frontmatter（--- ... --- 块）。
     *
     * @param  string  $text
     * @return string
     */
    protected function stripFrontmatter($text)
    {
        if (preg_match('/^\s*---\r?\n.*?\r?\n---\r?\n/s', $text, $m, PREG_OFFSET_CAPTURE)) {
            return substr($text, $m[0][1] + strlen($m[0][0]));
        }

        return $text;
    }

    /**
     * 平台定义表。
     *
     * 每个平台包含：name（展示名）、target（项目根下相对路径）、可选 builder（正文转换器）。
     *
     * @return array
     */
    protected function platforms()
    {
        $triggers = 'dcat-plus, dcat-admin, laravel admin, 后台管理, admin panel, Grid, Form, 扩展开发';

        return [
            'claude' => [
                'name' => 'Claude Code',
                'target' => '.claude/skills/dcat-plus-admin',
                // 原样复制目录，无需 builder。
            ],
            'cursor' => [
                'name' => 'Cursor',
                'target' => '.cursor/rules/dcat-plus-admin.mdc',
                'builder' => function ($content) use ($triggers) {
                    $front = "---\n"
                        ."description: dcat-plus-admin Laravel 后台框架开发技能\n"
                        ."globs: \"**/*.php\"\n"
                        ."alwaysApply: false\n"
                        ."---\n\n";

                    return $front.$content;
                },
            ],
            'copilot' => [
                'name' => 'GitHub Copilot',
                'target' => '.github/copilot-instructions.md',
                'builder' => function ($content) {
                    $header = "<!-- dcat-plus-admin 编码技能，由 `php artisan admin:publish:skills` 生成。 -->\n\n";

                    return $header.$content;
                },
            ],
            'windsurf' => [
                'name' => 'Windsurf',
                'target' => '.windsurf/rules/dcat-plus-admin.md',
                'builder' => function ($content) {
                    $front = "---\n"
                        ."trigger: glob\n"
                        ."globs: \"**/*.php\"\n"
                        ."description: dcat-plus-admin Laravel 后台框架开发技能\n"
                        ."---\n\n";

                    return $front.$content;
                },
            ],
            'codex' => [
                'name' => 'OpenAI Codex',
                'target' => 'AGENTS.md',
                // AGENTS.md 为纯 markdown，直接使用合并正文。
            ],
        ];
    }

    /**
     * @param  string  $directory
     * @return void
     */
    protected function makeDirectory($directory)
    {
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }
}
