<?php

namespace Dcat\Admin\Extend;


use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Class LogViewer.
 *
 * @see https://github.com/laravel-admin-extensions/log-viewer/blob/master/src/LogViewer.php
 */
class LogViewer
{
    /**
     * The log file name.
     *
     * @var string
     */
    public $file;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $files;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $currentDirectory;

    /**
     * The path of log file.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Start and end offset in current page.
     *
     * @var array
     */
    protected $pageOffset = [];

    /**
     * @var array
     */
    public static $levelColors = [
        'EMERGENCY' => 'black',
        'ALERT' => 'navy',
        'CRITICAL' => 'maroon',
        'ERROR' => 'danger',
        'WARNING' => 'orange',
        'NOTICE' => 'light-blue',
        'INFO' => 'primary',
        'DEBUG' => 'light',
        '' => '',
    ];

    protected $keyword;

    protected $filename;

    /**
     * LogViewer constructor.
     *
     * @param null $file
     */
    public function __construct($basePath, $dir, $file = null)
    {
        $this->basePath = $this->getRealPath(rtrim($basePath, '/'));
        $this->currentDirectory = $this->formatPath(rtrim($dir, '/'));
        $this->file = $this->formatPath($file);
        $this->files = new Filesystem();
    }

    protected function getRealPath($path)
    {
        try {
            $paths = explode('/', $path);

            $result = '';
            foreach ($paths as $v) {
                $result .= $v.'/';

                $current = rtrim($result, '/');
                if (is_link($current)) {
                    $result = readlink($current).'/';
                }
            }

            return rtrim($result, '/');
        } catch (\Throwable $e) {
            return $path;
        }
    }

    protected function formatPath($path)
    {
        return $path ? str_replace(['../'], '', $path) : '';
    }

    /**
     * Get file path by giving log file name.
     *
     * @return string
     *
     */
    public function getFilePath()
    {
        if (!$this->filePath) {
            $path = $this->mergeDirectory().'/'.$this->getFile();

            $this->filePath = is_file($path) ? $path : false;
        }

        return $this->filePath;
    }

    public function setKeyword($value)
    {
        $this->keyword = strtolower($value);
    }

    public function setFilename($value)
    {
        $this->filename = $this->formatPath($value);
    }

    /**
     * Get size of log file.
     *
     * @return int
     */
    public function getFilesize()
    {
        if (!$this->getFilePath()) {
            return 0;
        }

        return filesize($this->getFilePath());
    }

    /**
     * Get log file list in storage.
     *
     * @return array
     */
    public function getLogFiles()
    {
        if ($this->filename) {
            return collect($this->files->allFiles($this->mergeDirectory()))->map(function (\SplFileInfo $fileInfo) {
                return $this->replaceBasePath($fileInfo->getRealPath());
            })->filter(function ($v) {
                return Str::contains($v, $this->filename);
            })->toArray();
        }

        $files = glob($this->mergeDirectory().'/*.*');
        //$files = array_combine($files, array_map('filemtime', $files));
        rsort($files);

        return array_map('basename', $files);
    }

    public function getLogDirectories()
    {
        return array_map([$this, 'replaceBasePath'], $this->files->directories($this->mergeDirectory()));
    }

    protected function replaceBasePath($v)
    {
        $basePath = str_replace('\\', '/', $this->getLogBasePath());

        return str_replace($basePath.'/', '', str_replace('\\', '/', $v));
    }

    public function mergeDirectory()
    {
        if (!$this->currentDirectory) {
            return $this->getLogBasePath();
        }

        return $this->getLogBasePath().'/'.$this->currentDirectory;
    }

    /**
     * @return string
     */
    public function getLogBasePath()
    {
        return $this->basePath;
    }

    /**
     * Get the last modified log file.
     *
     * @return string
     */
    public function getLastModifiedLog()
    {
        return current($this->getLogFiles());
    }

    public function getFile()
    {
        if (! $this->file) {
            $this->file = $this->getLastModifiedLog();
        }

        return $this->file;
    }

    public function isCurrentFile($file)
    {
        return $this->replaceBasePath($this->getFilePath()) === trim($this->currentDirectory.'/'.$file, '/');
    }

    /**
     * Get previous page url.
     *
     * @return bool|string
     */
    public function getPrevPageUrl()
    {
        if (
            !$this->getFilePath()
            || $this->pageOffset['end'] >= $this->getFilesize() - 1
            || $this->keyword
        ) {
            return false;
        }


        return admin_route('ycookies.log-viewer.log-viewer-file', [
            'file' => $this->getFile(),
            'offset' => $this->pageOffset['end'],
            'keyword' => $this->keyword,
            'dir' => $this->currentDirectory,
            'filename' => $this->filename,
        ]);
    }

    /**
     * Get Next page url.
     *
     * @return bool|string
     */
    public function getNextPageUrl()
    {
        if (
            !$this->getFilePath()
            || $this->pageOffset['start'] == 0
            || $this->keyword
        ) {
            return false;
        }

        return admin_route('ycookies.log-viewer.log-viewer-file', [
            'file' => $this->getFile(),
            'offset' => -$this->pageOffset['start'],
            'keyword' => $this->keyword,
            'dir' => $this->currentDirectory,
            'filename' => $this->filename,
        ]);
    }

    /**
     * Fetch logs by giving offset.
     *
     * @param int $seek
     * @param int $lines
     * @param int $buffer
     *
     * @return array
     *
     * @see http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
     */
    public function fetch($seek = 0, $lines = 20, $buffer = 4096)
    {
        $logs = $this->read($seek, $lines, $buffer);

        if (!$this->keyword || !$logs) {
            return $logs;
        }

        $result = [];

        foreach ($logs as $log) {
            if (Str::contains(strtolower(implode(' ', $log)), $this->keyword)) {
                $result[] = $log;
            }
        }

        if (count($result) >= $lines || !$this->getNextOffset()) {
            return $result;
        }

        return array_merge($result, $this->fetch($this->getNextOffset(), $lines - count($result), $buffer));
    }

    public function getNextOffset()
    {
        if ($this->pageOffset['start'] == 0) {
            return false;
        }

        return -$this->pageOffset['start'];
    }

    protected function read($seek = 0, $lines = 20, $buffer = 4096)
    {
        if (! $this->getFilePath()) {
            return [];
        }

        $f = fopen($this->getFilePath(), 'rb');

        $type = (preg_match('/\[(\d{4}(?:-\d{2}){2} \d{2}(?::\d{2}){2})\] (\w+)*/', fread($f, 34)) == 0) ? 'txt' : '';

        if ($seek) {
            fseek($f, abs($seek));
        } else {
            fseek($f, 0, SEEK_END);
        }

        if (fread($f, 1) != "\n") {
            $lines -= 1;
        }
        fseek($f, -1, SEEK_CUR);

        // 从前往后读,上一页
        // Start reading
        if ($seek > 0) {
            $output = $this->readPrevPage($f, $lines, $buffer, $type);
            // 从后往前读,下一页
        } else {
            $output = $this->readNextPage($f, $lines, $buffer, $type);
        }

        fclose($f);

        return $this->parseLog($output, $type);
    }

    protected function readPrevPage($f, &$lines, $buffer, $type = '')
    {
        $rule = ($type == 'txt') ? "\n" : "\n[20";
        $output = '';

        $this->pageOffset['start'] = ftell($f);

        while (!feof($f) && $lines >= 0) {
            $output = $output . ($chunk = fread($f, $buffer));
            $lines -= substr_count($chunk, $rule);
        }

        $this->pageOffset['end'] = ftell($f);

        while ($lines++ < 0) {
            $strpos = strrpos($output, $rule) + 1;
            $_ = mb_strlen($output, '8bit') - $strpos;
            $output = substr($output, 0, $strpos);
            $this->pageOffset['end'] -= $_;
        }

        return $output;
    }

    // @lila
    protected function readNextPage($f, &$lines, $buffer, $type = '')
    {
        $rule = ($type == 'txt') ? "\n" : "\n[20";
        $output = '';

        $this->pageOffset['end'] = ftell($f);

        while (ftell($f) > 0 && $lines >= 0) {
            $offset = min(ftell($f), $buffer);
            fseek($f, -$offset, SEEK_CUR);
            $output = ($chunk = fread($f, $offset)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, $rule);
        }

        $this->pageOffset['start'] = ftell($f);

        while ($lines++ < 0) {
            $strpos = strpos($output, $rule) + 1;
            $output = substr($output, $strpos);
            $this->pageOffset['start'] += $strpos;
        }

        return $output;
    }

    /**
     * Get tail logs in log file.
     *
     * @param int $seek
     *
     * @return array
     */
    public function tail($seek)
    {
        // Open the file
        $f = fopen($this->getFilePath(), 'rb');

        if (!$seek) {
            // Jump to last character
            fseek($f, -1, SEEK_END);
        } else {
            fseek($f, abs($seek));
        }

        $output = '';

        while (!feof($f)) {
            $output .= fread($f, 4096);
        }

        $pos = ftell($f);

        fclose($f);

        $logs = [];

        foreach ($this->parseLog(trim($output)) as $log) {
            $logs[] = $this->renderTableRow($log);
        }

        return [$pos, $logs];
    }

    /**
     * Render table row.
     *
     * @param $log
     *
     * @return string
     */
    protected function renderTableRow($log)
    {
        $color = self::$levelColors[$log['level']] ?? 'black';

        $index = uniqid();

        $button = '';

        if (!empty($log['trace'])) {
            $button = "<a class=\"btn btn-primary btn-xs\" data-toggle=\"collapse\" data-target=\".trace-{$index}\"><i class=\"fa fa-info\"></i>&nbsp;&nbsp;Exception</a>";
        }

        $trace = '';

        if (!empty($log['trace'])) {
            $trace = "<tr class=\"collapse trace-{$index}\">
    <td colspan=\"5\"><div style=\"white-space: pre-wrap;background: #333;color: #fff; padding: 10px;\">{$log['trace']}</div></td>
</tr>";
        }

        return <<<TPL
<tr style="background-color: rgb(255, 255, 213);">
    <td><span class="label bg-{$color}">{$log['level']}</span></td>
    <td><strong>{$log['env']}</strong></td>
    <td  style="width:150px;">{$log['time']}</td>
    <td><code>{$log['info']}</code></td>
    <td>$button</td>
</tr>
$trace
TPL;
    }

    /**
     * Parse raw log text to array.
     *
     * @param $raw
     *
     * @return array
     */
    protected function parseLog($raw, $type = '')
    {
        if ($type == 'txt') {
            $logs = preg_split('/(\r\n|\n)/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $logs = preg_split('/\[(\d{4}(?:-\d{2}){2} \d{2}(?::\d{2}){2})\] (\w+)\.(\w+):((?:(?!{"exception").)*)?/', trim($raw), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

            foreach ($logs as $index => $log) {
                if (preg_match('/^\d{4}/', $log)) {
                    break;
                } else {
                    unset($logs[$index]);
                }
            }
        }

        if (empty($logs)) {
            return [];
        }

        $parsed = [];
        $logs = array_values($logs);

        if ($type == 'txt') {
            foreach ($logs as $log) {
                $parsed[] = [
                    'time' => '',
                    'env' => '',
                    'level' => '',
                    'info' => $log,
                    'trace' => '',
                ];
            }
            $parsed = array_reverse($parsed);
        } else {
            foreach (array_chunk($logs, 5) as $log) {
                $parsed[] = [
                    'time' => $log[0] ?? '',
                    'env' => $log[1] ?? '',
                    'level' => $log[2] ?? '',
                    'info' => $log[3] ?? '',
                    'trace' => $this->replaceRootPath(trim($log[4] ?? '')),
                ];
            }
            rsort($parsed);
        }

        unset($logs);

        return $parsed;
    }

    protected function replaceRootPath($content)
    {
        $basePath = str_replace('\\', '/', base_path() . '/');

        return str_replace($basePath, '', str_replace(['\\\\', '\\'], '/', $content));
    }
}