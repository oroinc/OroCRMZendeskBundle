<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Zendesk Rest Transport entity
 */
#[ORM\Entity(repositoryClass: ZendeskRestTransportRepository::class)]
#[Config]
class ZendeskRestTransport extends Transport
{
    #[ORM\Column(name: 'orocrm_zd_url', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $url = null;

    #[ORM\Column(name: 'orocrm_zd_email', type: Types::STRING, length: 100, nullable: false)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $email = null;

    #[ORM\Column(name: 'orocrm_zd_token', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $token = null;

    #[ORM\Column(name: 'orocrm_zd_default_user_email', type: Types::STRING, length: 100, nullable: false)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $zendeskUserEmail = null;

    #[ORM\Column(
        name: 'orocrm_zd_authorization_type',
        type: Types::STRING,
        length: 32,
        nullable: true,
        enumType: AuthorizationType::class
    )]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?AuthorizationType $authorizationType = AuthorizationType::DEFAULT;

    #[ORM\Column(name: 'orocrm_zd_oauth_client_id', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $oauthClientId = null;

    #[ORM\Column(name: 'orocrm_zd_access_token', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $accessToken = null;

    #[ORM\Column(name: 'orocrm_zd_refresh_token', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?string $refreshToken = null;

    #[ORM\Column(name: 'orocrm_zd_oauth_last_refresh_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => false, 'immutable' => true]])]
    protected ?\DateTimeInterface $oauthLastRefreshAt = null;

    /**
     * @var ParameterBag
     */
    private $settings;

    /**
     * @param string $email
     * @return ZendeskRestTransport
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $token
     * @return ZendeskRestTransport
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $url
     * @return ZendeskRestTransport
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $zendeskUserEmail
     * @return ZendeskRestTransport
     */
    public function setZendeskUserEmail($zendeskUserEmail)
    {
        $this->zendeskUserEmail = $zendeskUserEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getZendeskUserEmail()
    {
        return $this->zendeskUserEmail;
    }

    public function getAuthorizationType(): ?AuthorizationType
    {
        return $this->authorizationType;
    }

    public function setAuthorizationType(?AuthorizationType $authorizationType): self
    {
        $this->authorizationType = $authorizationType ?? AuthorizationType::DEFAULT;

        return $this;
    }

    public function getOauthClientId(): ?string
    {
        return $this->oauthClientId;
    }

    public function setOauthClientId(?string $oauthClientId): self
    {
        $this->oauthClientId = $oauthClientId;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getOauthLastRefreshAt(): ?\DateTimeInterface
    {
        return $this->oauthLastRefreshAt;
    }

    public function setOauthLastRefreshAt(?\DateTimeInterface $oauthLastRefreshAt): self
    {
        $this->oauthLastRefreshAt = $oauthLastRefreshAt;

        return $this;
    }

    /**
     * Clear locally cached settings bag to reflect updated properties.
     */
    public function clearSettings(): void
    {
        $this->settings = null;
    }

    #[\Override]
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([
                'email' => $this->getEmail(),
                'url' => $this->getUrl(),
                'token' => $this->getToken(),
                'zendeskUserEmail' => $this->getZendeskUserEmail(),
                'authorizationType' => ($this->getAuthorizationType() ?: AuthorizationType::DEFAULT)->value,
                'oauthClientId' => $this->getOauthClientId(),
                'refreshToken' => $this->getRefreshToken(),
                'accessToken' => $this->getAccessToken(),
                'oauthLastRefreshAt' => $this->getOauthLastRefreshAt(),
            ]);
        }

        return $this->settings;
    }
}
