@extends('superadmin.layouts.app')
@section('title', 'Load Testing')
@section('content')
    <div class="settings-wrapper">
        <h2>Load Testing</h2>
        <form method="POST" action="{{ route('load-test.run') }}">
            @csrf
            <div class="form-group" style="margin-bottom:15px;">
                <label for="vus">Virtual Users (VUs)</label>
                <input type="number" id="vus" name="vus" class="form-control" min="1" required>
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label for="duration">Duration (seconds) <small>(optional, default 60)</small></label>
                <input type="number" id="duration" name="duration" class="form-control" min="1">
            </div>
            <button type="submit" class="btn btn-primary">Run Load Test</button>
        </form>
    </div>
@endsection
