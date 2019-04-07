<?php
declare(strict_types=1);

namespace PTS\VkAuthMiddleware;

class VkToken
{
    /** @var string */
    protected $access_token = '';
    /** @var int sec */
    protected $expires_in = 0;
    /** @var int */
    protected $user_id = 0;
    /** @var string */
    protected $email = '';

    /** @var string|null */
    protected $error;
    /** @var string|null */
    protected $error_description;

    public function __construct(array $data)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
    }

    public function getException(): VkException
    {
        return new VkException(sprintf(
            'Error: %s. Description: %s',
            $this->error ?? '-',
            $this->error_description ?? '-'
        ));
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expires_in;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
