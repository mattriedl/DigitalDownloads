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
        $redirect = Redirect::where('from_path', Redirect::normalizePath('/'.$request->path()))->first();

        if (! $redirect) {
            abort(404);
        }

        $redirect->increment('hits');

        if ($redirect->status_code === 410) {
            abort(410);
        }

        return redirect()->to($redirect->to_url, $redirect->status_code === 302 ? 302 : 301);
    }
}
