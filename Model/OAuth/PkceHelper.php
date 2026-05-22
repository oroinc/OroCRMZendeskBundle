<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Model\OAuth;

/**
 * PKCE (Proof Key for Code Exchange) helper utilities for OAuth 2.0.
 */
final class PkceHelper
{
    private const LENGTH_IN_BYTES = 96; // 128 characters

    /**
     * Generate cryptographically secure random code verifier.
     */
    public static function generateCodeVerifier(): string
    {
        $bytes = random_bytes(self::LENGTH_IN_BYTES);

        return self::base64UrlEncode($bytes);
    }

    /**
     * Generate code challenge from verifier using SHA256 and base64url encoding.
     */
    public static function generateCodeChallenge(string $verifier): string
    {
        $hash = hash('sha256', $verifier, true);

        return self::base64UrlEncode($hash);
    }

    /**
     * Base64 URL-safe encoding (RFC 4648).
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
