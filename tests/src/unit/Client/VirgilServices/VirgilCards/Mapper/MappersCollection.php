<?php
namespace Virgil\Sdk\Tests\Unit\Client\VirgilServices\VirgilCards\Mapper;


use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\CardContentModelMapper;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\ErrorResponseModelMapper;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\ModelMappersCollection;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\SearchRequestModelMapper;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\SignedResponseModelsMapper;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\SignedRequestModelMapper;
use Virgil\Sdk\Client\VirgilServices\VirgilCards\Mapper\SignedResponseModelMapper;

class MappersCollection
{
    public static function getMappers()
    {
        $signedResponseModelMapper = new SignedResponseModelMapper(new CardContentModelMapper());

        return new ModelMappersCollection(
            $signedResponseModelMapper,
            new SignedRequestModelMapper(),
            new SignedResponseModelsMapper($signedResponseModelMapper),
            new SearchRequestModelMapper(),
            new ErrorResponseModelMapper()
        );
    }
}
