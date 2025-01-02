<?php

namespace v5\Controller\LoginTokens;

use v5\Connector;

trait DeleteToken
{
    /**
     * Deletes a login token
     *
     * @url DELETE {token}
     *
     * @status 204
     * @return array
     */
    public static function deleteToken($token)
    {
        if (preg_match(isUUIDv4Token, $token)) {
            Connector\DB\SQL::exec('unity')
                ->procedure('dbo.hydraDeleteLoginToken')
                ->argument('Token', $token)
                ->actual_user()
                ->x_request_id()
                ->query();
        }

        return [];
    }
}
