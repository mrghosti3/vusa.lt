<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendWelcomeEmail;
use App\Http\Controllers\ResourceController;
use App\Models\Duty;
use App\Models\Padalinys;
use App\Models\Role;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class UserController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [User::class, $this->authorizer]);

        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $users = $indexer->execute(User::class, $search, 'name', $this->authorizer)->with('duties:id,institution_id', 'duties.institution:id,padalinys_id', 'duties.institution.padalinys:id,shortname')->withCount('duties')
        ->get()->makeVisible(['last_action'])->paginate(20);

        return Inertia::render('Admin/People/IndexUser', [
            'users' => $users,
            'usersCount' => fn () => $users->total(),
            'usersLoggedInCount' => fn () => User::when(auth()->user()->hasRole(config('permission.super_admin_role_name')), fn ($query) => $query, function ($query) {
                return $query->whereHas('padaliniai', function ($query) {
                    $query->whereIn('padaliniai.id', $this->authorizer->getPadaliniai()->pluck('id'));
                });
            })->whereNotNull('last_action')->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [User::class, $this->authorizer]);

        return Inertia::render('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'padaliniaiWithDuties' => $this->getDutiesForForm(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [User::class, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'duties' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            // create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'profile_photo_path' => $request->profile_photo_path,
            ]);

            foreach ($request->duties as $duty) {
                $user->duties()->attach($duty, ['start_date' => now()]);
            }

            // check if user is super admin
            if (User::find(Auth::id())->hasRole(config('permission.super_admin_role_name'))) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->syncRoles([]);
                }
            }
        });

        return redirect()->route('users.index')->with('success', 'Kontaktas sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $this->authorize('view', [User::class, $user, $this->authorizer]);

        return Inertia::render('Admin/People/ShowUser', [
            'user' => $user->load(['duties' => function ($query) {
                $query->withPivot('start_date', 'end_date');
            }]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        // user load duties with pivot
        $user->load(['duties' => function ($query) {
            $query->withPivot('start_date', 'end_date');
        }])->load('roles');

        return Inertia::render('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action']),
            // get all roles
            'roles' => fn () => Role::all(),
            'padaliniaiWithDuties' => fn () => $this->getDutiesForForm(),
        ]);
    }

    public function sendWelcomeEmail(User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        SendWelcomeEmail::execute((new Collection())->push($user));

        return back()->with('success', 'Laiškas sėkmingai išsiųstas!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', [User::class, $user, $this->authorizer]);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'roles' => 'array',
        ]);

        DB::transaction(function () use ($request, $user) {
            $user->update($request->only('name', 'email', 'phone', 'profile_photo_path'));
            $user->duties()->syncWithPivotValues($request->duties, ['start_date' => now()]);

            // check if user is super admin
            if (User::find(Auth::id())->hasRole(config('permission.super_admin_role_name'))) {
                // check if user is super admin
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->roles()->sync([]);
                }
            }
        });

        return back()->with('success', 'Kontaktas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', [User::class, $user, $this->authorizer]);

        $user->duties()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
    }

    private function getDutiesForForm()
    {
        // return Duty::with(['institution:id,name,padalinys_id', 'institution.padalinys:id,shortname'])
        // ->when(!auth()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
        //     $query->whereHas('institution', function ($query) {
        //         $query->where('padalinys_id', User::find(Auth::id())->padalinys()?->id);
        //     });
        // })->get();

        return Padalinys::orderBy('shortname')->with('institutions:id,name,padalinys_id', 'institutions.duties:id,name,institution_id')
            ->when(! auth()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
                $query->whereIn('id', User::find(Auth::id())->padaliniai->pluck('id'));
            })->get();
    }

    public function storeFromMicrosoft()
    {
        $microsoftUser = Socialite::driver('microsoft')->stateless()->user();

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::where('email', $microsoftUser->email)->first();

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token;

            if (Auth::login($user)) {
                request()->session()->regenerate();

                return redirect()->intended(RouteServiceProvider::HOME);
            }

            return redirect()->route('dashboard');
        }

        $duty = Duty::where('email', $microsoftUser->email)->first();

        if ($duty) {
            $user = $duty->users()->first();
            $user->microsoft_token = $microsoftUser->token;

            if (Auth::login($user)) {
                request()->session()->regenerate();

                return redirect()->intended(RouteServiceProvider::HOME);
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('home');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('home', ['padalinys' => 'www']);
    }
}
