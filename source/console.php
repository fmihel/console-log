<?php
namespace fmihel;

/** Класс вывода в log файл, с интерфейсом похожим на интерфейс console для js
 * Class out to log file like interface as console for js
*/
class console{
    private static $params= [
        'break'     =>"\n", // line break symbol
        'breakFirst'=>true, // true - before print first param was out break
        'breakOnlyComposite'=>true, // break only arg or one of args is composite object (array,object,...)
        'printParamNum'=>true,
        'header'=>'[file{object}:line] ', // format for header, file can be [file,short,name] 
        'short'=>3, // сount of dir for input when format header use short
        'headerReplace'=>['from'=>['{}'],'to'=>['']], // replace strings in header after assign format
        'stringQuotes'=>'"', // quotes for print string
        'gap'=>' ',// margin between args in one line out string
        'onGetExceptionMessage'=>false, // callback as function ($e:Exception) :string
        'table_field_len'=>10,// width in chars for out col in console::table
        'debug_backtrace_level'=>3, // use debug_backtrace_level=4 for def header on call level up
    ];
    
    /** get or set console param
     * @return array of params 
    */
    public static function params($params = false){
        if (gettype($params) === 'array')
            self::$params = array_merge(self::$params,$params);
        return self::$params;
    }
    /** форматирование списка аргументов к выводу */
    private static function _formatArgs(...$args){
        $p = self::$params;
        $out = '';
        $num = 0;
        
        $composite = self::isComposite($args);

        foreach($args as $arg){
            $gap = ($out !== ''?$p['gap']:'');
            $break = (($p['breakOnlyComposite']&& $composite) && ($out !== '' || $p['breakFirst']) );
            $out.=
                ($break ? $p['break'] : $gap )
                .( ( $p['printParamNum'] && $break )?'#'.($num++).': ' :'' )
                .self::argToStr($arg);
        }
        return $out;

    }
    /** вывод в лог */
    public static function log(...$args){

        $trace = self::trace();
        error_log(self::getHeader($trace).self::_formatArgs(...$args));
    }
    /** клон console::log */
    public static function debug(...$args){
        $trace = self::trace();
        error_log(self::getHeader($trace).self::_formatArgs(...$args));
    }
    /** клон console::log */
    public static function info(...$args){
        $trace = self::trace();
        error_log(self::getHeader($trace).self::_formatArgs(...$args));
    }
    /** вывод в лог либо аналогично выводу log с приставкой error либо вывод объекта Exception*/
    public static function error(...$args){
        $p = self::$params;

        $trace = self::trace();
        
        $out = '';
        $num = 0;
        $is_exception = false;
        $composite = self::isComposite($args);
        if ( count($args) === 1 && is_a($args[0],'\Exception') ){
            $is_exception = true;
            $composite = false;
        }

        foreach($args as $arg){
            $gap = ($out !== ''?$p['gap']:'');
            $break = (($p['breakOnlyComposite']&& $composite) && ($out !== '' || $p['breakFirst']) );
            $out.=
                ($break ? $p['break'] : $gap )
                .( ( $p['printParamNum'] && $break )?'#'.($num++).': ' :'' )
                .self::argToStr($arg,['exceptionAsObject'=>false]);
        }

        if ($is_exception){
            
            self::line();
            //error_log(print_r($args[0]->getTrace(),true));
            self::_log_exception($args[0],$trace);
            self::line();
        }else
            error_log('Error '.self::getHeader($trace).$out);

    }
    /** логирование Exception  */
    private static function _log_exception($e,$tr){

        $p = self::$params;
        //--------------------------------------------------------------
        
        $msg = ( $p['onGetExceptionMessage'] ? $p['onGetExceptionMessage']($e) : $e->getMessage() );
        
        $msg = $p['stringQuotes'].$msg.$p['stringQuotes'];
        $traces = $e->getTrace();
        $count = count($traces);
        //--------------------------------------------------------------
        $object = ['file'=>$e->getFile(),'line'=>$e->getLine()];
        if ($count>0){
            $first = $traces[0];
            $object['class']    =isset($first['class'])?$first['class']:'';
            $object['type']     =isset($first['type'])?$first['type']:'';
            $object['function'] =isset($first['function'])?$first['function'].'()':'';
        }
        //--------------------------------------------------------------
        error_log('Exception '.self::getHeader($tr).$msg);
        //--------------------------------------------------------------
        for($i=0;$i<$count;$i++){
            
            if ($i<$count-1){
                $traces[$i]['function'] = isset($traces[$i+1]['function'])?$traces[$i+1]['function']:'';
                $traces[$i]['class'] = isset($traces[$i+1]['class'])?$traces[$i+1]['class']:'';
                $traces[$i]['type'] = isset($traces[$i+1]['type'])?$traces[$i+1]['type']:'';
            }else{
                $traces[$i]['function'] = '';
                $traces[$i]['class'] = '';
                $traces[$i]['type'] = '';

            }
        }                
        //--------------------------------------------------------------
        for($i=0;$i<$count;$i++){   
            $trace = $traces[$count-$i-1];
            error_log('trace     '.self::getHeader($trace));
        };
        //--------------------------------------------------------------
        error_log('trace     '.self::getHeader($object));
        //--------------------------------------------------------------
    }
    /** formating file name for use in header
     * @param {string} $name - original file name
     * @param {string} $format - type of format 'file' | 'name' | 'short' 
     * @return string
    */
    private static function formatFileName(string $name,string $format):string{
        $p = self::$params;
        
        if ($format === 'name')
            return basename($name);

        if ($format==='short'){
            
            $name = str_replace('/','\\',$name);
            $dirs = explode('\\',$name);
            
            $format= $p['short']+2;
            
            $len = count($dirs);

            if ($len>=$format){
                $count = min($len,$format);
                $out = '';
                for($i = $len-1;$i>$len-$count;$i--)
                    $out = $dirs[$i].($out!==''?'\\':'').$out;
                return  ($len!=$format?'..\\':'').$out;
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
    private static function trace(){

        $p = self::$params;
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS,$p['debug_backtrace_level']);
        $len = count($trace);
        $out = [
            'line'=>false,
            'func'=>false,
            'type'=>false,
            'file'=>false,
            'class'=>false,
            'fmt'=>0
        ];

        if ($len===2){
            $out['fmt']=1;            
            
            $out['line'] = isset($trace[1]['line']) ? $trace[1]['line'] : false;
            $out['file'] = isset($trace[1]['file']) ? $trace[1]['file'] : false;
            
        };
        
        if ($len===3){        
            $out['fmt']=2;;            
            
            $out['line']=isset($trace[1]['line']) ? $trace[1]['line'] : false;
            $out['file']=isset($trace[1]['file']) ? $trace[1]['file'] : false;
            $out['func']=isset($trace[2]['function']) ? $trace[2]['function'] : false;
            $out['type']=isset($trace[2]['type']) ? $trace[2]['type'] : false;
            $out['class']=isset($trace[2]['class']) ? $trace[2]['class'] : false;
        }

        if ($len===4){        
            $out['fmt']=2;;            
            
            $out['line']=isset($trace[2]['line']) ? $trace[2]['line'] : false;
            $out['file']=isset($trace[2]['file']) ? $trace[2]['file'] : false;
            $out['func']=isset($trace[3]['function']) ? $trace[3]['function'] : false;
            $out['type']=isset($trace[3]['type']) ? $trace[3]['type'] : false;
            $out['class']=isset($trace[3]['class']) ? $trace[3]['class'] : false;
        }

        return $out;
    }
    /** пулучить заголовок сообщения согласно формату params['header]
     * @return string for out before log message
    */
    private static function getHeader($trace):string{
        $p = self::$params;
        $trace = array_merge([
            'file'=>false,
            'line'=>false,
            'class'=>false,
            'func'=>false,
            'function'=>false,
            'type'=>false,
        ],$trace);

        if (!$trace['func'] && $trace['function'])
            $trace['func'] = $trace['function'];

        $file = $trace['file'] ? self::formatFileName($trace['file'],'file') : '';
        $name = $trace['file'] ? self::formatFileName($trace['file'],'name') : '';
        $short = $trace['file'] ? self::formatFileName($trace['file'],'short') : '';
        $line = $trace['line'] ? $trace['line'] : '';
        
        $object = '';
        if ($trace['class']||$trace['func']){
            $object.=$trace['class'] ? $trace['class'] :'';
            $object.=$trace['type'] ? $trace['type'] :'';
            $object.=$trace['func'] ? $trace['func'].'()' :'';
        }

        $out = str_replace(
            ['file','name','short','object','line'],
            [$file,$name,$short,$object,$line],
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
    private static function argToStr($arg,Array $config=[]):string{
        $c = array_merge([
            'exceptionAsObject' => true,
        ],$config);
        $p = self::$params;

        $type = gettype($arg);

        if ($type === 'string') 
            return $p['stringQuotes'].$arg.$p['stringQuotes'];
        
        if ($type === 'integer')
            return ''.$arg;

        if ($type === 'double')
            return ''.$arg;
            
        if ($type === 'boolean')
            return $arg?'true':'false';

        if ($type === 'NULL')
            return 'NULL';

        if ($type === 'object' && is_a($arg,'\Exception') && !$c['exceptionAsObject']){
            
            $msg = $arg->getMessage();
            return 'Exception(code:'.$arg->getCode().',line:'.$arg->getLine().') : '.$p['stringQuotes'].$msg.$p['stringQuotes'];
        }
            
        return print_r($arg,true);
            
        
    }
    /** determinate have the args a composite param (array,object,res)
     * @return boolean
    */
    private static function isComposite(Array $args):bool{
        foreach($args as $arg){
            $type = gettype($arg);
            if (array_search($type,['string','integer','boolean','double','NULL']) === false) {
                return true;
            }
        };
        return false;
    }
    /** отрисовывает разделительную линию */
    public static function line($line='-',$count = 60){
        error_log(str_repeat($line,$count));        
    }
    
    private static function gettype($value){
        $type = gettype($value);
        
        if ($type==='array'){
            if ( count(array_filter(array_keys($value), 'is_string')) > 0 ){
                $type = 'assoc';
            };
        };

        return $type;
    }
    /** отрисовывает таблицу */
    public static function table(array $rows,array $params=[
        'table_name'=>'table',
        //'debug_backtrace_level'=>3,
        //'table_field_len'=>10,
    ]){

        $storyParams = array_merge(self::$params);
        
        $params = array_merge(self::$params,$params);
        self::$params = $params;
        
        $field_len = $params['table_field_len'];
        $sep = '|';
        $sep_len = 1;
        $num_len = 5;

        $trace = self::trace();
        $count = count($rows);
        $header = self::getHeader($trace).$params['table_name'].'/count='.$count;
        $width = mb_strlen($header);
        self::line('-',$width);
        error_log($header);
        
        if ($count>0){
            if (self::gettype($rows[0]) === 'assoc'){
                $keys = array_keys($rows[0]);
                $width = $field_len*(count($keys))+$num_len;
                self::line('-',$width);
                $i = 0;    
                foreach($rows as $row){
                    if($i===0){
                        $out = 'N'.str_repeat(' ',$num_len-mb_strlen('N'));
                        foreach($keys as $key){
                            $val = isset($key)?$key:'?';
                            $val = trim(mb_substr($val.'',0,$field_len-1-$sep_len));
                            $val.=str_repeat(' ',$field_len-mb_strlen($val)-$sep_len);
                            $out.=$sep.$val;
                        }
                        error_log($out);                    
                        self::line('-',$width);
                    }
                    $out = $i.str_repeat(' ',$num_len-mb_strlen($i.''));
                    foreach($keys as $key){
                        $val = isset($row[$key])?$row[$key]:'null';
                        $val = trim(mb_substr($val.'',0,$field_len-1-$sep_len));
                        $val.= str_repeat(' ',$field_len-mb_strlen($val)-$sep_len);
                        $out.=$sep.$val;
                    }
                    error_log($out);
                    $i++;
                }
            }else{
                $width = $field_len*(count($rows[0])+1);
                self::line('-',$width);
                for($i=0;$i<count($rows);$i++){
                    $out = $i.str_repeat(' ',$field_len-mb_strlen($i.''));
                    $row = $rows[$i];
                    for($j = 0;$j<count($row);$j++){
                        $val = isset($row[$j])?$row[$j]:'null';
                        $val = trim(mb_substr($val.'',0,$field_len-1));
                        $val.= str_repeat(' ',$field_len-mb_strlen($val));
                        $out.=$val;
                    }
                    error_log($out);
                }
            }
        }
        self::line('-',$width);

        self::$params = $storyParams;
    }
    
}   
    


?>

