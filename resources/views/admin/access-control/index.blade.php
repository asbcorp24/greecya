@extends('admin.layout')
@section('title','Права доступа') @section('heading','Роли и права') @section('eyebrow','Безопасность CRM')
@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="admin-card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div><h3 class="mb-1">Профиль роли</h3><p class="text-muted mb-0">Права задаются по действиям, а не только по названию роли.</p></div>
                <form method="get" class="d-flex gap-2"><select class="form-select" name="role" onchange="this.form.submit()">@foreach($roles as $code=>$label)<option value="{{ $code }}" @selected($selectedRole===$code)>{{ $label }}</option>@endforeach</select></form>
            </div>
            <form method="post" action="{{ route('admin.permissions.roles.update') }}">@csrf @method('put')<input type="hidden" name="role" value="{{ $selectedRole }}">
                @foreach($permissions->groupBy('group') as $group=>$items)
                    <div class="border rounded-4 p-3 mb-3"><h5>{{ $group }}</h5><div class="row g-2">
                        @foreach($items as $permission)<div class="col-md-6"><label class="form-check border rounded-3 p-2 ps-5 h-100"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id,$rolePermissionIds,true))><strong>{{ $permission->name }}</strong><small class="d-block text-muted">{{ $permission->code }}</small></label></div>@endforeach
                    </div></div>
                @endforeach
                <button class="btn btn-primary">Сохранить права роли</button>
            </form>
        </div>

        @if($selectedUser)
        <div class="admin-card p-4">
            <h3>Индивидуальные исключения: {{ $selectedUser->name }}</h3><p class="text-muted">«Наследовать» использует права роли. Разрешить/запретить имеет приоритет над ролью.</p>
            <form method="post" action="{{ route('admin.permissions.users.update',$selectedUser) }}">@csrf @method('put')
                @foreach($permissions->groupBy('group') as $group=>$items)<h5 class="mt-4">{{ $group }}</h5><div class="table-responsive"><table class="table align-middle"><tbody>
                    @foreach($items as $permission)<tr><td><strong>{{ $permission->name }}</strong><small class="d-block text-muted">{{ $permission->code }}</small></td><td style="width:240px"><select class="form-select form-select-sm" name="state[{{ $permission->id }}]"><option value="">Наследовать от роли</option><option value="allow" @selected(array_key_exists($permission->id,$overrides)&&$overrides[$permission->id]===true)>Разрешить</option><option value="deny" @selected(array_key_exists($permission->id,$overrides)&&$overrides[$permission->id]===false)>Запретить</option></select></td></tr>@endforeach
                </tbody></table></div>@endforeach
                <button class="btn btn-dark">Сохранить исключения</button>
            </form>
        </div>
        @endif
    </div>

    <div class="col-xl-4">
        <div class="admin-card p-4 mb-4"><h3>Новый сотрудник</h3><form method="post" action="{{ route('admin.permissions.users.store') }}" class="row g-2">@csrf
            <div class="col-12"><input class="form-control" name="name" placeholder="ФИО" required></div><div class="col-12"><input type="email" class="form-control" name="email" placeholder="Email" required></div><div class="col-12"><input class="form-control" name="phone" placeholder="Телефон"></div>
            <div class="col-12"><select class="form-select" name="role" required>@foreach($roles as $code=>$label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12"><select class="form-select" name="trainer_id"><option value="">Карточка тренера — не требуется</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}">{{ $trainer->name }}</option>@endforeach</select></div>
            <div class="col-12"><input type="password" class="form-control" name="password" placeholder="Временный пароль" required></div><div class="col-12"><button class="btn btn-primary w-100">Создать сотрудника</button></div>
        </form></div>
        <div class="admin-card p-4"><h3>Сотрудники</h3>@foreach($users as $user)<div class="border-bottom py-3"><a class="fw-bold text-decoration-none" href="{{ route('admin.permissions.index',['role'=>$selectedRole,'user'=>$user->id]) }}">{{ $user->name }}</a><small class="d-block text-muted">{{ $roles[$user->role]??$user->role }} @if($user->trainer) · {{ $user->trainer->name }} @endif</small><form method="post" action="{{ route('admin.permissions.users.role',$user) }}" class="d-flex gap-1 mt-2">@csrf @method('patch')<select class="form-select form-select-sm" name="role">@foreach($roles as $code=>$label)<option value="{{ $code }}" @selected($user->role===$code)>{{ $label }}</option>@endforeach</select><select class="form-select form-select-sm" name="trainer_id"><option value="">—</option>@foreach($trainers as $trainer)<option value="{{ $trainer->id }}" @selected($user->trainer_id===$trainer->id)>{{ $trainer->name }}</option>@endforeach</select><button class="btn btn-sm btn-outline-dark">OK</button></form></div>@endforeach</div>
    </div>
</div>
@endsection
