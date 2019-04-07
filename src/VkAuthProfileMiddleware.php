<?php
declare(strict_types=1);

namespace PTS\VkAuthMiddleware;

use Psr\Http\Message\ServerRequestInterface;


class VkAuthProfileMiddleware extends VkAuthMiddleware
{

    /** @var VkApi */
    protected $apiClient;

    public function __construct(int $appId, string $appSecret, array $scope = [VkScope::DEFAULT])
    {
        parent::__construct($appId, $appSecret, $scope);
        $this->apiClient = new VkApi($this->httpClient, $this->version);
    }

    protected function addAttributeToRequest(ServerRequestInterface $request, VkToken $token): ServerRequestInterface
    {
        $request = parent::addAttributeToRequest($request, $token);
        $data = $request->getAttribute($this->attributeAuth);

        $data['profile'] = $this->getProfile($token);
        return $request->withAttribute($this->attributeAuth, $data);
    }

    protected function getProfile(VkToken $token)
    {
        $users = $this->apiClient
            ->setAccessToken($token->getAccessToken())
            ->call('users.get', [
                'user_ids' => $token->getUserId(),
                'fields' => ['about', 'activities', 'bdate', 'city', 'common_count', 'connections', 'contacts',
                    'counters', 'country', 'crop_photo', 'domain', 'exports', 'followers_count', 'has_mobile',
                    'has_photo', 'last_seen', 'nickname', 'online', 'photo_50', 'photo_100', 'photo_200_orig',
                    'photo_200', 'photo_400_orig', 'photo_id', 'photo_max', 'photo_max_orig', 'relation',
                    'screen_name', 'sex', 'status', 'timezone', 'trending', 'verified', 'wall_default'
                ]
            ]);

        return $users[0];
    }
}
