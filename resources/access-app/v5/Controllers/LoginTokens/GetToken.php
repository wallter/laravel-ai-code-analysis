<?php

namespace v5\Controller\LoginTokens;

use v5\AccessControl;
use v5\Throwable;
use v5\Utilities;

trait GetToken
{
    /**
     * Get details about a given bearer token (for developer purposes only)
     *
     * @url GET {token}
     *
     * @access protected
     * @class AccessControl {@requires guest}
     *
     * @param string $token Unicity bearer token {@from url} {@from body} {@required true}
     * @param string $env Custom environment {@from query} {@required false} {@choice dev,prod,qa,stg,test}
     *
     * @status 200
     * @return object
     */
    public function getToken(string $token, ?string $env = null)
    {
        if (!Utilities::isDevelopmentEnvironment()) {
            throw new Throwable\UnauthorizedException();
        }

        $headerAuthorization = "Bearer {$token}";
        if (!is_string($env)) {
            $environments = Utilities::isLiveMode() ? ['dev', 'prod'] : ['dev', 'qa', 'stg', 'test'];
        } else {
            $environments = [$env];
        }

        foreach ($environments as $environment) {
            $token = AccessControl::getLoginToken($headerAuthorization, $environment);
            if ($token->status === 200) {
                return $token;
            }
        }

        throw new Throwable\InvalidLoginTokenException();
    }
}
