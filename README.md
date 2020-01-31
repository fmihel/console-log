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
**conosel::log(...$args);**|| console::log("text",['a',3,4]);|
**conosel::info(...$args);**|clone of `console::log`| console::info("text",['a',3,4]);|
**conosel::error(...$args);**|add prefix [ERROR] to out and hanldler of Exception class| console::error('division by zerro');|