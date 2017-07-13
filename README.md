# PHP-VIT
Minimal PHP template engine.

** VIT ** let's you seperate your frontend code from your backend code.

It's basic, simple and easy to use.

Installation, Configuration & Setup:

Create a config.php file and setup VIT

```php
require_once __DIR__.'/VIT/VITAutoload.php';

$vitConfig = array('binder' => ['{{','}}'], 'dir' => 'selected template directory');

try {

    $vit = new VIT\VIT($vitConfig);
    
} catch(VIT\Exception\Config $e) {

    echo $e->getMessage();
}
```

