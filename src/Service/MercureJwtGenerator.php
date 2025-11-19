<?php

namespace App\Service;

use App\Entity\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class MercureJwtGenerator
{

    private string $mercureSecret;

    public function __construct(string $mercureSecret)
    {
        $this->mercureSecret = $mercureSecret;
    }

    public function generate(User $user)
    {
        if(!$user) {
            throw new \Exception('User not found');
        }

        $allowedTopics=[];
        foreach ($user->getConversationsAsUserA() as $conversation) {
            $allowedTopics[] = '/conversations/'.$conversation->getId();
        }
        foreach ($user->getConversationsAsUserB() as $conversation) {
            $allowedTopics[] = '/conversations/'.$conversation->getId();
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->mercureSecret)
        );

        $tokenBuilder = $config->builder();
        $token = $tokenBuilder->withClaim('mercure',
            ['publish' => $allowedTopics, 'subscribe' => $allowedTopics])
            ->issuedAt((new \DateTimeImmutable()))
            ->expiresAt((new \DateTimeImmutable())->modify('+1 hour'))
            ->getToken(new Sha256(), InMemory::plainText($this->mercureSecret));

        return $token->toString();

    }

}
