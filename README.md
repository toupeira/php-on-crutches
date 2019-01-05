**HERE BE DRAGONS:** This project is very dead and published here only as a curiosity for people with morbid interests

----

# PHP on Crutches

<img src="http://i.imgur.com/6YOGWTF.png" alt="PHP on Crutches" align="right" />

PHP on Crutches is a web application framework for PHP with a focus on convention over configuration. It's loosely based on [Ruby on Rails](http://www.rubyonrails.org) and clones like [CakePHP](http://www.cakephp.org).

## Features

* MVC-like structure with dispatcher, controllers, models and views
* Database-agnostic model implementation with validation
* ORM with ActiveRecord and lazily-evaluated querysets
* Internationalization with gettext and multi-language templates
* Modular cache store with support for Xcache, APC, and files
* Logger with colored output and optional MySQL query analysis
* Configuration system with environments
* Test framework based on [SimpleTest](http://www.simpletest.org)
* Code coverage generator (needs [Xdebug](http://www.xdebug.org))
* Server launcher for [lighttpd](http://www.lighttpd.net)
* Interactive PHP console (needs [rlwrap](https://github.com/hanslub42/rlwrap))

## Requirements

* PHP 5.2.3 or newer, with PDO and the necessary database drivers
* Debian or something similar helps (and is needed for some features)

It's only tested on Apache and lighttpd with MySQL and SQLite.

## Configuration

* Use `config/application.php` for global settings
* Use `config/environments/*` for environment-specific settings
* See `lib/config.php` for all available settings and defaults

* You can define Rails-style routes in `config/routes.php`
* You can configure your database connections in `config/database.php`

* The included `script/server` should run lighttpd configured for URL rewriting.<br />
  (Note: serving files outside of `images`, `stylesheets` and `javascripts` doesn't work.)

## Usage

Here are a few pointers to get started:

* If your webserver is setup correctly (or you use `script/server`) you should see a welcome page with some information about your system.

* Use `script/generate` vor some (very) basic scaffolding.

* Create models in `app/models`, e.g. for the model `User` you would use the file `app/models/user.php`. The model can either extend from the base `Model` or from `ActiveRecord`.

* Create controllers in `app/controllers`, e.g. for the path `/foo` you would create a `FooController` in `app/controllers/foo_controller.php`. The controller needs to extend from `ApplicationController`.

* For each action, add a public method to the controller with the same name as the action. Further path parts will be used as arguments to this method, e.g. for the path `/foo/edit/1` you would create a method `edit($id)`.

* Inside the action, use `$this->set($key, $value)` to assign values to the view.

* Create views in `app/views/foo`, e.g. for the action `edit` you would create the file `app/views/foo/edit.thtml`. This is a normal PHP file where you can display the variables assigned in the controller.

* Add generic helper functions in `app/helpers/application_helper.php`.

* Add controller-specific helper functions in `app/helpers`, e.g. for the `FooController` you would create `app/helpers/foo_helper.php`.<br />

For further details please refer to the source code...

## License

PHP on Crutches is distributed under the [MIT license](http://dev.diarrhea.ch/svn/php-on-crutches/trunk/COPYING)
