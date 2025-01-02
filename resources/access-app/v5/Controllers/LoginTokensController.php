<?php

namespace v5\Controller;

use v5\Framework\BaseController;

/** @psalm-suppress UnusedClass **/
class LoginTokensController
{
    use LoginTokens\CreateToken;
    use LoginTokens\DeleteAllTokens;
    use LoginTokens\DeleteToken;
    use LoginTokens\GetToken;
    use LoginTokens\RenewToken;
}
