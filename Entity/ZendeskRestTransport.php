<?php

namespace Oro\Bundle\ZendeskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class ZendeskRestTransport
 *
 * @ORM\Entity
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

    /**
     * {@inheritdoc}
     */
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
