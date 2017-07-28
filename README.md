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
        ->assign('title', 'VIT Demo page')
        
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

#### Assign variables
Direct assign
```
$vit->assign('title', 'VIT');
$vit->assign('description', 'PHP Template System');
```
Multi-Assign
```
$vit->assign([
    'title' => 'VIT',
    'description' => 'PHP Template System'
]);
```

#### Comments
VIT can be commented
```
{{!-- This is a VIT Comment --}}
```
#### Arrays, Object
(Objects are changed to arrays once assigned to vit).
```php
$vit->assign('info', ['title' => 'VIT', 'type' => 'Demo']);
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
You can also utilize the eachelse if the variable you want to loop through isn't an array or is undefined.

```
{{!-- looping through an undefined variable --}}
{{#each $vars as var}}

var is {{ var }}

{{eachelse}}

No vars assigned

{{/endeach}}
```
```
Result: No vars assigned
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

dam
```

Filters can also be used directly inline with strings
```
{{ "Hello World!" | strtoupper }}
```

Result:
```
HELLO WORLD!
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

#### in-template assign
Vit now let you assign variables in itself,
For instance, you will be calling the upper case of a variable multiple times,
You can easily re-assign that as another variable in vit

```php
//assign a title var
$vit->assign('title', 'My title goes here');
```

```
{{!-- Assign the capitalized version of title --}}
{{#assign $upperTitle->( {{ title | strtoupper}} ) }}

{{!-- Then you can call {{ upperTitle }} anywhere else --}}

The capitalized title is {{ upperTitle }}
```

#### Conditions
You can also run conditional statements using vit

```
{{#if $title}}

There is a title, which is {{title}}

{{else}}

No title

{{/endif}}
```

You can also use the elseif statement for a bit more complex condition

Note: Very complex condition should be done directly in php before assigned to vit.

```
{{#if $length < 10 }}

Length is less than 10

{{elseif $length == 10}}

Length is equal to 10

{{else}}

Length is not a number

{{/endif}}
```

#### Direct conditions
This is similar to PHP 7's "??"
If the first variable is undefined or null, it returns an alternate assigned value

eg, We create a header file with title as a variable and we want it add a default title if a custom title isn't assigned we can basically do this
```
{{ ($title) ? {{title}} : Default title }}
```

Or shorter
```
{{ $title ?? Default title }}
```
