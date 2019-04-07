<?php
declare(strict_types=1);

namespace PTS\VkAuthMiddleware;

use GuzzleHttp\Client;
use function GuzzleHttp\json_decode as json_decode_guzzle;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * VK Authorization code flow
 * @see - https://vk.com/dev/authcode_flow_user
 */
class VkAuthMiddleware implements MiddlewareInterface
{

    /** @var int */
    protected $appId;
    /** @var string */
    protected $appSecret;

    /** @var string|null */
    protected $redirectUrl;
    /** @var array */
    protected $state = [];
    /** @var int - https://vk.com/dev/permissions */
    protected $scope = 0;
    /** @var string */
    protected $version = '5.92';

    /** @var Client */
    protected $httpClient;
    protected $attributeAuth = 'vk-auth';

    public function __construct(int $appId, string $appSecret, array $scope = [VkScope::DEFAULT])
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->scope = (int)array_sum($scope);

        $this->httpClient = new Client([
            'http_errors' => false,
            'connect_timeout' => 5
        ]);
    }

    /**
     * @inheritDoc
     *
     * @throws VkException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $params = $request->getQueryParams();
        $error = $params['error'] ?? null;
        $code = $params['code'] ?? null;
        $state = $params['state'] ?? '[]';
        $state = json_decode($state, true);

        if ($error) {
            throw new VkException(sprintf(
                'Error: %s. Description: %s',
                $error,
                $params['error_description'] ?? ''
            ));
        }

        if ($code === null) {
            return $this->redirectToVkLogin($request, $state);
        }

        $token = $this->authByClientCode((string)$code, $state);
        $request = $this->addAttributeToRequest($request, $token);

        return $next->handle($request);
    }

    public function redirectToVkLogin(ServerRequestInterface $request, array $state): ResponseInterface
    {
        $backRedirect = $this->redirectUrl ?? $this->getCurrentUrl($request);

        $state = array_merge($this->state, $state);
        $state['back_redirect'] = $this->redirectUrl;
        $state['scope'] = $this->scope;

        $url = sprintf(
            'https://oauth.vk.com/authorize?client_id=%d&redirect_uri=%s&v=%s&scope=%d&response_type=code&state=%s',
            $this->appId,
            $backRedirect,
            $this->version,
            $this->scope,
            json_encode($state)
        );

        return new RedirectResponse($url);
    }

    protected function getCurrentUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $port = $uri->getPort();
        $url = $uri->getScheme() . '://' . $uri->getHost();
        $url .= $port === 80 || $port === 443 ? '' : ":$port";
        $url .= $uri->getPath();

        return $url;
    }

    /**
     * @param string $code
     * @param array $state
     *
     * @return VkToken
     * @throws VkException
     */
    public function authByClientCode(string $code, array $state): VkToken
    {
        $url = sprintf(
            'https://oauth.vk.com/access_token?client_id=%d&client_secret=%s&redirect_uri=%s&code=%s',
            $this->appId,
            $this->appSecret,
            $state['back_redirect'],
            $code
        );

        $response = $this->httpClient->get($url);
        $json = json_decode_guzzle($response->getBody()->getContents(), true);
        $token = new VkToken($json);

        if ($response->getStatusCode() !== 200 || $token->hasError()) {
            throw $token->getException();
        }

        return $token;
    }

    protected function addAttributeToRequest(ServerRequestInterface $request, VkToken $token): ServerRequestInterface
    {
        return $request->withAttribute($this->attributeAuth, [
            'token' => $token,
        ]);
    }
}
