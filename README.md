# Laravel 5.0 Gettext

With this package you can load/parse/store gettext strings

## Installation

Begin by installing this package through Composer.

```js
{
    "require": {
        "eusonlito/laravel-gettext": "1.1.*"
    }
}
```

### Laravel installation

```php

// config/app.php

'providers' => [
    '...',
    'Eusonlito\LaravelGettext\GettextServiceProvider',
];

'aliases' => [
    '...',
    'Gettext'    => 'Eusonlito\LaravelGettext\Facade',
];
```

Now you have a ```Gettext``` facade available.

Publish the config file:

```
php artisan vendor:publish
```

### Usage

```php
__('Here your text');
__('Here your text with %s parameters', 1);
__('Here your text with parameters %s and %s', 1, 2);
```

# Gettext Files

By default, gettext .po and .mo files are stored in resources/gettext/xx_XX/LC_MESSAGES/messages.XX

xx_XX is language code like `en_US`, `es_ES`, etc...

# Configuration

#### app/config/gettext.php

```php
return array(
    /*
    |--------------------------------------------------------------------------
    | Available locales
    |--------------------------------------------------------------------------
    |
    | A array list with available locales to load
    |
    | Default locale will the first in array list
    |
    */

    'locales' => ['en_US', 'es_ES', 'it_IT', 'fr_FR'],

    /*
    |--------------------------------------------------------------------------
    | Directories to scan
    |--------------------------------------------------------------------------
    |
    | Set directories to scan to find gettext strings (starting with __)
    |
    */

    'directories' => ['app', 'resources'],

    /*
    |--------------------------------------------------------------------------
    | Where the translations are stored
    |--------------------------------------------------------------------------
    |
    | Full path is $storage/xx_XX/LC_MESSAGES/$domain.XX
    |
    */

    'storage' => 'storage/gettext',

    /*
    |--------------------------------------------------------------------------
    | Store files as domain name
    |--------------------------------------------------------------------------
    |
    | Full path is $storage/xx_XX/LC_MESSAGES/$domain.XX
    |
    */

    'domain' => 'messages',

    /*
    |--------------------------------------------------------------------------
    | Use native gettext functions
    |--------------------------------------------------------------------------
    |
    | Are faster than open files from PHP. If you have enabled the php-gettext
    | module, is recommended to enable.
    |
    */

    'native' => false,

    /*
    |--------------------------------------------------------------------------
    | Preference to load translations from format
    |--------------------------------------------------------------------------
    |
    | Some systems and formats are fatest than others (low RAM or CPU usage)
    | Available options are mo, po, php
    |
    */

    'formats' => ['mo', 'php', 'po'],

    /*
    |--------------------------------------------------------------------------
    | Cookie name
    |--------------------------------------------------------------------------
    |
    | Locale cookie name. Cookie are stored as plain, without Laravel manager
    |
    */

    'cookie' => 'locale'
);
```
