<?php

namespace App\Support\Http;

use App\Support\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

/**
 * Thin base for module API controllers: authorization helpers + JSON envelope.
 */
abstract class ApiController extends Controller
{
    use ApiResponses, AuthorizesRequests;
}
