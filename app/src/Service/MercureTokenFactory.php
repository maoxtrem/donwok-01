<?php

namespace App\Service;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\RegisteredClaims;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

final class MercureTokenFactory implements TokenFactoryInterface
{
    private readonly Configuration $configuration;

    public function __construct(string $secret, private readonly string $issuer, private readonly string $audience)
    {
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($secret));
    }

    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string
    {
        $now = new DateTimeImmutable();
        $additionalClaims[RegisteredClaims::ISSUER] ??= $this->issuer;
        $additionalClaims[RegisteredClaims::AUDIENCE] ??= [$this->audience];
        $additionalClaims[RegisteredClaims::EXPIRATION_TIME] ??= $now->modify('+1 hour');
        $additionalClaims[RegisteredClaims::ISSUED_AT] ??= $now;
        $additionalClaims[RegisteredClaims::SUBJECT] ??= 'donwok-app';
        $additionalClaims['client_id'] ??= 'donwok-app';
        $additionalClaims[RegisteredClaims::ID] ??= bin2hex(random_bytes(16));

        $actions = [];
        $topics = [];
        if ($subscribe !== null && $subscribe !== []) {
            $actions[] = 'subscribe';
            $topics = array_merge($topics, $subscribe);
        }
        if ($publish !== null && $publish !== []) {
            $actions[] = 'publish';
            $topics = array_merge($topics, $publish);
        }
        if ($actions !== []) {
            $additionalClaims['authorization_details'] ??= [[
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => array_values(array_unique($actions)),
                'topics' => array_map(static fn (string $topic): array => ['match' => $topic], array_values(array_unique($topics))),
            ]];
        }

        $builder = $this->configuration->builder()->withHeader('typ', 'at+jwt');
        foreach ($additionalClaims as $name => $value) {
            $builder = match ($name) {
                RegisteredClaims::ISSUER => $builder->issuedBy((string) $value),
                RegisteredClaims::AUDIENCE => $builder->permittedFor(...(array) $value),
                RegisteredClaims::EXPIRATION_TIME => $builder->expiresAt($value),
                RegisteredClaims::ISSUED_AT => $builder->issuedAt($value),
                RegisteredClaims::NOT_BEFORE => $builder->canOnlyBeUsedAfter($value),
                RegisteredClaims::SUBJECT => $builder->relatedTo((string) $value),
                RegisteredClaims::ID => $builder->identifiedBy((string) $value),
                default => $builder->withClaim($name, $value),
            };
        }

        return $builder->getToken($this->configuration->signer(), $this->configuration->signingKey())->toString();
    }
}
