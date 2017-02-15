<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/caiweiming/parse-sql-file
// +----------------------------------------------------------------------

namespace util;

/**
 * 读取Sql文件并返回可执行的sql语句
 * @author CaiWeiMing <314013107@qq.com>
 */
class Sql
{
    /**
     * 从sql文件获取纯sql语句
     * @param  string $sql_file sql文件路径
     * @param  bool $string 如果为真，则只返回一条sql语句，默认以数组形式返回
     * @param  array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀"my_"替换成"me_"
     *         这种前缀替换方法不一定准确，比如正常内容内有跟前缀相同的字符，也会被替换
     * @return mixed
     */
    public static function getSqlFromFile($sql_file = '', $string = false, $replace = [])
    {
        if (!file_exists($sql_file)) {
            return false;
        }

        // 读取sql文件内容
        $handle = self::read_file($sql_file);

        // 分割语句
        $handle = self::parseSql($handle, $string, $replace);

        return $handle;
    }

    /**
     * 分割sql语句
     * @param  string $content sql内容
     * @param  bool $string 如果为真，则只返回一条sql语句，默认以数组形式返回
     * @param  array $replace 替换前缀，如：['my_' => 'me_']，表示将表前缀my_替换成me_
     * @return array|string 除去注释之后的sql语句数组或一条语句
     */
    public static function parseSql($content = '', $string = false, $replace = [])
    {
        // 被替换的前缀
        $from = '';
        // 要替换的前缀
        $to = '';

        // 替换表前缀
        if (!empty($replace)) {
            $to   = current($replace);
            $from = current(array_flip($replace));
        }

        if ($content != '') {
            // 纯sql内容
            $pure_sql = [];

            // 多行注释标记
            $comment = false;

            // 按行分割，兼容多个平台
            $content = str_replace(["\r\n", "\r"], "\n", $content);
            $content = explode("\n", trim($content));

            // 循环处理每一行
            foreach ($content as $key => $line) {
                // 跳过空行
                if ($line == '') {
                    continue;
                }

                // 跳过以#或者--开头的单行注释
                if (preg_match("/^(#|--)/", $line)) {
                    continue;
                }

                // 跳过以/**/包裹起来的单行注释
                if (preg_match("/^\/\*(.*?)\*\//", $line)) {
                    continue;
                }

                // 多行注释开始
                if (substr($line, 0, 2) == '/*') {
                    $comment = true;
                    continue;
                }

                // 多行注释结束
                if (substr($line, -2) == '*/') {
                    $comment = false;
                    continue;
                }

                // 多行注释没有结束，继续跳过
                if ($comment) {
                    continue;
                }

                // 替换表前缀
                if ($from != '') {
                    $line = str_replace('`'.$from, '`'.$to, $line);
                }

                // sql语句
                array_push($pure_sql, $line);
            }

            // 只返回一条语句
            if ($string) {
                return implode($pure_sql, "");
            }

            // 以数组形式返回sql语句
            $pure_sql = implode($pure_sql, "\n");
            $pure_sql = explode(";\n", $pure_sql);
            return $pure_sql;
        } else {
            return $string == true ? '' : [];
        }
    }

    /**
     * 读取文件内容
     * @param $filename  文件名
     * @return string 文件内容
     */
    public static function read_file($filename) {
        $content = '';
        if(function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if(@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        return $content;
    }
}