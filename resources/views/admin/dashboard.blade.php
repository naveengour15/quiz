@extends('admin.layouts.app')
@section('title', 'Admin Dashboard')
@section('content')

<style>
    @media(max-width:767px){
        .retailer-dashboard-grid-box{
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media(max-width:575px){
        .retailer-dashboard-grid-box{
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

    <div class="panel active">
        <div class="page-head">
            <div>
                <h1>Retailer Dashboard</h1>
                <p>Overview of your tenant platform & performance stats.</p>
            </div>
        </div>
        <div class="kpi-grid retailer-dashboard-grid-box">
            <div class="kpi-card">
                <div class="lbl">Total Users</div><b>{{ number_format($stats['total_users']) }}</b>
            </div>
            <div class="kpi-card">
                <div class="lbl">Revenue Today</div><b>₹{{ number_format($stats['revenue_today']) }}</b>
            </div>
            <div class="kpi-card">
                <div class="lbl">New Signups Today</div><b>{{ number_format($stats['new_signups_today']) }}</b>
            </div>
            <div class="kpi-card">
                <div class="lbl">Moderation Queue</div><b>{{ $stats['moderation_queue'] }}</b><span class="delta warn">● Needs review</span>
            </div>
            <div class="kpi-card">
                <div class="lbl">Active Subscriptions</div><b>{{ number_format($stats['active_subscriptions']) }}</b>
            </div>
        </div>

        <p style="color:var(--ink-faint);font-size:12px;margin-top:20px;">Use the sidebar to manage categories, quiz types, questions, quizzes, and surveys for your store.</p>
    </div>

@endsection
