# Karinto

Karinto is a minimal application framework for PHP 5.3 or later inspired by [Silex](http://silex.sensiolabs.org/) based on [Lune](https://github.com/hiroy/lune).

## Required

 * PHP 5.3 or later
 * mbstring

### Optional

You can use [Twig](http://twig.sensiolabs.org/) as a template engine instead of using plain PHP templates.

## Usage

### /index.php

    <?php
    require_once 'Karinto.php';
    
    use Karinto\Application;
    use Karinto\Request;
    
    $app = new Application();
    $app->templateDir = 'templates';
    
    // if using Twig
    // $app->templateCacheDir = 'cache';
    // $app->withTwig = true;
    
    $app->error(function($code, \Exception $e = null) use ($app) {
        switch ($code) {
        case 404:
            $app->render('error_404.php');
            break;
        default:
            $app->render('error_general.php');
            break;
        }
    });
    
    $app->get('/', function(Request $req) use ($app) {
        $app->render('default.php');
    });
    
    $app->get('/foo', function(Request $req) use ($app) {
        $app['name'] = $req->param('name');
        $app->render('foo.php');
    });
    
    $app->get('/bar', function(Request $req) use ($app) {
        $session = $app->session();
        $session['name'] = $req->param('name');
        $app->redirect('/baz');
    });
    
    $app->run();

### /templates/foo.php

    <html>
    <body>
    <p><?php echo h($name); ?></p>
    </body>
    </html>

Please access "/index.php/foo?name=bar"

## License

This code is free to use under the terms of the New BSD License.

## With Composer

If using [Composer](http://getcomposer.org/) as a dependency management tool, you can bring Karinto in your environment easily with settings below.

```
{
  "minimum-stability": "dev",
  "require": {
    "karinto/karinto": "1.0.*"
  }
}
```

Using [Twig](http://twig.sensiolabs.org/) with Karinto, you should set as the same below:

```
{
  "minimum-stability": "dev",
  "require": {
    "karinto/karinto": "1.0.*",
    "twig/twig": "1.*"
  }
}
```

