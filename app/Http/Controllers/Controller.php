<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Todo modulo do PayrollOS passa por policy (Fase 7 do plano).
    use AuthorizesRequests;
}
