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


// Ex: 1 simple call
console::log("test fore some string \n out with enter for some more");
// Ex: 1 simple call

//console::log(['a','b','c'],new \Test1(),'s',193,true);

test2func('jsjsj');


//$test1 = new Test1();
//$test1->f1(10);

//echo '</xmp>';

?>