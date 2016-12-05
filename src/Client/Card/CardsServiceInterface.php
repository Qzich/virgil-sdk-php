<?php
namespace Virgil\Sdk\Client\Card;


use Virgil\Sdk\Client\Card\Model\SearchCriteria;
use Virgil\Sdk\Client\Card\Model\SignedRequestModel;
use Virgil\Sdk\Client\Card\Model\SignedResponseModel;

interface CardsServiceInterface
{
    /**
     * Create card by given request model.
     *
     * @param SignedRequestModel $model
     * @return SignedResponseModel
     */
    public function create(SignedRequestModel $model);

    /**
     * Delete card by given request model.
     *
     * @param SignedRequestModel $model
     */
    public function delete(SignedRequestModel $model);

    /**
     * Search cards by given search criteria.
     *
     * @param SearchCriteria $model
     * @return SignedResponseModel[]
     */
    public function search(SearchCriteria $model);

    /**
     * Retrieve card by given id.
     *
     * @param string $id
     * @return SignedResponseModel
     */
    public function get($id);
}
