<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Chalo Online HR</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
  <style>
        :root{
            --y:#F5C400;--yd:#C49A00;--yl:#FFF8D6;
            --k:#0A0A0A;--k2:#141414;--k3:#1E1E1E;
            --w:#FFFFFF;--g1:#F5F4F0;--g2:#E8E6DF;
            --text:#1A1A1A;--muted:#7A7672;--border:#E0DDD5;
            --green:#1A9E5C;--greenl:#E6F7EE;
            --red:#D93B3B;--redl:#FDEAEA;
            --blue:#2B5CE6;--bluel:#EBF0FF;
            --radius:10px;--shadow:0 2px 16px rgba(0,0,0,0.07);
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;
            justify-content: center;
        background:var(--g1);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .login-wrap{display:flex;gap:0;max-width:900px;width:100%;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.12);}
        .login-left{background:var(--k);padding:50px 40px;flex:1;display:flex;flex-direction:column;justify-content:center;color:var(--w);}
        .login-left h1{font-family:'Syne',sans-serif;font-size:32px;font-weight:800;line-height:1.2;margin-bottom:12px;}
        .login-left h1 span{color:var(--y);}
        .login-left p{font-size:14px;color:rgba(255,255,255,0.6);line-height:1.6;margin-bottom:30px;}
        .login-features{list-style:none;padding:0;}
        .login-features li{display:flex;align-items:center;gap:12px;padding:10px 0;font-size:13px;color:rgba(255,255,255,0.7);border-bottom:1px solid rgba(255,255,255,0.06);}
        .login-features li:last-child{border-bottom:none;}
        .feature-icon{width:32px;height:32px;border-radius:8px;background:rgba(245,196,0,0.1);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
        .login-right{background:var(--w);padding:50px 40px;flex:1;}
        .login-right h2{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;margin-bottom:6px;}
        .login-right .sub{font-size:13px;color:var(--muted);margin-bottom:30px;}
        .field{display:flex;flex-direction:column;gap:6px;margin-bottom:18px;}
        label.lbl{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;}
        input{border:1.5px solid var(--border);border-radius:7px;padding:11px 13px;font-family:'DM Sans',sans-serif;font-size:13px;background:var(--g1);transition:all 0.15s;outline:none;width:100%;}
        input:focus{border-color:var(--yd);background:white;box-shadow:0 0 0 3px rgba(245,196,0,0.12);}
        .btn-login{width:100%;padding:12px;border:none;border-radius:8px;background:var(--k);color:var(--y);font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;cursor:pointer;transition:all 0.15s;margin-top:10px;}
        .btn-login:hover{background:var(--k2);transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.15);}
        .btn-login:active{transform:translateY(0);}
        .btn-login:disabled{opacity:0.5;cursor:not-allowed;}
        .alert-error{background:var(--redl);border:1px solid #f5c0c0;color:var(--red);padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:20px;}
        .alert-error ul{margin:0;padding-left:18px;}
        .alert-error li{margin:4px 0;}
        .footer-text{font-size:11px;color:var(--muted);text-align:center;margin-top:24px;line-height:1.6;}
        @media(max-width:768px){
            .login-wrap{flex-direction:column;}
            .login-left{padding:30px 24px;}
            .login-right{padding:30px 24px;}
            .login-left h1{font-size:26px;}
            .login-features{display:none;}
            .otp-inputs{grid-template-columns:repeat(3, 1fr);}
        }
        @keyframes fadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
        .fade-up{animation:fadeUp 0.3s ease;}
    </style>
</head>
<body>
    <div class="login-wrap fade-up">
        <div class="login-left">
            <h1>Welcome to<br><span>Chalo Online</span> </h1>
            <p>Streamline your workforce management with our comprehensive HR platform. From onboarding to offboarding, we've got you covered.</p>
            <ul class="login-features">
                <li>
                    <div class="feature-icon">👥</div>
                    Employee Management & Tracking
                </li>
                <li>
                    <div class="feature-icon">💰</div>
                    Automated Payroll & TA Processing
                </li>
                <li>
                    <div class="feature-icon">⚖️</div>
                    Compliance & Legal Monitoring
                </li>
                <li>
                    <div class="feature-icon">📊</div>
                    Real-time Analytics & Reports
                </li>
            </ul>
        </div>
        <div class="login-right">
            <h2>Sign In</h2>
            <p class="sub">Enter your credentials to access your dashboard</p>

            @if ($errors->any())
                <div class="alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="alert-error">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Password Login Form (Super Admin) --}}
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="login" class="lbl">Email Address</label>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" placeholder="admin@company.com" required>
                </div>
                <div class="field">
                    <label for="password" class="lbl">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">
                    Login →
                </button>
            </form>
            <p style="text-align:center;margin-top:15px;">
                <a href="{{ route('staff.login') }}" style="color:var(--blue);text-decoration:none;font-size:13px;">👥 Staff Login (OTP) →</a>
            </p>

            <p class="footer-text">
                Protected by enterprise-grade security<br>
                Need help? Contact <strong>support@chaloonline.in</strong>
            </p>
        </div>
    </div>
</body>
</html>
