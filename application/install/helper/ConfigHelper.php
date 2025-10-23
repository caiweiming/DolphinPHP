<?php

namespace app\install\helper;

use think\facade\Env;

/**
 * 配置文件助手类
 * Class ConfigHelper
 * @package app\install\helper
 */
class ConfigHelper
{
    /**
     * 是否备份原始文件
     * @var bool
     */
    const backup_file = false;

    /**
     * 在配置文件中的指定位置插入新配置项
     *
     * @param string $configFile 配置文件路径(相对于项目根目录或绝对路径)
     * @param string $afterKey 在哪个配置项之后插入
     * @param string $newConfigKey 新配置项的键名
     * @param mixed $newConfigValue 新配置项的值
     * @param int $linesAfter 在目标配置项后多少行插入(默认2行)
     * @param int $indent 缩进空格数(默认4)
     * @param string|array $comment 配置项的注释(可选)
     * @return bool|string 成功返回true,失败返回错误信息
     */
    public static function insertConfig($configFile, $afterKey, $newConfigKey, $newConfigValue, $linesAfter = 2, $indent = 4, $comment = '')
    {
        // 处理文件路径
        if (!file_exists($configFile)) {
            // 如果不是绝对路径,尝试相对于根目录
            $configFile = Env::get('root_path') . $configFile;
            if (!file_exists($configFile)) {
                return "配置文件不存在: {$configFile}";
            }
        }

        // 读取配置文件
        $content = file_get_contents($configFile);
        if ($content === false) {
            return "无法读取配置文件";
        }

        // 检查配置项是否已存在
        $checkPattern = "/^\s*['\"]" . preg_quote($newConfigKey, '/') . "['\"]\s*=>/m";
        if (preg_match($checkPattern, $content)) {
            return "配置项 '{$newConfigKey}' 已经存在";
        }

        // 构建查找目标配置项的正则表达式
        // 匹配格式: 'key' => value,
        $pattern = "/(^\s*['\"]" . preg_quote($afterKey, '/') . "['\"]\s*=>.*?,)(\r?\n)/m";

        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return "找不到配置项: {$afterKey}";
        }

        // 获取匹配位置
        $matchedText = $matches[0][0];
        $matchPosition = $matches[0][1];
        $lineBreak = $matches[2][0];

        // 计算插入位置(在找到的配置项后 $linesAfter 行)
        $insertPosition = $matchPosition + strlen($matchedText);

        // 如果需要在多行之后插入,继续查找换行符
        for ($i = 1; $i < $linesAfter; $i++) {
            $nextLineBreak = strpos($content, "\n", $insertPosition);
            if ($nextLineBreak !== false) {
                $insertPosition = $nextLineBreak + 1;
            } else {
                break;
            }
        }

        // 格式化新配置项的值
        $formattedValue = self::formatValue($newConfigValue);

        // 生成新配置行(保持与原文件相同的缩进)
        $indentStr = str_repeat(' ', $indent);

        // 处理注释
        $commentLines = '';
        if (!empty($comment)) {
            $commentLines = self::formatComment($comment, $indentStr, $lineBreak);
        }

        $newConfigLine = $commentLines . $indentStr . "'{$newConfigKey}' => {$formattedValue}," . $lineBreak;

        // 插入新配置
        $newContent = substr_replace($content, $newConfigLine, $insertPosition, 0);

        // 备份原文件
        if (self::backup_file) {
            $backupFile = $configFile . '.backup.' . date('YmdHis');
            if (!copy($configFile, $backupFile)) {
                return "无法创建备份文件";
            }
        }

        // 写入新内容
        if (file_put_contents($configFile, $newContent) === false) {
            return "无法写入配置文件";
        }

        return true;
    }

    /**
     * 格式化注释
     *
     * @param string|array $comment 注释内容(字符串或数组)
     * @param string $indentStr 缩进字符串
     * @param string $lineBreak 换行符
     * @return string 格式化后的注释
     */
    private static function formatComment($comment, $indentStr, $lineBreak)
    {
        $commentLines = '';

        if (is_array($comment)) {
            // 数组形式的注释(多行)
            foreach ($comment as $line) {
                $commentLines .= $indentStr . '// ' . $line . $lineBreak;
            }
        } else {
            // 字符串形式的注释(单行)
            $commentLines = $indentStr . '// ' . $comment . $lineBreak;
        }

        return $commentLines;
    }

    /**
     * 格式化配置值
     *
     * @param mixed $value 配置值
     * @return string 格式化后的字符串
     */
    private static function formatValue($value)
    {
        if (is_string($value)) {
            // 字符串值用单引号包裹
            return "'" . addslashes($value) . "'";
        } elseif (is_bool($value)) {
            // 布尔值
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            // null值
            return 'null';
        } elseif (is_numeric($value)) {
            // 数字
            return $value;
        } elseif (is_array($value)) {
            // 数组(简单格式化)
            return var_export($value, true);
        } else {
            // 其他类型转为字符串
            return "'" . addslashes((string)$value) . "'";
        }
    }

    /**
     * 简化版:直接在指定配置项后插入(默认2行后)
     *
     * @param string $afterKey 在哪个配置项之后插入
     * @param string $newConfigKey 新配置项的键名
     * @param mixed $newConfigValue 新配置项的值
     * @param string|array $comment 配置项的注释(可选)
     * @param string $configFile 配置文件路径(默认为 config/app.php)
     * @return bool|string
     */
    public static function addToAppConfig($afterKey, $newConfigKey, $newConfigValue, $comment = '', $configFile = 'config/app.php')
    {
        return self::insertConfig($configFile, $afterKey, $newConfigKey, $newConfigValue, 1, 4, $comment);
    }

    /**
     * 更新已存在的配置项的值
     *
     * @param string $configFile 配置文件路径(相对于项目根目录或绝对路径)
     * @param string $configKey 要更新的配置项键名
     * @param mixed $newValue 新的配置值
     * @param bool $updateComment 是否同时更新注释(默认false)
     * @param string|array $comment 新的注释内容(仅当$updateComment=true时有效)
     * @return bool|string 成功返回true,失败返回错误信息
     */
    public static function updateConfig($configFile, $configKey, $newValue, $updateComment = false, $comment = '')
    {
        // 处理文件路径
        if (!file_exists($configFile)) {
            // 如果不是绝对路径,尝试相对于根目录
            $configFile = Env::get('root_path') . $configFile;
            if (!file_exists($configFile)) {
                return "配置文件不存在: {$configFile}";
            }
        }

        // 读取配置文件
        $content = file_get_contents($configFile);
        if ($content === false) {
            return "无法读取配置文件";
        }

        // 检查配置项是否存在
        $pattern = "/(^\s*)(['\"]" . preg_quote($configKey, '/') . "['\"]\s*=>\s*)(.+?)(,)(\r?\n)/m";

        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return "配置项 '{$configKey}' 不存在";
        }

        // 格式化新值
        $formattedValue = self::formatValue($newValue);

        // 获取匹配信息
        $fullMatch = $matches[0][0];           // 完整匹配的内容
        $matchPosition = $matches[0][1];       // 匹配位置
        $indent = $matches[1][0];              // 缩进
        $keyPart = $matches[2][0];             // 'key' =>
        $comma = $matches[4][0];               // 逗号
        $lineBreak = $matches[5][0];           // 换行符

        // 构建新的配置行
        $newConfigLine = $indent . $keyPart . $formattedValue . $comma . $lineBreak;

        // 如果需要更新注释
        if ($updateComment && !empty($comment)) {
            // 查找该配置项上方的注释行
            // 注释行格式: 以 // 开头,在配置项的上方
            $beforeContent = substr($content, 0, $matchPosition);
            $lines = explode("\n", $beforeContent);

            // 从后向前查找连续的注释行
            $commentLineCount = 0;
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $trimmed = trim($lines[$i]);
                if (empty($trimmed)) {
                    // 空行,继续向上查找
                    continue;
                } elseif (strpos($trimmed, '//') === 0) {
                    // 注释行,计数
                    $commentLineCount++;
                } else {
                    // 非注释行,停止查找
                    break;
                }
            }

            // 计算需要删除的旧注释的起始位置
            $deleteStart = $matchPosition;
            if ($commentLineCount > 0) {
                // 找到第一个注释行的位置
                $linesBeforeConfig = array_slice($lines, 0, count($lines) - $commentLineCount);
                $deleteStart = strlen(implode("\n", $linesBeforeConfig));
                if (count($linesBeforeConfig) > 0) {
                    $deleteStart += 1; // 加上换行符
                }
            }

            // 生成新注释
            $commentLines = self::formatComment($comment, $indent, $lineBreak);

            // 替换(删除旧注释+旧配置,插入新注释+新配置)
            $deleteLength = $matchPosition - $deleteStart + strlen($fullMatch);
            $newContent = substr_replace($content, $commentLines . $newConfigLine, $deleteStart, $deleteLength);
        } else {
            // 只替换配置值,不修改注释
            $newContent = substr_replace($content, $newConfigLine, $matchPosition, strlen($fullMatch));
        }

        // 备份原文件
        if (self::backup_file) {
            $backupFile = $configFile . '.backup.' . date('YmdHis');
            if (!copy($configFile, $backupFile)) {
                return "无法创建备份文件";
            }
        }

        // 写入新内容
        if (file_put_contents($configFile, $newContent) === false) {
            return "无法写入配置文件";
        }

        return true;
    }

    /**
     * 更新 app.php 配置项(简化版)
     *
     * @param string $configKey 要更新的配置项键名
     * @param mixed $newValue 新的配置值
     * @param string|array $comment 新的注释(可选)
     * @param string $configFile 配置文件路径(默认为 config/app.php)
     * @return bool|string
     */
    public static function updateAppConfig($configKey, $newValue, $comment = '', $configFile = 'config/app.php')
    {
        $updateComment = !empty($comment);
        return self::updateConfig($configFile, $configKey, $newValue, $updateComment, $comment);
    }

    /**
     * 删除备份文件
     *
     * @param string $configFile 配置文件路径
     * @return bool
     */
    public static function removeBackups($configFile)
    {
        $pattern = $configFile . '.backup.*';
        $backups = glob($pattern);

        foreach ($backups as $backup) {
            @unlink($backup);
        }

        return true;
    }
}
