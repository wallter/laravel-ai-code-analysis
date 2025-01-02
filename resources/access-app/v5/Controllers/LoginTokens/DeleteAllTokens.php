<?php

namespace v5\Controller\LoginTokens;

use v5\AccessControl;
use v5\Helper\LoginToken;
use v5\Link\CustomerLink;
use v5\SecurityGroup;

trait DeleteAllTokens
{
    /**
     * Deletes all login tokens for a user
     *
     * @url DELETE
     *
     * @access protected
     * @class AccessControl {@requires guest}
     *
     * @param int|string $id.unicity Unicity distributor ID of the customer {@from query} {@required true}
     *
     * @status 204
     * @return array
     */
    public static function deleteAllTokens($id_unicity)
    {
        $customerLink = CustomerLink::getInstanceByUnicity($id_unicity);

        AccessControl::verifyPermissions(
            $customerLink,
            SecurityGroup\Customers::GET
        );

        LoginToken::deleteAllTokensForUser($customerLink);

        return [];
    }
}
