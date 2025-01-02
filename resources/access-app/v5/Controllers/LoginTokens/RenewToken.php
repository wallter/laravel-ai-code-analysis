<?php

namespace v5\Controller\LoginTokens;

use v5\AccessControl;
use v5\Connector;
use v5\SecurityGroup;
use v5\Service;

trait RenewToken
{
    /**
     * Renews a login token
     *
     * @url PATCH {token}
     *
     * @access protected
     * @class AccessControl {@requires guest}
     *
     * @param string $token Login token {@from url} {@required true}
     * @param integer $seconds The number of seconds to renew the token for {@from query} {@required false}
     *
     * @status 201
     * @return array
     */
    public static function renewToken($token, $seconds = -1)
    {
        AccessControl::verifyPermissions(
            SecurityGroup\LoginTokens::RENEW_TOKEN
        );

        if (preg_match(isUUIDv4Token, $token)) {
            $MaximumInterval = intval(Service\Connection::getProperties()->getValue('logintoken.expires_days')) * 86400;

            $IntervalSeconds = ($seconds > -1) ? min($seconds, $MaximumInterval) : $MaximumInterval;

            Connector\DB\SQL::exec('unity')
                ->procedure('dbo.hydraRenewLoginToken')
                ->argument('Token', $token)
                ->argument('IntervalSeconds', $IntervalSeconds)
                ->actual_user()
                ->x_request_id()
                ->query();
        }

        return [];
    }
}
