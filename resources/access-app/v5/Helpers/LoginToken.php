<?php

namespace v5\Helper;

use Exception;
use Firebase\JWT\JWT;
use Leap\Core\DB\SQL;
use v5\AccessControl;
use v5\Connector;
use v5\Controller\WhoAmIController;
use v5\Finder;
use v5\Helper;
use v5\Link\CustomerLink;
use v5\Link\DscEmployeeLink;
use v5\SecurityGroup;
use v5\Service;
use v5\Throwable;
use v5\Utilities;

class LoginToken
{
    private static function createByEmployee($employee)
    {
        $token = uuid_create();

        $tokenData = [
            'employee' => $employee,
            'environment' => Utilities::getEnvironment(),
            'ip' => Utilities::getIPAddress(),
            'mode' => Utilities::getMode(),
            'permissions' => Connector\UnityLogin::getUserPermissions($employee['username']),
            'userAgent' => Utilities::getUserAgent(),
        ];

        $IntervalSeconds = intval(Service\Connection::getProperties()->getValue('logintoken.expires_days')) * 86400;

        Connector\DB\SQL::exec('unity')
            ->procedure('dbo.hydraInsertLoginToken')
            ->argument('IntervalSeconds', $IntervalSeconds)
            ->argument('Token', $token)
            ->argument('TokenData', json_encode($tokenData, JSON_FORCE_OBJECT))
            ->actual_user()
            ->x_request_id()
            ->query();

        return $token;
    }

    public static function createByUnicity($unicity, $expirationHours = 0)
    {
        if ((int)$unicity < 1000) {
            throw new Throwable\UnauthorizedException();
        }
        Service\Logger::getInstance()->info('Helper\LoginToken::createByUnicity entered', []);

        // IMPORTANT: we don't use user-provided namespace here, otherwise
        // user would be able to obtain live-environment token using
        // credentials from test-environment
        $customerLink = CustomerLink::getInstanceByUnicity($unicity);

        $token = uuid_create();

        $tokenData = [
            'CustomerId_Unicity' => $customerLink->getUnicity(),
            'customer' => $customerLink->href,
            'environment' => Utilities::getEnvironment(),
            'ip' => Utilities::getIPAddress(),
            'mode' => Utilities::getMode(),
            'userAgent' => Utilities::getUserAgent(),
        ];

        $IntervalSeconds = ($expirationHours != 0)
            ? $expirationHours * 3600
            : intval(Service\Connection::getProperties()->getValue('logintoken.expires_days')) * 86400;

        Connector\DB\SQL::exec('unity')
            ->procedure('dbo.hydraInsertLoginToken')
            ->argument('IntervalSeconds', $IntervalSeconds)
            ->argument('Token', $token)
            ->argument('TokenData', json_encode($tokenData, JSON_FORCE_OBJECT))
            ->actual_user()
            ->x_request_id()
            ->query();

        return $token;
    }

    public static function customerLogin($type, $value, $expand = '')
    {
        $produceLoginResult = function ($unicityId) use ($expand) {
            $token = static::createByUnicity($unicityId);
            $customerLink = CustomerLink::getInstanceByUnicity($unicityId);

            // switching SECURITY context
            AccessControl::processAuthorizationToken("Bearer {$token}");

            $result = [
                'customer' => $customerLink,
                'expired' => AccessControl::getExpired(),
                'href' => Utilities::getBaseHref() . '/loginTokens/' . $token,
                'token' => $token,
            ];

            $expand = static::processExpand($expand);
            if (in_array('whoami', $expand)) {
                $result['whoami'] = WhoAmIController::getLoginDetails();
            } else {
                $result['whoami'] = [
                    'href' => Utilities::getBaseHref() . '/whoami',
                ];
            }

            return $result;
        };

        if ($type === 'unicity') {
            AccessControl::verifyPermissions(
                SecurityGroup\LoginTokens::CREATE_BY_UNICITY
            );

            if (!is_numeric($value)) {
                throw new Throwable\CustomerNotFoundException();
            }

            $token = static::createByUnicity($value);

            $result = [
                'expired' => AccessControl::getExpired(),
                'href' => Utilities::getBaseHref() . '/loginTokens/' . $token,
                'token' => $token,
            ];

            return $result;
        }

        if ($type === 'loginToken') {
            // switching SECURITY context
            AccessControl::processAuthorizationToken("Bearer {$value}");

            if (!AccessControl::getCustomerLink()) {
                throw new Throwable\UnauthorizedException();
            }

            $token = static::createByUnicity(AccessControl::getCustomerLink()->getUnicity());

            $result = [
                'expired' => AccessControl::getExpired(),
                'href' => Utilities::getBaseHref() . '/loginTokens/' . $token,
                'token' => $token,
            ];

            return $result;
        }

        if (in_array($type, ['apple', 'facebook', 'google'])) {
            list($clientId, $accessToken) = explode(':', $value);

            try {
                $userId = Helper\Helper::getLoginAssociationUserId($type, $clientId, $accessToken);

                $unicityId = Connector\UnityDB::getLoginAssociationCustomerId([
                    'entry' => [
                        'type' => $type,
                        'clientId' => $clientId,
                        'userId' => $userId,
                    ],
                ]);
            } catch (\Throwable $e) {
                throw new Throwable\UnauthorizedException();
            }

            if (!isset($unicityId)) {
                throw new Throwable\UnauthorizedException();
            }

            return $produceLoginResult($unicityId);
        }

        if ($type === 'base64') {
            $value = base64_decode($value);
        }

        list($username, $password) = explode(':', $value, 2);
        if (empty($username) || empty($password)) {
            throw new Throwable\UnauthorizedException();
        }

        $cacheClient = Utilities::getRedisCache();
        $cacheKey = Utilities::createCacheKey([$username], 'LoginTokens::customerLogin');
        $cacheVal = $cacheClient->get($cacheKey);

        /** @psalm-suppress PossiblyNullArgument */
        if (Utilities::doResetCache($cacheVal) || !Helper\Passwords::match(json_decode($cacheVal)->password_hash, $password)) {
            list($distributorId, $passwordHash) = static::newLogin($username, $password);
            $cacheClient->setex($cacheKey, 86400, json_encode([
                'user_id' => $distributorId,
                'password_hash' => $passwordHash,
            ]));
        } else {
            $distributorId = json_decode($cacheVal)->user_id;
        }

        return $produceLoginResult($distributorId);
    }

    private static function datatraxLogin($distributorId, $actual_password)
    {
        Service\Logger::getInstance()->info('Helper\LoginToken::datatraxLogin entered', [
            'username' => $distributorId,
        ]);

        try {
            $market = Service\DistributorMarketLocator::getMarketByCustomerId($distributorId);
            if (Service\BusinessRule::about('markets_in_unity')->matches($market)) {
                throw new Throwable\UnauthorizedException();
            }
        } catch (Exception $ex) {
            return false;
        }

        $results = SQL::select(Utilities::getInfoTraxDatabaseId($market))
            ->column('DSTDB.DIST_ID', 'DIST_ID')
            ->column('DSTDB.DIST_STATUS', 'DIST_STATUS')
            ->column('NUPDB.PASS_WORD', 'PASSWORD')
            ->from('DSTDB')
            ->join('LEFT', 'NUPDB')->on('NUPDB.DIST_ID', '=', 'DSTDB.DIST_ID')
            ->where('DSTDB.DIST_ID', '=', strval($distributorId))
            ->limit(1)
            ->log(true)
            ->query();

        if ($results->is_loaded()) {
            $expected_password = trim(substr(strval($results->get('PASSWORD', '')), 0, 20));
            $actual_password = trim(substr(strval($actual_password), 0, 20));
            if (!empty($expected_password) && ($expected_password === $actual_password)) {
                $customer_status = $results->get('DIST_STATUS');
                if (!in_array($customer_status, ['N', 'T'])) {
                    if (in_array($customer_status, [$retired = 'R'])) {
                        throw new Throwable\AccountLockedException();
                    }
                    if (in_array($customer_status, [$interrupted = 'I', $suspended = 'S'])) {
                        throw new Throwable\AccountSuspendedException();
                    }

                    return true;
                }
            }
        }

        return false;
    }

    public static function deleteAllTokensForUser(CustomerLink $customerLink): void
    {
        Connector\DB\SQL::exec('unity')
            ->procedure('dbo.hydraDeleteLoginToken')
            ->argument('CustomerId_Unicity', $customerLink->getUnicity())
            ->argument('CustomerHref', $customerLink->href)
            ->actual_user()
            ->x_request_id()
            ->query();
    }

    public static function dscEmployeeLogin($type, $value, $expand = '')
    {
        $value = base64_decode($value);
        list($username, $password) = explode(':', $value, 2);
        if (!$password) {
            throw new Throwable\UnauthorizedException();
        }

        $results = SQL::select('api')
            ->from('dscemployees')
            ->column('password')
            ->column('email')
            ->where('email', '=', $username)
            ->log(true)
            ->query();

        if (!$results->is_loaded()) {
            throw new Throwable\UnauthorizedException();
        }

        if (!password_verify($password, $results[0]['password'])) {
            throw new Throwable\UnauthorizedException();
        }

        $dscEmployeeLink = DscEmployeeLink::getLinkByQueryString($results[0]['email']);

        $token = uuid_create();

        $tokenData = [
            'dscEmployee' => $dscEmployeeLink->href,
            'environment' => Utilities::getEnvironment(),
            'ip' => Utilities::getIPAddress(),
            'mode' => Utilities::getMode(),
            'userAgent' => Utilities::getUserAgent(),
        ];

        $IntervalSeconds = intval(Service\Connection::getProperties()->getValue('logintoken.expires_days')) * 86400;

        Connector\DB\SQL::exec('unity')
            ->procedure('dbo.hydraInsertLoginToken')
            ->argument('IntervalSeconds', $IntervalSeconds)
            ->argument('Token', $token)
            ->argument('TokenData', json_encode($tokenData, JSON_FORCE_OBJECT))
            ->actual_user()
            ->x_request_id()
            ->query();

        // switching SECURITY context
        AccessControl::processAuthorizationToken("Bearer {$token}");

        $result = [
            'dscEmployee' => $dscEmployeeLink,
            'expired' => AccessControl::getExpired(),
            'href' => Utilities::getBaseHref() . '/loginTokens/' . $token,
            'token' => $token,
        ];

        $expand = static::processExpand($expand);
        if (in_array('whoami', $expand)) {
            $result['whoami'] = WhoAmIController::getLoginDetails();
        } else {
            $result['whoami'] = [
                'href' => Utilities::getBaseHref() . '/whoami',
            ];
        }

        return $result;
    }

    public static function employeeLogin($type, $value, $expand = '')
    {
        if ($type === 'base64') {
            $value = base64_decode($value);
            list($username, $password) = explode(':', $value, 2);

            if (!Connector\UnityLogin::canAuthorize($username, $password, 'Employee|Contractor|Service')) {
                throw new Throwable\UnauthorizedException();
            }

            $employee = [
                'username' => $username,
            ];
        } elseif ($type === 'loginToken') {
            AccessControl::processAuthorizationToken("Bearer {$value}");

            $employee = AccessControl::getEmployee();

            if (!$employee) {
                throw new Throwable\UnauthorizedException();
            }
        } else {
            throw new Throwable\UnauthorizedException();
        }

        $token = static::createByEmployee($employee);

        AccessControl::processAuthorizationToken("Bearer {$token}");

        $result = [
            'employee' => $employee,
            'expired' => AccessControl::getExpired(),
            'href' => Utilities::getBaseHref() . '/loginTokens/' . $token,
            'token' => $token,
        ];

        $expand = static::processExpand($expand);
        if (in_array('whoami', $expand)) {
            $result['whoami'] = WhoAmIController::getLoginDetails();
        } else {
            $result['whoami'] = [
                'href' => Utilities::getBaseHref() . '/whoami',
            ];
        }

        if (in_array('firebaseToken', $expand) || in_array('firebaseTokenNiffler', $expand)) {
            if (in_array('firebaseTokenNiffler', $expand)) {
                global $firebaseConfigNiffler;
                $firebaseConfig = $firebaseConfigNiffler;
            } else {
                global $firebaseConfig;
            }
            $now_seconds = time();
            $permissions = WhoAmIController::getLoginDetails()->permissions;
            $resultPermissions = [];
            foreach ($permissions as $permission) {
                $resultPermissions[$permission] = true;
            }
            $payload = [
                'iss' => $firebaseConfig->client_email,
                'sub' => $firebaseConfig->client_email,
                'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
                'iat' => $now_seconds,
                'exp' => $now_seconds + (60 * 60),
                'uid' => $username,
                'claims' => ['permissions' => $resultPermissions],
            ];
            $result['firebaseToken'] = JWT::encode($payload, $firebaseConfig->private_key, 'RS256');
        }

        return $result;
    }

    private static function getDistributorId($email)
    {
        $result = Finder\CustomerFinder::execute([
            'query' => [
                'match' => [
                    'email' => $email,
                ],
            ],
            '_source' => [
                'customer.href',
                'customer.id.*',
                'customer.market',
            ],
        ]);
        if (is_array($result) && (count($result) === 1)) {
            return intval($result[0]->getUnicity());
        }

        throw new Throwable\UnauthorizedException();
    }

    private static function loginToInfoTraxWithId($customer_id, $actual_password): array
    {
        try {
            if (is_numeric($customer_id) && static::datatraxLogin($customer_id, $actual_password)) {
                return [intval($customer_id), Helper\Passwords::getHash($actual_password, Helper\Passwords::getSalt())];
            }

            throw new Throwable\UnauthorizedException();
        } catch (Throwable\UnauthorizedException $unauthorizedException) {
            Service\Logger::getInstance()->info('Helper\LoginToken::loginToInfoTraxWithId - Failed to find username in infotrax.', [
                'username' => $customer_id,
            ]);

            throw $unauthorizedException;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    private static function loginToUnityWithId($customer_id, $actual_password, $expected_hash, $customer_status): array
    {
        $actual_password = trim(substr(strval($actual_password), 0, 20)); // TODO remove length constraint once we are fully on Unity

        try {
            if (is_numeric($customer_id) && Helper\Passwords::match($expected_hash, $actual_password)) {
                if (!in_array($customer_status, ['Deleted', 'Terminated'])) {
                    if (in_array($customer_status, ['Retired'])) {
                        throw new Throwable\AccountLockedException();
                    }
                    if (in_array($customer_status, ['Suspended'])) {
                        throw new Throwable\AccountSuspendedException();
                    }

                    return [intval($customer_id), $expected_hash];
                }
            }

            throw new Throwable\UnauthorizedException();
        } catch (Throwable\UnauthorizedException $unauthorizedException) {
            Service\Logger::getInstance()->info('Helper\LoginToken::loginToUnityWithId - Failed to find username in unity.', [
                'username' => $customer_id,
            ]);

            throw $unauthorizedException;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public static function newLogin($username, $password): array
    {
        $hits = Connector\DB\SQL::exec('unity')
            ->procedure('dbo.hydraGetLoginInformation')
            ->argument('LoginName', $username)
            ->actual_user()
            ->x_request_id()
            ->log(true)
            ->query();

        if (!Service\BusinessRule::about('disable_infotrax')->matches(1) && !$hits->is_loaded()) {
            Service\Logger::getInstance()->info('Helper\LoginToken::newLogin - Failed to find username in unity.', [
                'username' => $username,
            ]);

            return static::oldLogin($username, $password);
        }

        $hitCt = count($hits);
        if ($hitCt !== 1) {
            Service\Logger::getInstance()->info('Helper\LoginToken::newLogin - Unable to resolve username via unity.', [
                'username' => $username,
                'hitCt' => $hitCt,
            ]);

            throw new Throwable\UnauthorizedException();
        }

        return static::loginToUnityWithId(
            $hits->get('CustomerId_Unicity'),
            $password,
            $hits->get('Password_Hash'),
            $hits->get('Status')
        );
    }

    private static function oldLogin($username, $password): array
    {
        // If it has an at sign we are going to consider it an email.
        // Because usernames can't contain @ signs and user ids are numeric
        $loginName = (strpos($username, '@') !== false)
            ? strval(static::getDistributorId($username))
            : strval($username);

        return static::loginToInfoTraxWithId($loginName, $password);
    }

    private static function processExpand($expand = '', $addInstance = false): array
    {
        if (empty($expand)) {
            $expand = [];
        } elseif (!is_array($expand)) {
            $expand = explode(',', $expand);
            $expand = array_map('trim', $expand);
        }

        if ($addInstance
            && !in_array('instance', $expand)) {
            $expand = array_merge($expand, ['instance']);
        }

        return $expand;
    }
}
