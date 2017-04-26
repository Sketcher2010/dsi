<?php

require_once __DIR__ . '/../vendor/Sketcher/Db.php';
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$Db = new Db();
//Request::setTrustedProxies(array('127.0.0.1'));
$r = $Db->query("SELECT * FROM `goods`");
$goods = [];
while ($a = $Db->fetch($r)) {
    $goods[] = $a;
}

$app->get('/', function () use ($app, $Db, $goods) {
    return $app['twig']->render('index.html.twig', array("goods" => $goods));
})
    ->bind('form');

$app->post('/', function () use ($app, $Db, $goods) {

    $data = $_POST;
    $email = $Db->escape($data["email"]);
    if (!preg_match('/^[a-zA-Z0-9\.\-\_]+@([a-zA-Z0-9\-\_]+\.)+[a-zA-Z]{2,6}$/is', $email)) {
        return $app['twig']->render('index.html.twig', array("goods" => $goods, "error" => array("text" => "Enter a valid e-Mail.", "type" => "warn")));
    }
    $fio = $Db->escape($data["fio"]);
    if (!isset($fio)) {
        return $app['twig']->render('index.html.twig', array("goods" => $goods, "error" => array("text" => "Enter a valid fio.", "type" => "warn")));
    }
    $address = $Db->escape($data["address"]);
    if (!isset($address)) {
        return $app['twig']->render('index.html.twig', array("goods" => $goods, "error" => array("text" => "Enter a valid address.", "type" => "warn")));
    }

    $goods_cost = 0;
    foreach ($data["goods"] as $id => $data_good) {
        if ((int)$data_good < 0) {
            return $app['twig']->render('index.html.twig', array("goods" => $goods, "error" => array("text" => "The quantity of goods must be at least zero.", "type" => "warn")));
        } else {
            $goods_cost += (int)$data_good * $goods[$id - 1]["price"];
        }
    }

    if (!$Db->insert("orders", array("email" => $email, "fio" => $fio, "address" => $address, "cost" => $goods_cost))) {
        return $app['twig']->render('index.html.twig', array("goods" => $goods, "error" => array("text" => "We have problems with DB, try again later.<br>" . $Db->error(), "type" => "attention")));
    }

    return $app['twig']->render('thanks.html.twig', array("cost" => $goods_cost));
})->bind('form-post');


$app->get('/show', function () use ($app, $Db) {
    $q = $Db->query("SELECT * FROM `orders`");
    $orders = [];
    while($order = $Db->fetch($q)) {
        $orders[] = $order;
    }
    return $app['twig']->render('show.html.twig', array("orders" => $orders));
})
    ->bind('show');


$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/' . $code . '.html.twig',
        'errors/' . substr($code, 0, 2) . 'x.html.twig',
        'errors/' . substr($code, 0, 1) . 'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
