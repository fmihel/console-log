<?php

use fmihel\console;

const DS_ROWS  = [
    ['id'=>1,"NAMEFULLLENGTH"=>'Mike',"AGE"=>20,'ID_CLIENT'=>1],
    ['id'=>2,"NAMEFULLLENGTH"=>'Soma',"AGE"=>43,'ID_CLIENT'=>2],
    ['id'=>3,"NAMEFULLLENGTH"=>'Keks',"AGE"=>78,'ID_CLIENT'=>3],
    ['id'=>4,"NAMEFULLLENGTH"=>'Pretor wefjhwrf whjer jwjerh',"AGE"=>5,'ID_CLIENT'=>4],
    ['id'=>3,"NAMEFULLLENGTH"=>'Keks',"AGE"=>78,'ID_CLIENT'=>3],

];

const NUMS = [
    [1,2,4,52],
    ['A','T',34322,'2'],
    ['QW','3',2,67,'F',9,2],
    [0,8,8,63],
];

function out(){

    console::table(DS_ROWS,['debug_backtrace_level'=>4,'select_row'=>2]);

}
function saa(){
    out();
}

//console::table(DS_ROWS);

//console::table(NUMS);
saa();

?>