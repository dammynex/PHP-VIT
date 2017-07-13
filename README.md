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

## Working with VIT

#### Comments
VIT can be commented
```
{{!-- This is a VIT Comment --}}
```
#### Arrays, Object
(Objects are changed to arrays once assigned to vit).
```php
$vit->assign('info', ['title => 'VIT', 'type' => 'Demo']);
```

Then in vit file we can have something like this
```
Hey, this is {{ info[title] }} and we are working on the {{ info[type] }}
```

Looping through arrays
```php
$vit->assign('lists', ['a', 'b', 'c', 'd']);

$vit->assign('data', ['name' => 'Dammy', 'nick' => 'nex', 'age' => '10', 'lang' => 'PHP']);
```
And in vit
```
{{#each $lists as list}}
    {{ list }} <br>
{{/endeach}}

{{#each $data as key,val}}
    {{ key }}: {{ val }}<br>
{{/endeach}}
```
Result:
```
a
b
c
d

name: Dammy
nick: nex
age: 10
lang: PHP
```

#### Filters
VIT variable can be filtered using PHP functions

PHP
```php
$vit->assign('name', 'dammy');
```
VIT
```
{{!-- Use filters without args --}}
{{ name | strtoupper }}

{{!-- With args --}}
{{name | substr(0, 3) }}
```

Result:
```
DAMMY

DAM
```

Filters can also be used directly inline with strings
```
{{ "Hello World!" | strtoupper }}
```

Result:
```
HELLO WORLD
```

#### Includes
VIT let's you include vit files in '/path/to/template/includes'
Once VIT is correctly configured, the includes directory will be automatically created.

Create 'header.vit' in the includes directory

header.vit
```
This is the header file
```

Let's include the header in the 'index.vit' file created earlier
```
{{#include header}}
```
Multiple files can be included, for example, we create a 'nav.vit' file which contains all navigation links

nav.vit
```
<nav>
    <a href="#">Home</a>
    <a href="#">Download</a>
  </nav>
```

Now let's include the header and nav files in the index

index.vit
```
{{#include header,nav}}
```

