<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

$app->before(
    function(Request $request) use ($app) {
        if ($request->headers->get('api-username') != 'TestUser') {
            return $app->json(
                array(
                    'message' => "You're not allowed to see this page",
                    'code' => 1000
                ),
                401
            );
        }
    }
);

$app->delete('users/{id}', function($id) use ($app) {
    $app['db']->delete(
        'users',
        array(
            'id' => $id
        )
    );

    return $app->json(
        array(
            'message' => 'User deleted succesfully!'
        ),
        204
    );
});

$app->put('users/{id}', function($id) use ($app) {
    $app['db']->update(
        'users',
        array(
            'name' => $app['request']->request->get('name'),
            'surname' => $app['request']->request->get('surname'),
            'email' => $app['request']->request->get('email'),
            'is_active' => $app['request']->request->get('is_active'),
        ),
        array(
            'id' => $id
        )
    );

    return $app->json(
        array(
            'message' => 'User updated succesfully!'
        ),
        204
    );
});


$app->post('users', function() use ($app) {
    $app['db']->insert(
        'users',
        array(
            'name' => $app['request']->request->get('name'),
            'surname' => $app['request']->request->get('surname'),
            'email' => $app['request']->request->get('email'),
            'is_active' => 0
        )
    );

    return $app->json(
        array(
            'message' => 'User created succesfully!'
        ),
        201
    );
});

$app->get(
    'users/{id}',
    function($id) use ($app) {
        return $app->json(
            $app['db']->executeQuery(
                'SELECT * FROM users WHERE is_active = 1 AND id = :user_id',
                array('user_id' => $id)
            )->fetch()
        );
    }
);


$app->get(
    'users',
    function() use ($app) {
        return $app->json(
            $app['db']->executeQuery(
                'SELECT * FROM users where is_active = 1'
            )->fetchAll()
        );
    }
);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'silex',
        'user'      => 'root',
        'password'  => '',
        'charset'   => 'utf8'
    ),
));

$app->before(function (Request $request) {
    $data = json_decode($request->getContent(), true);
    $request->request->replace(is_array($data) ? $data : array());
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return $app->json(
        array(
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        )
    );
});


$app->run();
