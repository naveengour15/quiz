<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Login — FSR Quiz</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <style>
        :root {
            --indigo: #4F46E5;
            --indigo-deep: #3628B0;
            --indigo-ink: #221A5C;
            --orange: #FB923C;
            --emerald: #10B981;
            --emerald-bg: #ECFDF5;
            --coral: #EF4444;
            --coral-bg: #FEF2F2;
            --gold: #F59E0B;
            --gold-bg: #FFFBEB;
            --ink: #10132B;
            --ink-soft: #5B5F7A;
            --ink-faint: #8E92AC;
            --paper: #F5F4FB;
            --surface: #FFFFFF;
            --line: #E5E3F2;
            --font-display: 'Sora', sans-serif;
            --font-body: 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;

            /* mapped aliases so the rest of the stylesheet needs no structural changes */
            --y: var(--indigo);
            --yd: var(--indigo-deep);
            --yl: var(--gold-bg);
            --k: var(--ink);
            --k2: var(--indigo-ink);
            --k3: var(--indigo-deep);
            --w: var(--surface);
            --g1: var(--paper);
            --g2: var(--line);
            --text: var(--ink);
            --muted: var(--ink-soft);
            --border: var(--line);
            --green: var(--emerald);
            --greenl: var(--emerald-bg);
            --red: var(--coral);
            --redl: var(--coral-bg);
            --blue: var(--indigo);
            --bluel: rgba(79, 70, 229, 0.08);
            --radius: 10px;
            --shadow: 0 2px 16px rgba(34, 26, 92, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-body);
            background: var(--g1);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrap {
            display: flex;
            gap: 0;
            max-width: 900px;
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
        }

        .login-left {
            background: var(--k);
            padding: 50px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--w);
        }

        .login-left h1 {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .login-left h1 span {
            color: var(--y);
        }

        .login-left p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .login-right {
            background: var(--w);
            padding: 50px 40px;
            flex: 1;
        }

        .login-right h2 {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .login-right .sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 30px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 18px;
        }

        label.lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input {
            border: 1.5px solid var(--border);
            border-radius: 7px;
            padding: 11px 13px;
            font-family: var(--font-body);
            font-size: 13px;
            background: var(--g1);
            transition: all 0.15s;
            outline: none;
            width: 100%;
        }

        input:focus {
            border-color: var(--yd);
            background: white;
            box-shadow: 0 0 0 3px rgba(245, 196, 0, 0.12);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: var(--k);
            color: var(--y);
            font-family: var(--font-body);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: var(--k2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .alert-error {
            background: var(--redl);
            border: 1px solid #f5c0c0;
            color: var(--red);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: var(--greenl);
            border: 1px solid #b0e6cc;
            color: var(--green);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .footer-text {
            font-size: 11px;
            color: var(--muted);
            text-align: center;
            margin-top: 24px;
            line-height: 1.6;
        }

        .otp-step {
            display: none;
        }

        .otp-step.active {
            display: block;
        }

        .otp-inputs {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
        }

        .otp-inputs input {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            padding: 15px 5px;
        }

        .btn-otp {
            background: var(--blue);
            color: white;
        }

        .btn-otp:hover {
            background: var(--indigo-deep);
        }

        .resend-link {
            color: var(--blue);
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            margin-top: 15px;
            cursor: pointer;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .resend-link.hidden-link {
            display: none;
        }

        .back-to-admin {
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            display: inline-block;
            margin-top: 20px;
        }

        .back-to-admin:hover {
            color: var(--text);
        }

        .login-badge {
            display: inline-block;
            background: var(--yl);
            color: var(--yd);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        @media(max-width:768px) {
            .login-wrap {
                flex-direction: column;
            }

            .login-left {
                padding: 30px 24px;
            }

            .login-right {
                padding: 30px 24px;
            }

            .login-left h1 {
                font-size: 26px;
            }

            .otp-inputs {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(10px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .fade-up {
            animation: fadeUp 0.3s ease;
        }
    </style>
</head>

<body>
    <div class="login-wrap fade-up">
        <div class="login-left">
            <h1>Super Admin<br> <span>Login</span> </h1>
            <p class="mobilemb">Welcome! Login with your registered Email and Password to access your dashboard.</p>
            <div class="mobilehide"
                style="margin-top:30px;padding:20px;background:rgba(255,255,255,0.05);border-radius:8px;">
                <div style="font-size:13px;color:rgba(255,255,255,0.7);margin-bottom:10px;">📱 How to login:</div>
                <ol style="font-size:12px;color:rgba(255,255,255,0.6);padding-left:20px;line-height:1.8;">
                    <li>Enter your Email</li>                    
                    <li>Enter Password</li>
                    <li>Access your dashboard</li>
                </ol>
            </div>
        </div>
        <div class="login-right">
            <span class="login-badge">👥 Super Admin Portal</span>
            <h2>Super Admin Login</h2>
            <p class="sub">Login with your mobile number and OTP</p>

            

            {{-- <a href="{{ route('login') }}" class="back-to-admin">← Back to Admin Login</a> --}}

            {{-- Super Admin: ID & Password login --}}
            <div >
                <div class="field">
                    <label for="pwLogin" class="lbl">Email or Mobile Number</label>
                    <input type="text" id="pwLogin" placeholder="admin@example.com">
                </div>
                <div class="field">
                    <label for="pwPassword" class="lbl">Password</label>
                    <input type="password" id="pwPassword" placeholder="••••••••">
                </div>
                <button type="button" class="btn-login btn-otp" onclick="passwordLogin()">
                    Login →
                </button>
                 <a href="{{ route('superadmin.user.login') }}"class="resend-link">← Back to Mobile OTP Login</a> 
            </div>

            {{-- <a class="resend-link" id="toggleModeLink" onclick="toggleLoginMode()">🔐 Super Admin? Login with ID &amp; Password</a> --}}

            <p class="footer-text">
                Protected by enterprise-grade security<br>
                Need help? Contact <strong>support@chaloonline.in</strong>
            </p>
        </div>
    </div>

    <script>
        let currentMobile = '';

        // OTP Input auto-focus
        document.querySelectorAll('.otp-input').forEach((input, idx) => {
            input.addEventListener('input', function() {
                if (this.value && idx < 5) {
                    document.querySelector(`.otp-input[data-index="${idx + 1}"]`).focus();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    document.querySelector(`.otp-input[data-index="${idx - 1}"]`).focus();
                }
            });
        });

        async function sendOtp() {
            const mobile = document.getElementById('otpMobile').value;
            if (!mobile) {
                alert('Please enter your mobile number');
                return;
            }

            currentMobile = mobile;
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const response = await fetch('{{ route('superadmin.send-otp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile
                    })
                });

                const data = await response.json();

                if (data.success) {
                console.log(data.success);                    // Show OTP step
                    document.getElementById('otpStep1').classList.remove('active');
                    document.getElementById('otpStep2').classList.add('active');

                    document.getElementById('otpSentMsg').innerHTML = `✅ ${data.message || 'OTP sent successfully!'}`;

                    // Show OTP for testing (remove in production)
                    if (data.debug_otp) {
                        console.log('DEBUG OTP:', data.debug_otp);
                    }

                    // Focus first OTP input
                    document.querySelector('.otp-input[data-index="0"]').focus();
                } else {
                    alert(data.message || 'Failed to send OTP');
                }
            } catch (error) {
                alert('Error sending OTP. Please try again. Please verify your mobile number with admin: your mobile number is : ' +
                    currentMobile);
            }

            btn.disabled = false;
            btn.textContent = 'Send OTP →';
        }

        async function resendOtp() {
            if (!currentMobile) {
                alert('Please enter mobile number first you are login with : ' + currentMobile);
                return;
            }
            document.getElementById('otpMobile').value = currentMobile;
            await sendOtp();
        }

        async function verifyOtp() {
            const otp = Array.from(document.querySelectorAll('.otp-input')).map(i => i.value).join('');

            if (otp.length !== 6) {
                alert('Please enter complete 6-digit OTP');
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Verifying...';

            try {
                const response = await fetch('{{ route('superadmin.verify-otp') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        mobile: currentMobile,
                        otp
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Invalid or expired OTP');
                    // Clear OTP inputs
                    document.querySelectorAll('.otp-input').forEach(i => i.value = '');
                    document.querySelector('.otp-input[data-index="0"]').focus();
                }
            } catch (error) {
                alert('Error verifying OTP. Please try again.');
            }

            btn.disabled = false;
            btn.textContent = 'Verify & Login →';
        }
    

    function validateMobile(input) {
        input.value = input.value.replace(/[^0-9]/g, '').slice(0, 10);

        const sendBtn = document.getElementById('sendOtpBtn');

        if (input.value.length === 10) {
            sendBtn.disabled = false;
        } else {
            sendBtn.disabled = true;
        }
    }

    function toggleLoginMode() {
        document.getElementById('otpStep1').classList.toggle('active');
        document.getElementById('otpStep2').classList.remove('active');
        document.getElementById('passwordStep').classList.toggle('active');
        document.getElementById('toggleModeLink').classList.toggle('hidden-link');
    }

    async function passwordLogin() {
        const login = document.getElementById('pwLogin').value.trim();
        const password = document.getElementById('pwPassword').value;

        if (!login || !password) {
            alert('Please enter both ID and password.');
            return;
        }

        const btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Logging in...';

        try {
            const response = await fetch('{{ route('superadmin.login.password') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ login, password })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Login failed');
            }
        } catch (error) {
            alert('Error logging in. Please try again.');
        }

        btn.disabled = false;
        btn.textContent = 'Login →';
    }
</script>
</body>

</html>
