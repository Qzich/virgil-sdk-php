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

namespace Virgil\Sdk;


use DateTime;
use Virgil\CryptoApi\CardCrypto;
use Virgil\Sdk\Signer\ModelSigner;

use Virgil\Sdk\Verification\CardVerificationException;
use Virgil\Sdk\Verification\CardVerifier;

use Virgil\Sdk\Web\Authorization\AccessToken;
use Virgil\Sdk\Web\Authorization\AccessTokenProvider;
use Virgil\Sdk\Web\Authorization\TokenContext;
use Virgil\Sdk\Web\ErrorResponseModel;
use Virgil\Sdk\Web\CardClient;
use Virgil\Sdk\Web\RawCardContent;
use Virgil\Sdk\Web\RawSignedModel;


/**
 * Class CardManager
 * @package Virgil\Sdk
 */
class CardManager
{
    /**
     * @var callable|null
     */
    private $signCallback;
    /**
     * @var ModelSigner
     */
    private $modelSigner;
    /**
     * @var CardCrypto
     */
    private $cardCrypto;
    /**
     * @var AccessTokenProvider
     */
    private $accessTokenProvider;
    /**
     * @var CardVerifier
     */
    private $cardVerifier;
    /**
     * @var CardClient
     */
    private $cardClient;


    public function __construct(
        ModelSigner $modelSigner,
        CardCrypto $cardCrypto,
        AccessTokenProvider $accessTokenProvider,
        CardVerifier $cardVerifier,
        CardClient $cardClient,
        callable $signCallback = null
    ) {
        $this->signCallback = $signCallback;
        $this->modelSigner = $modelSigner;
        $this->cardCrypto = $cardCrypto;
        $this->accessTokenProvider = $accessTokenProvider;
        $this->cardVerifier = $cardVerifier;
        $this->cardClient = $cardClient;
    }


    /**
     * @param CardParams $cardParams
     *
     * @return RawSignedModel
     */
    public function generateRawCard(CardParams $cardParams)
    {
        $now = new DateTime();
        $publicKeyString = $this->cardCrypto->exportPublicKey($cardParams->getPublicKey());

        $rawCardContent = new RawCardContent($cardParams->getIdentity(), $publicKeyString, '5.0', $now->getTimestamp());
        $rawCardContentSnapshot = json_encode($rawCardContent);

        $rawSignedModel = new RawSignedModel($rawCardContentSnapshot, []);

        try {
            $this->modelSigner->selfSign($rawSignedModel, $cardParams->getPrivateKey(), $cardParams->getExtraFields());
        } catch (VirgilException $e) {
            //model with empty signatures hasn't this exception
        }

        return $rawSignedModel;
    }


    /**
     * @param RawSignedModel $rawSignedModel
     *
     * @return Card
     *
     * @throws CardVerificationException
     * @throws CardClientException
     */
    public function publishRawSignedModel(RawSignedModel $rawSignedModel)
    {
        $contentSnapshotString = base64_decode($rawSignedModel->getContentSnapshot());
        $contentSnapshot = json_decode($contentSnapshotString, true);

        $tokenContext = new TokenContext($contentSnapshot['identity'], 'publish');
        $token = $this->accessTokenProvider->getToken($tokenContext);

        $card = $this->publishRawSignedModelWithToken($rawSignedModel, $token);

        if (!$this->cardVerifier->verifyCard($card)) {
            throw new CardVerificationException('Validation errors have been detected');
        }

        return $card;
    }


    /**
     * @param CardParams $cardParams
     *
     * @return Card
     *
     * @throws CardVerificationException
     * @throws CardClientException
     */
    public function publishCard(CardParams $cardParams)
    {
        $tokenContext = new TokenContext($cardParams->getIdentity(), 'publish');
        $token = $this->accessTokenProvider->getToken($tokenContext);

        $rawSignedModel = $this->generateRawCard(
            CardParams::create(
                [
                    CardParams::Identity       => $token->identity(),
                    CardParams::PrivateKey     => $cardParams->getPrivateKey(),
                    CardParams::PublicKey      => $cardParams->getPublicKey(),
                    CardParams::ExtraFields    => $cardParams->getExtraFields(),
                    CardParams::PreviousCardID => $cardParams->getPreviousCardID(),
                ]
            )
        );

        $card = $this->publishRawSignedModelWithToken($rawSignedModel, $token);

        if (!$this->cardVerifier->verifyCard($card)) {
            throw new CardVerificationException('Validation errors have been detected');
        }

        return $card;
    }
    //
    //
    ///**
    // * @param $cardID
    // *
    // * @return Card
    // */
    //public function getCard($cardID)
    //{
    //    return new Card();
    //}
    //
    //
    ///**
    // * @param string $identity
    // *
    // * @return Card[]
    // */
    //public function searchCards($identity)
    //{
    //    return [new Card()];
    //}
    //
    //
    ///**
    // * @param string $stringCard
    // *
    // * @return Card
    // */
    //public function importCardFromString($stringCard)
    //{
    //    return new Card();
    //}
    //
    //
    ///**
    // * @param string $jsonCard
    // *
    // * @return Card
    // */
    //public function importCardFromJson($jsonCard)
    //{
    //    return new Card();
    //}
    //
    //
    ///**
    // * @param RawSignedModel $rawSignedModel
    // *
    // * @return Card
    // */
    //public function importCard(RawSignedModel $rawSignedModel)
    //{
    //    return new Card();
    //}
    //
    //
    ///**
    // * @param Card $card
    // *
    // * @return string
    // */
    //public function exportCardAsString(Card $card)
    //{
    //    return "";
    //}
    //
    //
    ///**
    // * @param Card $card
    // *
    // * @return string
    // */
    //public function exportCardAsJson(Card $card)
    //{
    //    return "";
    //}
    //
    //
    ///**
    // * @param Card $card
    // *
    // * @return RawSignedModel
    // */
    //public function exportCardAsRawCard(Card $card)
    //{
    //    return new RawSignedModel();
    //}

    /**
     * @param RawSignedModel $model
     * @param AccessToken    $token
     *
     * @return Card
     *
     * @throws CardClientException
     */
    protected function publishRawSignedModelWithToken(RawSignedModel $model, AccessToken $token)
    {
        if (is_callable($this->signCallback)) {
            $model = call_user_func($this->signCallback, $model);
        }

        $responseModel = $this->cardClient->publishCard($model, (string)$token);

        if ($responseModel instanceof ErrorResponseModel) {
            throw new CardClientException("error response from card service", $responseModel);
        }

        $contentSnapshotString = base64_decode($responseModel->getContentSnapshot());
        $contentSnapshot = json_decode($contentSnapshotString, true);

        $cardSignatures = [];
        foreach ($responseModel->getSignatures() as $signature) {
            $extraFields = null;
            if ($signature->getSnapshot() != "") {
                $snapshotString = base64_decode($signature->getSnapshot());
                $extraFields = json_decode($snapshotString, true);

            }

            $cardSignatures[] = new CardSignature(
                $signature->getSigner(), $signature->getSignature(), $signature->getSnapshot(), $extraFields
            );
        }

        $publicKey = $this->cardCrypto->importPublicKey($contentSnapshot['public_key']);

        $previousCardID = null;
        if (array_key_exists('previous_card_id', $contentSnapshot)) {
            $previousCardID = $contentSnapshot['previous_card_id'];
        }

        return new Card(
            $this->generateCardID($this->cardCrypto, $model->getContentSnapshot()),
            $contentSnapshot['identity'],
            $publicKey,
            $contentSnapshot['version'],
            new DateTime($contentSnapshot['created_at']),
            false,
            $cardSignatures,
            $model->getContentSnapshot(),
            $previousCardID
        );
    }


    /**
     * @param CardCrypto $cardCrypto
     * @param string     $snapshot
     *
     * @return string
     */
    protected function generateCardID(CardCrypto $cardCrypto, $snapshot)
    {
        return bin2hex(substr($cardCrypto->generateSHA512($snapshot), 0, 32));
    }
}
