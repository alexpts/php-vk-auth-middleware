<?php
declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use PTS\NextRouter\Next;
use PTS\VkAuthMiddleware\VkAuthProfileMiddleware;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

require_once '../vendor/autoload.php';

$app = new Next;

$vkAppId = 12345678;
$vkAppSecret = 'xxxxxxxxxxxx';
$vkLoginMd = new VkAuthProfileMiddleware($vkAppId, $vkAppSecret);

$app->getStoreLayers()->middleware($vkLoginMd, ['path' => '/vk/login/']);
$loginLayer = $app->getStoreLayers()->getLayerFactory()->callable(static function (ServerRequestInterface $request) {
    /** @var \PTS\VkAuthMiddleware\VkToken $token */
    ['token' => $token, 'profile' => $profile] = $request->getAttribute('vk-auth');
    return new JsonResponse([
        'profile' => $profile,
        'user_id' => $token->getUserId(),
    ]);
}, ['path' => '/vk/login/']);

$otherwiseLayer = $app->getStoreLayers()->getLayerFactory()->callable(static function () {
    return new JsonResponse(['error' => 'not found'], 404);
});

$app->getStoreLayers()->addLayer($loginLayer);
$app->getStoreLayers()->addLayer($otherwiseLayer);


$request = ServerRequestFactory::fromGlobals();
$response = $app->handle($request);

(new SapiEmitter)->emit($response);
