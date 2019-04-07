<?php
declare(strict_types=1);

namespace PTS\VkAuthMiddleware;

use GuzzleHttp\Client;
use function GuzzleHttp\json_decode as json_decode_guzzle;

class VkApi
{
    protected $version = '5.92';
    /** @var Client */
    protected $httpClient;
    /** @var  */
    protected $accessToken;

    public function __construct(Client $httpClient, string $version = '5.92')
    {
        $this->version = $version;
        $this->httpClient = $httpClient;
    }

    public function setAccessToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    public function call(string $methodName, array $params = [], $options = []): array
    {
        $url = sprintf(
            'https://api.vk.com/method/%s?v=%s',
            $methodName,
            $this->version
        );

        $response = $this->httpClient->post($url, [
            'form_params' => array_merge($params, [
                'v' => $options['v'] ?? $this->version,
                'access_token' => $options['access_token'] ?? $this->accessToken
            ])
        ]);

        $json = $response->getBody()->getContents();
        $json = json_decode_guzzle($json, true);
        if (isset($json['error']) || $response->getStatusCode() >= 300) {
            throw new VkException('Vk api error');
        }

        return $json['response'];
    }
}
