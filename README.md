# PHP-VIT
Minimal PHP template engine.

** VIT ** let's you seperate your frontend code from your backend code.

It's basic, simple and easy to use.

Installation, Configuration & Setup:

Create a config.php file and setup VIT

```php
require_once __DIR__.'/VIT/VITAutoload.php';

$vitConfig = array('binder' => ['{{','}}'], 'dir' => '/path/to/template');

try {

    $vit = new VIT\VIT($vitConfig);
    
} catch(VIT\Exception\Config $e) {

    echo $e->getMessage();
}
```

Now, let's create a simple page using VIT

index.php
```php
#include config.php
require_once 'config.php';

try {

    #Assign a variable to vit
    $vit
        #Assign a title variable
        ->('title', 'VIT Demo page')
        
        #Compile and build template
        ->build('index');

} catch (VIT\Exception\Build $e) {

    die($e->getMessage());
}
```

In '/path/to/template' directory, create 'index.vit'
```
<!DOCTYPE html>
<html>
    <head>
        <title>{{ title }}</title>
    </head>
    <body>
        Hello!!!
    </body>
</html>
```
