@extends('layouts.master')
@section('title', 'Add Employee')
@section('content')
    <div class="card mt-4">
        <div class="card-header">Create Employee</div>
        <div class="card-body">
            <form method="POST" action="{{ route('employees.store') }}">
                @csrf


                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif



                <div class="mb-3">
                    <label>Name</label>
                    <input name="name" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input name="email" type="email" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input name="password" type="password" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label>Confirm Password</label>
                    <input name="password_confirmation" type="password" class="form-control" required />
                </div>
                <button type="submit" class="btn btn-primary">Add Employee</button>
            </form>
        </div>
    </div>
@endsection
