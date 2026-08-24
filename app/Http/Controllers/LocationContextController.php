<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LocationContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationContextController extends Controller
{
    public function __construct(protected LocationContext $context) {}

    public function index(Request $request): View|JsonResponse
    {
        $locations = $this->context->authorizedLocations();
        $data = [
            'locations' => $locations,
            'activeLocation' => $this->context->current(),
            'isGlobal' => $this->context->isGlobal(),
            'canViewAll' => app('admin.auth')->user()->hasPermission('Restaurant.LocationContext.ViewAll'),
            'canSelectInactive' => app('admin.auth')->user()->hasPermission('Admin.Locations'),
            'redirect' => (string) $request->query('redirect', ''),
        ];

        return $request->expectsJson()
            ? response()->json(['data' => [
                'active_location_id' => $this->context->currentId(),
                'global' => $data['isGlobal'],
                'locations' => $locations->map(fn ($location) => [
                    'id' => $location->getKey(), 'name' => $location->location_name,
                    'city' => $location->location_city, 'active' => (bool) $location->location_status,
                ])->values(),
            ]])
            : view('admin.location-context.select', $data);
    }

    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(['location_id' => ['required', 'integer'], 'redirect' => ['nullable', 'string']]);
        try {
            $location = $this->context->set($validated['location_id']);
        } catch (AuthorizationException $exception) {
            return $this->forbidden($request, $exception->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['data' => ['active_location_id' => $location->getKey()]])
            : $this->redirectAfterSelection($request);
    }

    public function global(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $this->context->setGlobal();
        } catch (AuthorizationException $exception) {
            return $this->forbidden($request, $exception->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['data' => ['global' => true]])
            : $this->redirectAfterSelection($request);
    }

    protected function redirectAfterSelection(Request $request): RedirectResponse
    {
        $redirect = (string) $request->input('redirect', '');
        if ($redirect !== '' && str_starts_with($redirect, url('/'))) {
            return redirect()->to($redirect);
        }

        return redirect()->intended(route('naxas.restaurantops.overview'));
    }

    protected function forbidden(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['error' => ['code' => 'location_forbidden', 'message' => $message]], 403)
            : abort(403, $message);
    }
}
