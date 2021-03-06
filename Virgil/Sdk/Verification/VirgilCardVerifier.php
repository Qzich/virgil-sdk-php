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

namespace Virgil\Sdk\Verification;


use Virgil\CryptoApi\CardCrypto;
use Virgil\CryptoApi\PublicKey;

use Virgil\Sdk\Card;

/**
 * Class VirgilCardVerifier
 * @package Virgil\Sdk\Verification
 */
class VirgilCardVerifier implements CardVerifier
{
    const VirgilPublicKey = "MCowBQYDK2VwAyEAljOYGANYiVq1WbvVvoYIKtvZi2ji9bAhxyu6iV/LF8M=";

    /**
     * @var CardCrypto
     */
    private $cardCrypto;
    /**
     * @var bool
     */
    private $verifySelfSignature;
    /**
     * @var bool
     */
    private $verifyVirgilSignature;
    /**
     * @var Whitelist[]
     */
    private $whiteLists;

    /**
     * @var PublicKey
     */
    private $virgilPublicKey;


    /**
     * VirgilCardVerifier constructor.
     *
     * @param CardCrypto  $cardCrypto
     * @param bool        $verifySelfSignature
     * @param bool        $verifyVirgilSignature
     * @param Whitelist[] $whiteLists
     * @param string      $virgilPublicKey
     */
    public function __construct(
        CardCrypto $cardCrypto,
        $verifySelfSignature = true,
        $verifyVirgilSignature = true,
        array $whiteLists = [],
        $virgilPublicKey = self::VirgilPublicKey
    ) {
        $this->cardCrypto = $cardCrypto;
        $this->verifySelfSignature = $verifySelfSignature;
        $this->verifyVirgilSignature = $verifyVirgilSignature;
        $this->whiteLists = $whiteLists;

        if ($verifyVirgilSignature) {
            $this->virgilPublicKey = $cardCrypto->importPublicKey(base64_decode($virgilPublicKey));
        }
    }


    /**
     * @param Card $card
     *
     * @return bool
     */
    public function verifyCard(Card $card)
    {
        if ($this->verifySelfSignature) {
            if (!$this->validateSignerSignature($card, 'self', $card->getPublicKey())) {
                return false;
            }
        }

        if ($this->verifyVirgilSignature) {
            if (!$this->validateSignerSignature($card, 'virgil', $this->virgilPublicKey)) {
                return false;
            }
        }

        foreach ($this->whiteLists as $whiteList) {
            $isOk = false;
            foreach ($whiteList->getCredentials() as $credentials) {
                if ($this->validateSignerSignature($card, $credentials->getSigner(), $credentials->getPublicKey())) {
                    $isOk = true;
                    break;
                }
            }

            if (!$isOk) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param Card      $card
     * @param string    $signer
     * @param PublicKey $publicKey
     *
     * @return bool
     */
    private function validateSignerSignature(Card $card, $signer, PublicKey $publicKey)
    {
        foreach ($card->getSignatures() as $cardSignature) {
            if ($cardSignature->getSigner() == $signer) {
                $snapshot = $card->getContentSnapshot();
                if ($cardSignature->getSnapshot() != "") {
                    $snapshot .= $cardSignature->getSnapshot();
                }

                return $this->cardCrypto->verifySignature($cardSignature->getSignature(), $snapshot, $publicKey);
            }
        }

        return false;
    }
}
