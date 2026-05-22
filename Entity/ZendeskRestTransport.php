<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ZendeskBundle\Enum\Transport\AuthorizationType;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Zendesk Rest Transport entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository")
 * @Config()
 */
class ZendeskRestTransport extends Transport
{
    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_zd_url", type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_zd_email", type="string", length=100, nullable=false)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_zd_token", type="string", length=255, nullable=false)
     */
    protected $token;

    /**
     * @var string
     *
     * @ORM\Column(name="orocrm_zd_default_user_email", type="string", length=100, nullable=false)
     */
    protected $zendeskUserEmail;

    /**
     * @ORM\Column(
     *     name="orocrm_zd_authorization_type",
     *     type="string",
     *     length=32,
     *     nullable=true,
     *     enumType=AuthorizationType::class
     * )
     */
    protected ?AuthorizationType $authorizationType = AuthorizationType::DEFAULT;

    /**
     * @ORM\Column(
     *     name="orocrm_zd_oauth_client_id",
     *     type="string",
     *     length=255,
     *     nullable=true
     * )
     */
    protected ?string $oauthClientId = null;

    /**
     * @ORM\Column(name="orocrm_zd_access_token", type="string", length=255, nullable=false)
     */
    protected ?string $accessToken = null;

    /**
     * @ORM\Column(name="orocrm_zd_refresh_token", type="string", length=255, nullable=false)
     */
    protected ?string $refreshToken = null;

    /**
     * @ORM\Column(name="orocrm_zd_oauth_last_refresh_at", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $oauthLastRefreshAt = null;

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

    public function getOauthLastRefreshAt(): ?DateTimeInterface
    {
        return $this->oauthLastRefreshAt;
    }

    public function setOauthLastRefreshAt(?DateTimeInterface $oauthLastRefreshAt): self
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
