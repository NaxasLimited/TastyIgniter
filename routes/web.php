<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\LocationContextController;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\User\Facades\AdminAuth;
use Igniter\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::post(Igniter::adminUri().'/local-login', function(Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $user = User::query()->whereEmail($data['email'])->whereIsEnabled()->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        throw ValidationException::withMessages([
            'email' => lang('igniter.user::default.login.alert_login_failed'),
        ]);
    }

    AdminAuth::login($user, true);
    $request->session()->regenerate();

    return redirect(admin_url('dashboard'));
});

Route::middleware(['web', 'igniter', 'igniter:admin', 'location.context', 'restaurant.ops.permission:Restaurant.LocationContext.Access'])
    ->prefix(Igniter::adminUri())
    ->name('naxas.restaurantops.location-context.')
    ->group(function (): void {
        Route::get('/restaurant-ops/location-context', [LocationContextController::class, 'index'])->name('index');
        Route::get('/restaurant-ops/location-context/select', [LocationContextController::class, 'index'])->name('select');
        Route::post('/restaurant-ops/location-context/switch', [LocationContextController::class, 'switch'])->name('switch');
        Route::post('/restaurant-ops/location-context/global', [LocationContextController::class, 'global'])->name('global');
    });
