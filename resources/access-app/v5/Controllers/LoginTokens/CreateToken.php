<?php

namespace v5\Controller\LoginTokens;

use v5\AccessControl;
use v5\Helper\Captcha\Captcha;
use v5\Helper\LoginToken;
use v5\Throwable;
use v5\Utilities;

trait CreateToken
{
    /**
     * Authenticates customer.
     *
     * Authenticates customer with base64 encoded username and
     * password pair that is separated with a colon (for example testuser:testpassword)
     *
     * @url POST
     *
     * @access protected
     * @class AccessControl {@requires guest}
     *
     * @param string $type {@from body} {@choice apple,base64,facebook,google,loginToken,plain,unicity}
     * @param string $value {@from body}
     * @param string $namespace {@from body}
     * @param string $expand Fields to expand {@from query} {@required false}
     * @param null|string $recaptchaToken recaptcha response used for verifying user is not a bot {@from body} {@required false}
     * @param null|string $recaptchaType recaptcha type (null | invisible) {@from body} {@required false}
     *
     * @status 201
     * @return mixed
     * @throws 400 Username or password are invalid.
     */
    public static function createToken(
        $type,
        $value,
        $namespace,
        $expand = '',
        ?string $recaptchaToken = null,
        ?string $recaptchaType = null
    ) {
        if ($recaptchaToken !== null) {
            // check the Google Recaptcha if configured/required
            Captcha::validateCaptchaOrThrow($recaptchaToken, $recaptchaType);
        }

        if ($namespace === Utilities::getBaseHref() . '/customers') {
            return LoginToken::customerLogin($type, $value, $expand);
        } elseif ($namespace === Utilities::getBaseHref() . '/dscemployees') {
            return LoginToken::dscEmployeeLogin($type, $value, $expand);
        } elseif ($namespace === Utilities::getBaseHref() . '/employees') {
            return LoginToken::employeeLogin($type, $value, $expand);
        } else {
            throw new Throwable\UnauthorizedException();
        }
    }
}
