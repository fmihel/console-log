<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE);

// in real project load as
// 
require_once __DIR__.'/../source/console.php';
require_once __DIR__.'/test1.php';
require_once __DIR__.'/test2.php';
require_once __DIR__.'/test3.php';
use fmihel\console;

console::params([
    'header'=>'[short{object}:line] ',
    'stringQuotes'=>' ',
 
]);
class test{
    static function ttt(){
        try{
            //throw new \Exception('rais in test');    
            $test1 = new Test1();
            $test1->mm();
            return 1;
                        
        }catch(Exception $e){
            console::error($e);        
        }
    
    }
}
function aa($msg=''){
    
    try{
        
        test::ttt();
                    
    }catch(Exception $e){
        throw $e;        
    }
    
    return 0;
}

function bb($msg=''){
    
    try{
        throw new \Exception('rais in test');      
        aa();
    
    }catch(Exception $e){
        //console::error($e);    
        throw $e;
    }
    
}
// Ex: 1 simple call
//console::log("test fore some string \n out with enter for some more");

// Ex: 2 simple call composite param
//console::log(['a','b','c'],new \Test1(),'s',193,true);

//test2func('jsjsj');


//$test1 = new Test1();
//$test1->f1(10);

//echo '</xmp>';

try {
    bb();
    
} catch (\Exception $e) {
    console::error($e);
};


?>