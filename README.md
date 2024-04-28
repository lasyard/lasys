# lasys

[![PHP Composer](https://github.com/lasyard/lasys/actions/workflows/php.yml/badge.svg)](https://github.com/lasyard/lasys/actions/workflows/php.yml)
[![NodeJS with Webpack](https://github.com/lasyard/lasys/actions/workflows/webpack.yml/badge.svg)](https://github.com/lasyard/lasys/actions/workflows/webpack.yml)

`Lasys` is a simple PHP framework.

## Usage

Put `lasys` in your project:

```sh
cd "dir_of_your_project"
git clone "git@github.com:lasyard/lasys.git"
```

or as a submodule if your project is also managed by git:

```sh
git submodule add "git@github.com:lasyard/lasys.git"
```

Build the public resources:

```sh
npm install
npm run release-build
```

Create a directory to put the public resources:

```sh
mkdir "pub"
```

then make a symlink from `lasys/pub` to `pub/sys`:

```sh
ln -snf "lasys/pub" "pub/sys"
```

Create app entry `entry.php`. The contents of the file is like:

```php
<?php
define('ROOT_PATH', __DIR__);
require_once 'lasys/src/sys.php';
Sys::app()->run();
```

It is crucial to put the file at the root of project, so that `ROOT_PATH` can be defined properly.

Rewrite all to `entry.php` except `/pub`. for example, in `.htaccess`:

```apache
DirectoryIndex disabled
Options -Indexes -Multiviews
RewriteEngine On

RewriteRule !^pub index.php [L,NS]
```

## Config

There must be a directory named `configs` in the root of your project to put your config files. Config files are `.php` files.

### `defs.php`

In this file, constants are defined. If one is not defined, it will be set to a default value.

```php
date_default_timezone_set('Asia/Shanghai');
define('APP_TITLE', 'Lasys');     // The title of app.
define('DATA_DIR', 'data');       // The root dir of website contents.
define('PUB_DIR', 'pub');         // The dir of public assets.
define('VIEW_DIR', 'views');      // The dir of view files.
define('ACTIONS_DIR', 'actions'); // The dir of actions.
define('SITE', 'unknown');        // For multi-site deployment.
```

The values defined above are also the default values.
