# The plan of Karinto

Karinto is a minimal application framework for PHP 5.3 or later inspired by [Silex](http://silex.sensiolabs.org/).

## Required

 * PHP 5.3.2 or later
 * mbstring

## Usage

### /index.php

    <?php
    require_once 'Karinto.php';
    
    use Karinto\Application;
    use Karinto\Request;
    
    $app = new Application(array(
        'template_dir' => 'templates',
        'encoding' => 'UTF-8',
    ));
    
    $app->error(function(\Exception $e, $code) use ($app) {
        $app->contentTypeHtml();
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
        $app->contentTypeHtml();
        $app->render('default.php');
    }
    
    $app->get('/foo', function(Request $req) use ($app) {
        $app->contentTypeHtml();
        $app->render('foo.php', array('name' => $req->name));
    });
    
    $app->get('/bar', function(Request $req) use ($app) {
        $session = $app->session();
        $session->name = $req->name;
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

