<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** The visitor's own area: saved downloads, download history and account settings. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', [
            'favorites' => $user->favorites()->published()->with('category')
                ->orderByPivot('created_at', 'desc')->paginate(20, pageName: 'saved'),
            'history' => Download::where('user_id', $user->id)
                ->with('item.category')
                ->whereHas('item', fn ($q) => $q->published())
                ->latest('created_at')->take(20)->get(),
        ]);
    }

    public function settings(): View
    {
        return view('account.settings');
    }

    public function toggleFavorite(Request $request, Item $item): RedirectResponse
    {
        abort_unless($item->isPublished(), 404);

        $saved = $request->user()->favorites()->toggle($item->id)['attached'] !== [];

        return back()->with('status', $saved ? 'Saved to your downloads.' : 'Removed from your saved downloads.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->password && ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'That password is not correct.'])->errorBag('deleteAccount');
        }
        abort_if($user->isStaff(), 403, 'Staff accounts are removed by another admin in the admin panel.');

        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account was deleted.');
    }
}
