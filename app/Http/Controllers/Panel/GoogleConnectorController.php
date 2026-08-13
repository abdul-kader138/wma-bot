<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ConnectorAccount;
use App\Services\Maria\Google\GoogleOAuthService;
use App\Services\Maria\MariaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoogleConnectorController extends Controller
{
    public function redirect(GoogleOAuthService $oauth): RedirectResponse
    {
        abort_unless(MariaAccess::allowed(auth()->user()), 403);

        return $oauth->authorizationRedirect();
    }

    public function writeRedirect(GoogleOAuthService $oauth): RedirectResponse
    {
        abort_unless(MariaAccess::allowed(auth()->user()), 403);

        return $oauth->writeAuthorizationRedirect();
    }

    public function callback(Request $request, GoogleOAuthService $oauth): RedirectResponse
    {
        abort_unless(MariaAccess::allowed($request->user()), 403);

        $data = $request->validate(['code' => ['required', 'string'], 'state' => ['required', 'string']]);
        $oauth->connect($request->user(), $data['code'], $data['state']);

        return redirect()->to('/admin/connector-accounts')->with('status', 'Google Workspace connected.');
    }

    public function disconnect(Request $request, ConnectorAccount $connector, GoogleOAuthService $oauth): RedirectResponse
    {
        abort_unless(MariaAccess::allowed($request->user()), 403);
        abort_unless($request->user()->isAdmin() || $connector->user_id === $request->user()->id, 403);
        $oauth->disconnect($connector);

        return back()->with('status', 'Google Workspace disconnected.');
    }
}
