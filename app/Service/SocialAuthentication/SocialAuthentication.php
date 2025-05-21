<?php

/**
 * Abstract Class SocialAuthentication
 * @package App\Service\SocialAuthentication
 * @author Üveys SERVETOĞLU <uveysservetoglu@gmail.com>
 */

namespace App\Service\SocialAuthentication;

use GuzzleHttp\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

abstract class SocialAuthentication
{
    protected $accessToken;

    protected $email;

    protected $firstName;

    protected $lastName;

    protected $container;

    protected $client;

    abstract public function verify();

    public function __construct()
    {
        $this->client = new Client();
    }

    public function convertToObject($data)
    {
        foreach ($data as $key => $value) {
            $set = 'set'.ucwords($key);

            if (method_exists($this, $set)) {
                $this->{$set}($value);
            }
        }


        return $this;
    }
}
