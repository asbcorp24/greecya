@extends('admin.layout')

@section('title', 'Товары')
@section('heading', 'Билеты и абонементы')
@section('eyebrow', 'Каталог сайта и CRM-членства')

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="admin-card p-4">
            <h3>Новый товар</h3>
            <form method="post" action="{{ route('admin.products.store') }}" class="row g-3 mt-1">
                @csrf
                <div class="col-12">
                    <label class="form-label">Название</label>
                    <input class="form-control" name="name" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Тип</label>
                    <select class="form-select" name="type">
                        <option value="ticket">Разовый билет</option>
                        <option value="subscription">Абонемент</option>
                        <option value="gift">Подарочный</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Тариф членства CRM</label>
                    <select class="form-select" name="membership_plan_id">
                        <option value="">Не создавать членство автоматически</option>
                        @foreach($membershipPlans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} · {{ number_format($plan->price,0,',',' ') }} ₽</option>
                        @endforeach
                    </select>
                    <div class="form-text">Для абонемента свяжите товар сайта с тарифом CRM. После оплаты заказа членство создастся автоматически.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Описание</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="col-6">
                    <label class="form-label">Цена, ₽</label>
                    <input type="number" step="0.01" class="form-control" name="price" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Посещений</label>
                    <input type="number" class="form-control" name="visits_count" min="1">
                </div>
                <div class="col-12">
                    <label class="form-label">Срок действия, дней</label>
                    <input type="number" class="form-control" name="validity_days" value="30" min="1" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary w-100">Добавить товар</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h3>Каталог</h3>
                    <p>Цена, видимость на сайте и связь с тарифом членства</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Тип</th>
                            <th>CRM-тариф</th>
                            <th>Редактирование</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    <small>{{ Str::limit($product->description,55) }}</small>
                                </td>
                                <td>{{ $product->type }}</td>
                                <td>{{ $product->membershipPlan?->name ?: '—' }}</td>
                                <td>
                                    <form method="post" action="{{ route('admin.products.update',$product) }}" class="row g-2 align-items-center">
                                        @csrf
                                        @method('patch')
                                        <div class="col-md-4">
                                            <input type="number" step="0.01" class="form-control form-control-sm" name="price" value="{{ $product->price }}">
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select form-select-sm" name="membership_plan_id">
                                                <option value="">Без CRM-тарифа</option>
                                                @foreach($membershipPlans as $plan)
                                                    <option value="{{ $plan->id }}" @selected($product->membershipPlan?->id === $plan->id)>{{ $plan->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($product->is_active)>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-dark w-100"><i class="bi bi-check2"></i></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
