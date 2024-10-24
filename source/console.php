<?php
namespace fmihel;

/** Класс вывода в log файл, с интерфейсом похожим на интерфейс console для js
 * Class out to log file like interface as console for js
 */
class console
{
    private static $params = [
        'break' => "\n", // line break symbol
        'breakFirst' => true, // true - before print first param was out break
        'breakOnlyComposite' => true, // break only arg or one of args is composite object (array,object,...)
        'printParamNum' => true,
        'header' => '[file{object}:line] ', // format for header, file can be [file,short,name]
        'short' => 3, // сount of dir for input when format header use short
        'headerReplace' => ['from' => ['{}'], 'to' => ['']], // replace strings in header after assign format
        'stringQuotes' => '"', // quotes for print string
        'gap' => ' ', // margin between args in one line out string
        'onGetExceptionMessage' => false, // callback as function ($e:Exception) :string
        'table_field_len' => 10, // width in chars for out col in console::table
        'debug_backtrace_level' => 3, // use debug_backtrace_level=4 for def header on call level up
    ];
    private static $timers = []; // list of current timers
    private static $vars = []; // [name->count] for logf

    /** get or set console param
     * @return array of params
     */
    public static function params($params = false)
    {
        if (gettype($params) === 'array') {
            self::$params = array_merge(self::$params, $params);
        }

        return self::$params;
    }
    /** форматирование списка аргументов к выводу */
    private static function _formatArgs(...$args)
    {
        $p = self::$params;
        $out = '';
        $num = 0;

        $composite = self::_isComposite($args);

        foreach ($args as $arg) {
            $gap = ($out !== '' ? $p['gap'] : '');
            $break = (($p['breakOnlyComposite'] && $composite) && ($out !== '' || $p['breakFirst']));
            $out .=
            ($break ? $p['break'] : $gap)
            . (($p['printParamNum'] && $break) ? '#' . ($num++) . ': ' : '')
            . self::_argToStr($arg);
        }
        return $out;

    }
    /** вывод в лог */
    public static function log(...$args)
    {
        $trace = self::_trace();
        error_log(self::_getHeader($trace) . self::_formatArgs(...$args));
    }
    /** вывод в лог по условию. либо $countOrCallback = int (кол-во раз вывода, по умолчанию один раз)  либо ф-ция, которая должна вернуть true для вывода*/
    public static function logf(...$argsWidthCond/*...$args, $countOrCallback = 1*/)
    {
        if (count($argsWidthCond) < 2) {
            error_log('logf must have two or more params (!!!');
            return;
        };

        $args = array_slice($argsWidthCond, 0, count($argsWidthCond) - 1);
        $countOrCallback = $argsWidthCond[count($argsWidthCond) - 1];

        $trace = self::_trace();
        $name = self::_getHeader($trace);

        if (is_int($countOrCallback)) {

            if (!isset(self::$vars[$name])) {
                self::$vars[$name] = $countOrCallback;
            }

            self::$vars[$name] -= 1;

            if (self::$vars[$name] < 0) {
                return;
            }

        } elseif (is_callable($countOrCallback)) {
            if ($countOrCallback(...$args) !== true) {
                return;
            }
        };

        error_log($name . self::_formatArgs(...$args));
    }
    /** вывод в лог единожды из данного места в файле*/
    public static function once(...$args)
    {
        $trace = self::_trace();
        $name = self::_getHeader($trace);

        if (!isset(self::$vars[$name])) {
            self::$vars[$name] = 1;
            error_log($name . self::_formatArgs(...$args));
        }
    }

    /** вывод в лог по условию  */
    public static function if($condition, ...$args)
    {
        $trace = self::_trace();
        $name = self::_getHeader($trace);

        if (is_callable($condition)) {
            if ($condition(...$args) !== true) {
                return;
            }
        } elseif (!$condition) {
            return;
        }

        error_log($name . self::_formatArgs(...$args));
    }

    /** клон console::log */
    public static function debug(...$args)
    {
        $trace = self::_trace();
        error_log(self::_getHeader($trace) . self::_formatArgs(...$args));
    }
    /** клон console::log */
    public static function info(...$args)
    {
        $trace = self::_trace();
        error_log(self::_getHeader($trace) . self::_formatArgs(...$args));
    }
    /** вывод в лог либо аналогично выводу log с приставкой error либо вывод объекта Exception*/
    public static function error(...$args)
    {
        $p = self::$params;

        $trace = self::_trace();

        $out = '';
        $num = 0;
        $is_exception = false;
        $composite = self::_isComposite($args);
        if (count($args) === 1 && is_a($args[0], '\Exception')) {
            $is_exception = true;
            $composite = false;
        }

        foreach ($args as $arg) {
            $gap = ($out !== '' ? $p['gap'] : '');
            $break = (($p['breakOnlyComposite'] && $composite) && ($out !== '' || $p['breakFirst']));
            $out .=
            ($break ? $p['break'] : $gap)
            . (($p['printParamNum'] && $break) ? '#' . ($num++) . ': ' : '')
            . self::_argToStr($arg, ['exceptionAsObject' => false]);
        }

        if ($is_exception) {

            self::line();

            self::_log_exception($args[0], $trace);

            error_log('');
            error_log('remote     ' . $_SERVER['REMOTE_ADDR']);
            error_log('uri        ' . trim($_SERVER['REQUEST_URI'] . $p['break']));

            $request_param = print_r($_REQUEST, true);
            $request_param = strlen($request_param) > 200 ? (substr($request_param, 0, 200) . '..') : $request_param;
            $request_param = str_replace(["\n", '  ', '  '], [' ', '', ''], $request_param);
            error_log('request    ' . $request_param);

            self::line();
        } else {
            error_log('Error ' . self::_getHeader($trace) . $out);
        }

    }

    private static function short_format($val)
    {

        $type = self::gettype($val);
        if ($type === 'array') {
            // $count = count($val);
            return "[...]";

        } elseif ($type === 'assoc') {
            return '{...}';
        }
        return $val;
    }

    public static function top(...$args)
    {
        $out = [];
        foreach ($args as $arg) {
            $typeArg = self::gettype($arg);
            $to = [];
            if ($typeArg === 'array') {

                for ($i = 0; $i < count($arg); $i++) {
                    $to[] = self::short_format($arg[$i]);
                };
                $arg = $to;

            } elseif ($typeArg === 'assoc') {

                foreach ($arg as $key => $val) {
                    $to[$key] = self::short_format($val);
                };
                $arg = $to;

            }
            $out[] = $arg;
        }
        $trace = self::_trace();
        error_log(self::_getHeader($trace) . self::_formatArgs(...$out));
    }

    /** логирование Exception  */
    private static function _log_exception($e, $tr)
    {

        $p = self::$params;
        //--------------------------------------------------------------

        $msg = ($p['onGetExceptionMessage'] ? $p['onGetExceptionMessage']($e) : $e->getMessage());

        $msg = $p['stringQuotes'] . $msg . $p['stringQuotes'];
        $traces = $e->getTrace();
        $count = count($traces);
        //--------------------------------------------------------------
        $object = ['file' => $e->getFile(), 'line' => $e->getLine()];
        if ($count > 0) {
            $first = $traces[0];
            $object['class'] = isset($first['class']) ? $first['class'] : '';
            $object['type'] = isset($first['type']) ? $first['type'] : '';
            $object['function'] = isset($first['function']) ? $first['function'] . '()' : '';
        }
        //--------------------------------------------------------------
        error_log('Exception ' . self::_getHeader($tr) . $msg);
        //--------------------------------------------------------------
        for ($i = 0; $i < $count; $i++) {

            if ($i < $count - 1) {
                $traces[$i]['function'] = isset($traces[$i + 1]['function']) ? $traces[$i + 1]['function'] : '';
                $traces[$i]['class'] = isset($traces[$i + 1]['class']) ? $traces[$i + 1]['class'] : '';
                $traces[$i]['type'] = isset($traces[$i + 1]['type']) ? $traces[$i + 1]['type'] : '';
            } else {
                $traces[$i]['function'] = '';
                $traces[$i]['class'] = '';
                $traces[$i]['type'] = '';

            }
        }
        //--------------------------------------------------------------
        for ($i = 0; $i < $count; $i++) {
            $trace = $traces[$count - $i - 1];
            error_log('trace     ' . self::_getHeader($trace));
        };
        //--------------------------------------------------------------
        error_log('trace     ' . self::_getHeader($object));
        //--------------------------------------------------------------
    }
    /** formating file name for use in header
     * @param {string} $name - original file name
     * @param {string} $format - type of format 'file' | 'name' | 'short'
     * @return string
     */
    private static function _formatFileName(string $name, string $format): string
    {
        $p = self::$params;

        if ($format === 'name') {
            return basename($name);
        }

        if ($format === 'short') {

            $name = str_replace('/', '\\', $name);
            $dirs = explode('\\', $name);

            $format = $p['short'] + 2;

            $len = count($dirs);

            if ($len >= $format) {
                $count = min($len, $format);
                $out = '';
                for ($i = $len - 1; $i > $len - $count; $i--) {
                    $out = $dirs[$i] . ($out !== '' ? '\\' : '') . $out;
                }

                return ($len != $format ? '..\\' : '') . $out;
            }
        }

        return $name;
    }
    /** разбор debug_backtrace
     * @return [
     * 'line'=>false  | num of line calling console command
     * 'func'=>false, | name of func calling console command
     * 'type'=>false, | type object -> or :: if func in class
     * 'file'=>false, | file name
     * 'class'=>false,| class name calling console command
     * 'fmt'=>0       | 1 - outer func 2 - class func
     * ];
     */
    private static function _trace()
    {

        $p = self::$params;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $p['debug_backtrace_level']);
        $len = count($trace);
        $out = [
            'line' => false,
            'func' => false,
            'type' => false,
            'file' => false,
            'class' => false,
            'fmt' => 0,
        ];

        if ($len === 2) {
            $out['fmt'] = 1;

            $out['line'] = isset($trace[1]['line']) ? $trace[1]['line'] : false;
            $out['file'] = isset($trace[1]['file']) ? $trace[1]['file'] : false;

        };

        if ($len === 3) {
            $out['fmt'] = 2;

            $out['line'] = isset($trace[1]['line']) ? $trace[1]['line'] : false;
            $out['file'] = isset($trace[1]['file']) ? $trace[1]['file'] : false;
            $out['func'] = isset($trace[2]['function']) ? $trace[2]['function'] : false;
            $out['type'] = isset($trace[2]['type']) ? $trace[2]['type'] : false;
            $out['class'] = isset($trace[2]['class']) ? $trace[2]['class'] : false;
        }

        if ($len === 4) {
            $out['fmt'] = 2;

            $out['line'] = isset($trace[2]['line']) ? $trace[2]['line'] : false;
            $out['file'] = isset($trace[2]['file']) ? $trace[2]['file'] : false;
            $out['func'] = isset($trace[3]['function']) ? $trace[3]['function'] : false;
            $out['type'] = isset($trace[3]['type']) ? $trace[3]['type'] : false;
            $out['class'] = isset($trace[3]['class']) ? $trace[3]['class'] : false;
        }

        return $out;
    }
    /** пулучить заголовок сообщения согласно формату params['header]
     * @return string for out before log message
     */
    private static function _getHeader($trace): string
    {
        $p = self::$params;
        $trace = array_merge([
            'file' => false,
            'line' => false,
            'class' => false,
            'func' => false,
            'function' => false,
            'type' => false,
        ], $trace);

        if (!$trace['func'] && $trace['function']) {
            $trace['func'] = $trace['function'];
        }

        $file = $trace['file'] ? self::_formatFileName($trace['file'], 'file') : '';
        $name = $trace['file'] ? self::_formatFileName($trace['file'], 'name') : '';
        $short = $trace['file'] ? self::_formatFileName($trace['file'], 'short') : '';
        $line = $trace['line'] ? $trace['line'] : '';

        $object = '';
        if ($trace['class'] || $trace['func']) {
            $object .= $trace['class'] ? $trace['class'] : '';
            $object .= $trace['type'] ? $trace['type'] : '';
            $object .= $trace['func'] ? $trace['func'] . '()' : '';
        }

        $out = str_replace(
            ['file', 'name', 'short', 'object', 'line'],
            [$file, $name, $short, $object, $line],
            $p['header']
        );

        $out = str_replace(
            $p['headerReplace']['from'],
            $p['headerReplace']['to'],
            $out
        );

        return $out;
    }
    /** translate $arg to string representation
     * @return string
     */
    private static function _argToStr($arg, array $config = []): string
    {
        $c = array_merge([
            'exceptionAsObject' => true,
        ], $config);
        $p = self::$params;

        $type = gettype($arg);

        if ($type === 'string') {
            return $p['stringQuotes'] . $arg . $p['stringQuotes'];
        }

        if ($type === 'integer') {
            return '' . $arg;
        }

        if ($type === 'double') {
            return '' . $arg;
        }

        if ($type === 'boolean') {
            return $arg ? 'true' : 'false';
        }

        if ($type === 'NULL') {
            return 'NULL';
        }

        if ($type === 'object' && is_a($arg, '\Exception') && !$c['exceptionAsObject']) {

            $msg = $arg->getMessage();
            return 'Exception(code:' . $arg->getCode() . ',line:' . $arg->getLine() . ') : ' . $p['stringQuotes'] . $msg . $p['stringQuotes'];
        }

        return print_r($arg, true);

    }
    /** determinate have the args a composite param (array,object,res)
     * @return boolean
     */
    private static function _isComposite(array $args): bool
    {
        foreach ($args as $arg) {
            $type = gettype($arg);
            if (array_search($type, ['string', 'integer', 'boolean', 'double', 'NULL']) === false) {
                return true;
            }
        };
        return false;
    }
    /** отрисовывает разделительную линию */
    public static function line($line = '-', $count = 60)
    {
        error_log(str_repeat($line, $count));
    }

    private static function gettype($value)
    {
        $type = gettype($value);

        if ($type === 'array') {
            if (count(array_filter(array_keys($value), 'is_string')) > 0) {
                $type = 'assoc';
            };
        };

        return $type;
    }
    /** отрисовывает таблицу
     * @param {array}  rows - is simple array, or array of assoc array [1,2,3,4] or [['A'=>1,'B'=>2],['A'=>4,'B'=>5],[...]]
     * @param {array}  params - ['table_name'=>'table','debug_backtrace_level'=>3,'table_field_len'=>10,'select_row'=>1]
     */
    public static function table(array $rows, array $params = [])
    {

        $storyParams = array_merge(self::$params);

        $params = array_merge(self::$params, ['table_name' => 'table', 'select_row' => false], $params);
        self::$params = $params;

        $field_len = $params['table_field_len'];
        $select_row = $params['select_row'];
        $sep = '|';
        $sep_len = 1;
        $num_len = 5;

        $trace = self::_trace();
        $count = count($rows);
        $header = self::_getHeader($trace) . $params['table_name'] . '/count=' . $count;
        $width = mb_strlen($header);
        self::line('-', $width);
        error_log($header);

        if ($count > 0) {
            if (self::gettype($rows[0]) === 'assoc') {
                $keys = array_keys($rows[0]);
                $width = $field_len * (count($keys)) + $num_len + ($select_row !== false ? 2 : 0);

                self::line('-', $width);
                $i = 0;

                $select_left = '';
                $select_right = '';

                foreach ($rows as $row) {

                    if ($select_row !== false) {
                        $select_left = ($i === $select_row ? '> ' : '  ');
                        $select_right = ($i === $select_row ? '<' : '');
                    }

                    if ($i === 0) {
                        $out = ($select_row !== false ? '  ' : '') . 'N' . str_repeat(' ', $num_len - mb_strlen('N'));
                        foreach ($keys as $key) {
                            $val = isset($key) ? $key : '?';
                            $val = trim(mb_substr($val . '', 0, $field_len - 1 - $sep_len));
                            $val .= str_repeat(' ', $field_len - mb_strlen($val) - $sep_len);
                            $out .= $sep . $val;
                        }
                        error_log($out);
                        self::line('-', $width);
                    }
                    $out = $i . str_repeat(' ', $num_len - mb_strlen($i . ''));
                    foreach ($keys as $key) {
                        $val = isset($row[$key]) ? $row[$key] : 'null';
                        $val = trim(mb_substr($val . '', 0, $field_len - 1 - $sep_len));
                        $val .= str_repeat(' ', $field_len - mb_strlen($val) - $sep_len);
                        $out .= $sep . $val;
                    }
                    if ($i === $select_row && $i > 0) {
                        self::line('.', $width);
                    }

                    error_log($select_left . $out . $select_right);
                    if ($i === $select_row && $i < $count - 1) {
                        self::line('`', $width);
                    }

                    $i++;
                }
            } else {
                $width = $field_len * (count($rows[0]) + 1);
                self::line('-', $width);
                for ($i = 0; $i < count($rows); $i++) {
                    $out = $i . str_repeat(' ', $field_len - mb_strlen($i . ''));
                    $row = $rows[$i];
                    for ($j = 0; $j < count($row); $j++) {
                        $val = isset($row[$j]) ? $row[$j] : 'null';
                        $val = trim(mb_substr($val . '', 0, $field_len - 1));
                        $val .= str_repeat(' ', $field_len - mb_strlen($val));
                        $out .= $val;
                    }
                    error_log($out);
                }
            }
        }
        self::line('-', $width);

        self::$params = $storyParams;
    }
    public static function time(string $label, $out = true)
    {
        if (isset(self::$timers[$label])) {
            $trace = self::_trace();
            error_log(self::_getHeader($trace) . "warn  timer [$label] is already running!");
        } else {
            self::$timers[$label] = microtime(true);
            if ($out) {
                $trace = self::_trace();
                error_log(self::_getHeader($trace) . "start timer [$label]");
            }
        }
    }
    public static function timeEnd(string $label)
    {
        if (!isset(self::$timers[$label])) {
            $trace = self::_trace();
            error_log(self::_getHeader($trace) . "error timer [$label] is not defined! ");
        } else {
            $current = microtime(true) - self::$timers[$label];
            unset(self::$timers[$label]);
            $trace = self::_trace();
            error_log(self::_getHeader($trace) . "stop  timer [$label] at " . round($current, 4) . '[sec]');
        }

    }
    public static function timeLog(string $label)
    {
        if (!isset(self::$timers[$label])) {
            $trace = self::_trace();
            error_log(self::_getHeader($trace) . "warn  timer [$label] is not defined! ");
        } else {
            $current = microtime(true) - self::$timers[$label];
            $trace = self::_trace();
            error_log(self::_getHeader($trace) . "now   timer [$label] is " . round($current, 4) . '[sec]');
        }

    }

}
