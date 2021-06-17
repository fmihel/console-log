<?php
use fmihel\console;

class Test1 {
    public $param=6;
    function mm(){
        console::log('line[7] file[test1] func[mm] class[Test1]');
        throw new \Exception(' generate in m');
    }
    function f1($a,$b=10){
        $this->mm();
    }

    
}

?>