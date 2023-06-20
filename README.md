# Template Parser
This is a lightweight template parser class for PHP. It provides a simple way to parse and render templates using a familiar double curly braces syntax.

## Usage
1. Instantiate the `Template` class:

```php
$template = new Template();
```

2. Parse a template file:

```php
$template->parse('path/to/template.html', ['variable' => 'value']);
```

The `parse` method accepts the path to the template file as the first argument and an optional associative array of data variables as the second argument. The template file will be parsed, and the variables will be accessible within the template.

3. Compilation and Caching

The `Template` class compiles the templates to PHP code for better performance. The compiled templates are cached in a specified directory to avoid repeated compilation.

You can set the cache directory by assigning a value to the `$cache_dir` property:

```php
$template->cache_dir = 'path/to/cache/dir/';
```

You can also specify the cache directory during object initialization by passing a configuration array:

```php
$config = ['cache_dir' => 'path/to/cache/dir/'];
$template = new Template($config);
```

To clear the template cache and delete all cached files, use the `clearCache` method:

```php
$template->clearCache();
```

## Template Syntax

The template syntax uses double curly braces (`{{ ... }}`) for placeholders and control statements.

### Variables

To display a variable, use the double curly braces syntax with the variable name:

```html
{{ variable }}
```

### Control Statements

The template engine supports the following control statements:

#### Include

The `include` statement allows you to include other template files within a template:

```html
{{@ include 'path/to/file.html' }}
```

#### Extend

The `extend` statement allows you to extend a base template and define blocks:

```html
{{@ extend 'path/to/base.html' }}

{{@ setblock 'content' }}
<!-- Block content here -->
{{@ endsetblock }}
<!-- Additional content -->

```

#### Block

The `block` statement is used in conjunction with the `extend` statement to define and override blocks:

```html
{{@ block 'content' }}
<!-- Block content here -->
{{@ endblock }}
```

Inside the block, you can use `@parent` to include the content of the parent block:

```html
{{@ block 'content' }}
@parent
<!-- Additional content here -->
{{@ endblock }}
```

## Credits

This `Template` class is based on the Template class by David Adams. You can find the original implementation here.
License

This `Template` class is licensed under the MIT License. Feel free to use and modify it according to your needs.
