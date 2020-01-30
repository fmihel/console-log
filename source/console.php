<?php
namespace fmihel;

/** 
 * Класс вывода в log файл, с интерфейсом похожим на интерфейс 
*/
class console{
    private static $params= [
        'break'     =>"\n", // line break symbol
        'breakFirst'=>true, // true - before print first param was out break
        'breakOnlyComposite'=>true, // break only arg or one of args is composite object (array,object,...)
        'printParamNum'=>true,
        
        'short'=>3,
        'header'=>'[file{object}:line] ', // format for header, file can be [file,short,name] 
        
    ];
    private static function params($params){
        if (gettype($params) === 'array')
            self::$params = array_merge(self::$params,$params);
        
        return self::$params;
    }
    private static function formatFileName(string $name,$format):string{
        $p = self::$params;
        
        if ($format === 'name')
            return basename($name);

        if ($format==='short'){
            
            $name = str_replace('/','\\',$name);
            $dirs = explode('\\',$name);
            
            $format= $p['short']+2;
            
            $len = count($dirs);

            if ($len<=$format){
                $count = min($len,$format);
                $out = '';
                for($i = $len-1;$i>$len-$count;$i--)
                    $out = $dirs[$i].($out!==''?'\\':'').$out;
                return '..\\'.$out;
            }
        }

        return $name;
    }
    private static function trace(){
        $p = self::$params;
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS,3);
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

        return $out;
    }
    
    private static function getHeader($trace):string{
        $p = self::$params;
        
        $file = $trace['file'] ? self::formatFileName($trace['file'],'file') : '';
        $name = $trace['file'] ? self::formatFileName($trace['file'],'name') : '';
        $short = $trace['file'] ? self::formatFileName($trace['file'],'short') : '';
        $line = $trace['line'] ? $trace['line'] : '';
        
        $object = '';
        if ($trace['class']||$trace['func']){
            $object.=$trace['class'] ? $trace['class'] :'';
            $object.=$trace['type'] ? $trace['type'] :'';
            $object.=$trace['func'] ? $trace['func'] :'';
        }

        $replace = ['{}'];
        $to = [''];        
        $out = str_replace(
            ['file','name','short','object','line','{}'],
            [$file,$name,$short,$object,$line,''],
            $p['header']);
        
        return $out;    
    }
    
    private static function argToStr($arg):string{
        
        $type = gettype($arg);

        if ($type === 'string') 
            return '"'.$arg.'"';
        
        if ($type === 'integer')
            return ''.$arg;

        if ($type === 'double')
            return ''.$arg;
            
        if ($type === 'boolean')
            return $arg?'true':'false';

        if ($type === 'NULL')
            return 'NULL';
            
        return print_r($arg,true);
            
        
    }
    
    static function log(...$args){
        $p = self::$params;

        $trace = self::trace();
        
        $out = '';
        $num = 0;
        
        $composite = false;
        foreach($args as $arg){
            $type = gettype($arg);
            if (array_search($type,['string','integer','boolean','double','NULL']) === false) {
                $composite = true;
                break;
            }
        };

        foreach($args as $arg){
            
            $break = (($p['breakOnlyComposite']&& $composite) && ($out !== '' || $p['breakFirst']) );
            $prefParam =  
            $out.=
                ($break ? $p['break'] : '' )
                .( ( $p['printParamNum'] && $break )?'#'.($num++).': ' :'' )
                .self::argToStr($arg);
        }
        error_log(self::getHeader($trace).$out.'');
        
    }
}    


?>

