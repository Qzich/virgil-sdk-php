<?php
/**
 * Copyright (C) 2015-2018 Virgil Security Inc.
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3) Neither the name of the copyright holder nor the names of its
 *     contributors may be used to endorse or promote products derived from
 *     this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ''AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Lead Maintainer: Virgil Security Inc. <support@virgilsecurity.com>
 */

namespace Virgil\Sdk\Web\Authorization;


use Virgil\CryptoApi\AccessTokenSigner;
use Virgil\CryptoApi\PrivateKey;


/**
 * Class JwtGenerator
 * @package Virgil\Sdk\Web\Authorization
 */
class JwtGenerator
{
    /**
     * @var PrivateKey
     */
    private $apiKey;
    /**
     * @var string
     */
    private $apiPublicKeyIdentifier;
    /**
     * @var AccessTokenSigner
     */
    private $accessTokenSigner;
    /**
     * @var string
     */
    private $appID;
    /**
     * @var int
     */
    private $ttl;


    /**
     * JwtGenerator constructor.
     *
     * @param PrivateKey        $apiKey
     * @param string            $apiPublicKeyIdentifier
     * @param AccessTokenSigner $accessTokenSigner
     * @param string            $appID
     * @param int               $ttl
     */
    public function __construct(
        PrivateKey $apiKey,
        $apiPublicKeyIdentifier,
        AccessTokenSigner $accessTokenSigner,
        $appID,
        $ttl
    ) {
        $this->apiKey = $apiKey;
        $this->apiPublicKeyIdentifier = $apiPublicKeyIdentifier;
        $this->accessTokenSigner = $accessTokenSigner;
        $this->appID = $appID;
        $this->ttl = $ttl;
    }


    /**
     * @param string $identity
     * @param array  $additionalData
     *
     * @return Jwt
     */
    public function generateToken($identity, array $additionalData = null)
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + $this->ttl;
        $jwtBody = new JwtBodyContent($this->appID, $identity, $issuedAt, $expiresAt, $additionalData);
        $jwtHeader = new JwtHeaderContent($this->accessTokenSigner->getAlgorithm(), $this->apiPublicKeyIdentifier);

        $unsignedJwt = new Jwt($jwtHeader, $jwtBody, '');

        $jwtSignature = $this->accessTokenSigner->generateTokenSignature($unsignedJwt->getUnsigned(), $this->apiKey);

        return new Jwt($jwtHeader, $jwtBody, $jwtSignature);
    }
}
