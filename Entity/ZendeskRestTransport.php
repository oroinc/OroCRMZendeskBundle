<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Zendesk Rest Transport entity
 */
#[ORM\Entity]
#[Config]
class ZendeskRestTransport extends Transport
{
    #[ORM\Column(name: 'orocrm_zd_url', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $url = null;

    #[ORM\Column(name: 'orocrm_zd_email', type: Types::STRING, length: 100, nullable: false)]
    protected ?string $email = null;

    #[ORM\Column(name: 'orocrm_zd_token', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $token = null;

    #[ORM\Column(name: 'orocrm_zd_default_user_email', type: Types::STRING, length: 100, nullable: false)]
    protected ?string $zendeskUserEmail = null;

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

    #[\Override]
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                array(
                    'email'            => $this->getEmail(),
                    'url'              => $this->getUrl(),
                    'token'            => $this->getToken(),
                    'zendeskUserEmail' => $this->getZendeskUserEmail()
                )
            );
        }

        return $this->settings;
    }
}
