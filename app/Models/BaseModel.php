<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * BaseModel serves as the shared Eloquent base for all CRM entities,
     * keeping table/fillable conventions centralized without imposing
     * empty guarded defaults.
     */
}
