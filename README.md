# console-log
Class out to error_log like as console.info from js

### Install
```composer require fmihel/console-log```
### Use

```php
<?php
require_once __DIR__.'/vendor/autoload.php';
use fmihel\console;

console::log('Can out array',[1,2,3,4]);

try{
    
    throw new \Exception('generate error');

}catch(\Exception $e){
    console::error($e);
};

>
```

### Api

|name|notes|example|
|----|----|----|
**console::log(...$args);**|| console::log("text",['a',3,4]);|
**console::info(...$args);**|clone of `console::log`| console::info("text",['a',3,4]);|
**console::debug(...$args);**|clone of `console::log`| console::debug("text",['a',3,4]);|
**console::error(...$args);**|add prefix [ERROR] to out and hanldler of Exception class| console::error('division by zerro');|

### Config
For config use method `console::params([...])` with next params:
|
name|notes|example|
|----|----|----|
|break|line break symbol| console::params(['break'=>"\n"]);|
|breakFirst|true - before print first param was out break|console::params(['breakFirst'=>true]);|
|breakOnlyComposite|break only arg or one of args is composite object (array,object,...)|console::params(['breakOnlyComposite'=>true]);|
|printParamNum||        console::params(['printParamNum'=>true]);|
|header|format for header, file can be [file,short,name] |console::params(['header'=>'[file{object}:line] ']);|
|short'=>3|сount of dir for input when format header use short|console::params(['short'=>3]);|
|headerReplace|replace strings in header after assign format|console::params(['headerReplace'=>['from'=>['{}'],'to'=>['']]]);|
|stringQuotes|quotes for print string |console::params(['stringQuotes'=>'"']);|


