<?php
namespace App\Http\Controllers\Web;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Artisan;

use App\Http\Controllers\Controller;
use App\Models\User;

class UsersController extends Controller {

	use ValidatesRequests;

    public function list(Request $request) {
        if(!auth()->user()->hasPermissionTo('show_users'))abort(401);
        $query = User::select('*');
        $query->when($request->keywords,
        fn($q)=> $q->where("name", "like", "%$request->keywords%"));
        $users = $query->get();
        return view('users.list', compact('users'));
    }

	public function register(Request $request) {
        return view('users.register');
    }

    public function doRegister(Request $request) {

    	try {
    		$this->validate($request, [
	        'name' => ['required', 'string', 'min:5'],
	        'email' => ['required', 'email', 'unique:users'],
	        'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
	    	]);
    	}
    	catch(\Exception $e) {

    		return redirect()->back()->withInput($request->input())->withErrors('Invalid registration information.');
    	}


    	$user =  new User();
	    $user->name = $request->name;
	    $user->email = $request->email;
	    $user->password = bcrypt($request->password); //Secure
	    $user->save();

        return redirect('/');
    }

    public function login(Request $request) {
        return view('users.login');
    }

    public function doLogin(Request $request) {

    	if(!Auth::attempt(['email' => $request->email, 'password' => $request->password]))
            return redirect()->back()->withInput($request->input())->withErrors('Invalid login information.');

        $user = User::where('email', $request->email)->first();
        Auth::setUser($user);

        return redirect('/');
    }

    public function doLogout(Request $request) {

    	Auth::logout();

        return redirect('/');
    }

    public function profile(Request $request, User $user = null) {

        $user = $user??auth()->user();
        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $permissions = [];
        foreach($user->permissions as $permission) {
            $permissions[] = $permission;
        }
        foreach($user->roles as $role) {
            foreach($role->permissions as $permission) {
                $permissions[] = $permission;
            }
        }

        return view('users.profile', compact('user', 'permissions'));
    }

    public function edit(Request $request, User $user = null): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        $roles = [];
        foreach(Role::all() as $role) {
            $role->taken = ($user->hasRole($role->name));
            $roles[] = $role;
        }

        $permissions = [];
        $directPermissionsIds = $user->permissions()->pluck('id')->toArray();
        foreach(Permission::all() as $permission) {
            $permission->taken = in_array($permission->id, $directPermissionsIds);
            $permissions[] = $permission;
        }

        return view('users.edit', compact('user', 'roles', 'permissions'));
    }

    public function save(Request $request, User $user): \Illuminate\Foundation\Application|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {

        if(auth()->id()!=$user->id) {
            if(!auth()->user()->hasPermissionTo('show_users')) abort(401);
        }

        $user->name = $request->name;
        $user->save();

        if(auth()->user()->hasPermissionTo('admin_users')) {

            $user->syncRoles($request->roles);
            $user->syncPermissions($request->permissions);

            Artisan::call('cache:clear');
        }

        //$user->syncRoles([1]);
        //Artisan::call('cache:clear');

        return redirect(route('profile', ['user'=>$user->id]));
    }

    public function delete(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {

        if(!auth()->user()->hasPermissionTo('delete_users')) abort(401);

        //$user->delete();

        return redirect()->route('users');
    }

    public function editPassword(Request $request, User $user = null): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application
    {

        $user = $user??auth()->user();
        if(auth()->id()!=$user?->id) {
            if(!auth()->user()->hasPermissionTo('edit_users')) abort(401);
        }

        return view('users.edit_password', compact('user'));
    }



    ////////////New New

    public function createEmployee()
    {

        return view('users.create_employee');
    }

    public function storeEmployee(Request $request)
    {

        $request->validate([
            'name' => 'required|string|min:5',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

     $user->assignRole('Employee'); // Assign Employee role

      return redirect('/');

     // return redirect()->route('users')->with('success', 'Employee added successfully.');
    }

    //////////////////


    public function savePassword(Request $request, User $user): \Illuminate\Foundation\Application|\Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
    {

        if(auth()->id()==$user?->id) {

            $this->validate($request, [
                'password' => ['required', 'confirmed', Password::min(8)->numbers()->letters()->mixedCase()->symbols()],
            ]);

            if(!Auth::attempt(['email' => $user->email, 'password' => $request->old_password])) {

                Auth::logout();
                return redirect('/');
            }
        }
        else if(!auth()->user()->hasPermissionTo('edit_users')) {

            abort(401);
        }

        $user->password = bcrypt($request->password); //Secure
        $user->save();

        return redirect(route('profile', ['user'=>$user->id]));
    }
}
