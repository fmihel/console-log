<?php
use fmihel\console;

function test2func(...$args){
    console::log('line[5] func[test2func] file[test2.php]');    
}

class Test2 {
    function aaa(){
        $test1 = new \Test1();
        $test1->f1(1);
    }
}


?>