<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL & ~E_NOTICE);

// in real project load as
// 
require_once __DIR__.'/../source/console.php';
require_once __DIR__.'/test1.php';
require_once __DIR__.'/test2.php';

use fmihel\console;

console::params([
    'header'=>'[short:line] ',
    'stringQuotes'=>'',
 
]);

function aa($msg=''){
    
    try{
        
        throw new \Exception('aa rais except');    
        //console::doThrow('text=','more');
        return 1;
                    
    }catch(Exception $e){
        //$msg = 'Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage();
        //error_log($msg);
        //throw new Exception('--'.$msg);
        console::doThrow($e);
    }
    
    return 0;
}

function bb($msg=''){
    
    try{
        console::error('test error');
        aa();
        throw new Exception('bb rais except');    
    
    }catch(Exception $e){
        //$msg = '  Exception ['.__FILE__.':'.__LINE__.'] '.$e->getMessage();
        //error_log($msg);
        //throw new Exception($e->getMessage());
        console::doThrow($e);
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
    //throw new \Exception("Error Processing Request", 1);        
} catch (\Exception $e) {
    //console::log($e->getMessage());
    console::error($e);
};

console::log('qjhedjhew');

?>