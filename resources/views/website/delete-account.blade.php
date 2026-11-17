@include('website.layout.header')

    <section class="">
        <div class="pt-36 relative z-10 max-w-6xl mx-auto px-6 py-20">
            <h2 class="font-display font-extrabold leading-[1.05] text-3xl md:text-[32px]" style="color:#f5f0ff;">
                FSRQuiz —
                <span class="grad-text">Delete your account.</span>
            </h2>
            <h4 style="font-size: 22px; color: #ffd76a; font-weight: 700; margin-top: 16px;">
                App: FSRQuiz
            </h4>

            <p class="mt-6 text-[20px]" style="color: #f5f0ff;">
                You can ask us to delete your FSRQuiz account at any time. This page explains how to request deletion,
                what we remove, and what we may keep.
            </p>

            <div>
                <div class="list-info pt-6">
                    <h3 class="font-display font-bold text-[22px] mb-1.5" style="color:#e8ddff;">
                        1. How to request deletion
                    </h3>
                    <p class="text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        Send an email from any address to
                        <a href="mailto:support@chaloonline.in?subject=Delete%20my%20FSRQuiz%20account"
                            style="text-decoration: underline; color:#e8ddff;">support@chaloonline.in</a>
                        with the subject <strong style="color:#e8ddff;">Delete my FSRQuiz account</strong>.
                        Include the mobile number you use to log in to the app so we can find the right account.
                    </p>
                    <p class="mt-4 text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        We will deactivate the account and sign you out. You will not be able to log in with that
                        number after the account is deleted.
                    </p>
                    <a href="mailto:support@chaloonline.in?subject=Delete%20my%20FSRQuiz%20account&body=Please%20delete%20my%20FSRQuiz%20account.%0A%0ARegistered%20mobile%20number%3A%20"
                        class="btn-primary inline-flex items-center gap-2 mt-6 px-6 py-3 rounded-xl font-display font-bold text-[14px]">
                        <span class="material-symbols-outlined text-[18px]">mail</span>
                        Email a deletion request
                    </a>
                </div>

                <div class="list-info pt-6">
                    <h3 class="font-display font-bold text-[22px] mb-1.5" style="color:#e8ddff;">
                        2. Data that is deleted
                    </h3>
                    <ul>
                        <li class="mt-2 text-[16.5px] leading-relaxed"
                            style="color:rgba(232,221,255,0.65); list-style: disc; margin-left:40px">
                            Your login access and active session
                        </li>
                        <li class="mt-2 text-[16.5px] leading-relaxed"
                            style="color:rgba(232,221,255,0.65); list-style: disc; margin-left:40px">
                            Your profile, including name and profile photo, so it is no longer available in the app
                        </li>
                        <li class="mt-2 text-[16.5px] leading-relaxed"
                            style="color:rgba(232,221,255,0.65); list-style: disc; margin-left:40px">
                            Access to your points wallet, quiz history, and referral activity inside the app
                        </li>
                    </ul>
                </div>

                <div class="list-info pt-6">
                    <h3 class="font-display font-bold text-[22px] mb-1.5" style="color:#e8ddff;">
                        3. Data we may keep
                    </h3>
                    <p class="text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        Some records can stay for a limited time after deletion when the law, a dispute, or fraud
                        prevention requires it. That includes redemption and reward records, and security logs tied to
                        past activity. We do not use a deleted account to keep playing or to show you on the live app.
                    </p>
                </div>

                <div class="list-info pt-6">
                    <h3 class="font-display font-bold text-[22px] mb-1.5" style="color:#e8ddff;">
                        4. Partial deletion
                    </h3>
                    <p class="text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        If you want a specific piece of information removed without closing the whole account, use the
                        same email address and say which data you want deleted. We will tell you if a record must be
                        kept for a legal or fraud-prevention reason.
                    </p>
                </div>

                <div class="list-info pt-6">
                    <h3 class="font-display font-bold text-[22px] mb-1.5" style="color:#e8ddff;">
                        5. Contact
                    </h3>
                    <p class="text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        <strong style="color:#e8ddff;">Email</strong>:
                        <a href="mailto:support@chaloonline.in" style="text-decoration: underline; color:#e8ddff;">support@chaloonline.in</a>
                        —
                        <strong style="color:#e8ddff;">Address</strong>: 83A Narendra Tiwari Marg, Sudama Nagar, Indore
                    </p>
                    <p class="mt-4 text-[16.5px] leading-relaxed" style="color:rgba(232,221,255,0.65);">
                        More detail on how we handle personal information is in our
                        <a href="{{ route('website.privacy-policy') }}" style="text-decoration: underline; color:#e8ddff;">Privacy Policy</a>.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @include('website.layout.footer')
