<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AIModel extends Model implements Auditable
{
    use AuditableTrait;

    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'ai_models';
}
