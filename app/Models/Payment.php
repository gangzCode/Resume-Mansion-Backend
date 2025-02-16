<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
class Payment extends Model
{
    use Billable;
}
