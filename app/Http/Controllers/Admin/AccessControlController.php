<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    public function index(Request $request)
    {
        $roles = Arr::except(config('access.roles', []), ['customer']);
        $selectedRole = $request->string('role')->toString() ?: 'manager';
        if (! array_key_exists($selectedRole, $roles)) {
            $selectedRole = 'manager';
        }

        $permissions = Permission::orderBy('group')->orderBy('sort_order')->get();
        $rolePermissionIds = DB::table('role_permissions')->where('role', $selectedRole)->pluck('permission_id')->map(fn($id)=>(int)$id)->all();
        $users = User::where('role','!=','customer')->orderBy('name')->get();
        $selectedUser = $request->integer('user') ? User::find($request->integer('user')) : null;
        $overrides = $selectedUser
            ? DB::table('user_permissions')->where('user_id',$selectedUser->id)->pluck('allowed','permission_id')->map(fn($v)=>(bool)$v)->all()
            : [];

        return view('admin.access-control.index', compact('roles','selectedRole','permissions','rolePermissionIds','users','selectedUser','overrides'));
    }

    public function storeUser(Request $request)
    {
        $roles = array_keys(Arr::except(config('access.roles', []), ['customer']));
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'email'=>'required|email|max:190|unique:users,email',
            'phone'=>'nullable|string|max:30',
            'role'=>['required',Rule::in($roles)],
            'password'=>'required|string|min:8|max:190',
        ]);
        $data['password'] = Hash::make($data['password']);
        User::create($data);
        return back()->with('success','Сотрудник создан.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 422, 'Роль клиента меняется в клиентской карточке.');
        $roles = array_keys(Arr::except(config('access.roles', []), ['customer']));
        $data = $request->validate(['role'=>['required',Rule::in($roles)]]);
        if ($user->id === $request->user()->id && ! in_array($data['role'], ['admin','director'], true)) {
            return back()->withErrors(['role'=>'Нельзя понизить собственную административную роль из этого раздела.']);
        }
        $user->update($data);
        return back()->with('success','Роль сотрудника изменена.');
    }

    public function updateRolePermissions(Request $request)
    {
        $roles = array_keys(Arr::except(config('access.roles', []), ['customer']));
        $data = $request->validate([
            'role'=>['required',Rule::in($roles)],
            'permissions'=>'array',
            'permissions.*'=>'exists:permissions,id',
        ]);
        $ids = collect($data['permissions'] ?? [])->map(fn($id)=>(int)$id)->unique()->values();
        $crmAccess = Permission::where('code','crm.access')->value('id');
        if ($crmAccess && ! $ids->contains((int)$crmAccess)) {
            $ids->push((int)$crmAccess);
        }

        DB::transaction(function() use ($data,$ids) {
            DB::table('role_permissions')->where('role',$data['role'])->delete();
            foreach ($ids as $id) {
                DB::table('role_permissions')->insert([
                    'role'=>$data['role'],'permission_id'=>$id,'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
        return back()->with('success','Права роли сохранены.');
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        abort_if($user->role === 'customer', 422, 'Индивидуальные CRM-права клиентам не назначаются.');
        $states = $request->input('state', []);
        $validIds = Permission::pluck('id')->map(fn($id)=>(int)$id)->flip();

        DB::transaction(function() use ($user,$states,$validIds) {
            DB::table('user_permissions')->where('user_id',$user->id)->delete();
            foreach ($states as $permissionId => $state) {
                $permissionId = (int) $permissionId;
                if (! $validIds->has($permissionId) || ! in_array($state,['allow','deny'],true)) {
                    continue;
                }
                DB::table('user_permissions')->insert([
                    'user_id'=>$user->id,
                    'permission_id'=>$permissionId,
                    'allowed'=>$state==='allow',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        });
        return back()->with('success','Индивидуальные исключения сохранены.');
    }
}
