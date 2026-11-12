<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Any URL no route matched: follow an admin-defined redirect if there is one, else 404. */
class FallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Redirect::responseFor($request->path()) ?? abort(404);
    }
}
