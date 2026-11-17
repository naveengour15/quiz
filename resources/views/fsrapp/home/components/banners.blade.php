

<!-- ===== Reusable Banner Component ===== -->

 @php
    $safeBanners = (isset($banners) && is_object($banners) && !($banners instanceof \__PHP_Incomplete_Class) && method_exists($banners, 'where'))
        ? $banners
        : (class_exists(\App\Models\Banner::class) ? \App\Models\Banner::where('status', 'active')->latest()->get() : collect());

    $activeBanners = isset($pageType)
        ? $safeBanners->where('status', 'active')->filter(function ($banner) use ($pageType) {
            $types = is_array($banner->type) ? $banner->type : [];
            // Show ALL banners everywhere + current page banner
            return in_array('all', $types) || in_array($pageType, $types);
        })->values()
        : collect();
@endphp

@if ($activeBanners->count() > 0)
    <section class="mb-4 banner-section" data-page-type="{{ $pageType }}">
        <div class="relative rounded-2xl banner-track">

            <div class="flex overflow-x-auto scrollbar-hide snap-x snap-mandatory rounded-2xl"
                data-banner-scroller
                style="scroll-behavior: smooth;">

                @foreach ($activeBanners as $index => $banner)
                    @php
                        $quizStageTypes = [
                            'question',
                            'start-counter',
                            'review-counter',
                            'waiting',
                            'final-counter',
                            'quiz_start_10s_count',
                            'before_answer_count',
                            'before_quiz_start_count',
                            'quiz_answer_sheet',
                            'practices_start',
                            'before_practice_count',
                            'practice_before_answer_sheet',
                            'practice_answer_sheet'
                        ];
                        $isQuizStageBanner = !empty($disableClick) || in_array($pageType ?? '', $quizStageTypes);
                    @endphp

                    @if ($isQuizStageBanner)
                        <div class="banner-slide w-full shrink-0 snap-center select-none pointer-events-none cursor-default {{ $banner->banner_file ? '' : 'h-[180px] max-h-[180px]' }}" data-banner-id="{{ $banner->id }}" data-link="{{ $banner->url ?? '' }}">
                    @else
                        {{-- <div class="banner-slide w-full shrink-0 snap-center cursor-pointer" data-banner-id="{{ $banner->id }}" data-link="{{ $banner->url ?? '' }}" onclick="window.location.href='{{ route('fsrapp.banner.click', ['banner' => $banner->id, 'type' => $pageType]) }}'">  --}}
                        <div class="banner-slide w-full shrink-0 snap-center cursor-pointer {{ $banner->banner_file ? '' : 'h-[180px] max-h-[180px]' }}" data-banner-id="{{ $banner->id }}" data-link="{{ $banner->url ?? '' }}" data-banner-click-endpoint="{{ route('fsrapp.banner.click', ['banner' => $banner->id, 'type' => $pageType]) }}">
                    @endif 
                        <div class="relative w-full rounded-2xl overflow-hidden glass-card flex items-center justify-center bg-white/[0.02] {{ $banner->banner_file ? '' : 'h-[180px] max-h-[180px]' }}">

                            @if ($banner->banner_file)
                                @php
                                    $imgUrl = Str::startsWith($banner->banner_file, ['http://', 'https://'])
                                        ? $banner->banner_file
                                        : rtrim(
                                            env('AWS_URL', 'https://chaloonline.s3.ap-south-1.amazonaws.com'),
                                            '/'
                                        ) . '/' . ltrim($banner->banner_file, '/');
                                @endphp

                                <img src="{{ $imgUrl }}"
                                    alt="{{ $banner->name }}"
                                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                    {!! $index === 0 ? 'fetchpriority="high"' : 'decoding="async"' !!}
                                    class="block w-full h-auto object-contain" style="border-radius:16px;">
                            @else
                                <div class="w-full h-full vibrant-gradient" style="border-radius:16px;"></div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>

            @if ($activeBanners->count() > 1)
                <div class="flex justify-center gap-1.5 mt-2" data-banner-dots>

                    @foreach ($activeBanners as $index => $banner)
                        <span
                            class="w-2 h-2 rounded-full transition-all duration-300 {{ $index === 0 ? 'w-5' : 'bg-white/30' }} banner-dot"
                            style="{{ $index === 0 ? 'background:#f4bf32;' : '' }}"
                            data-i="{{ $index }}">
                        </span>
                    @endforeach

                </div>
            @endif

        </div>
    </section>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            document.querySelectorAll('.banner-section').forEach((bannerSection) => {
                if (bannerSection.dataset.trackingReady === 'true') return;
                bannerSection.dataset.trackingReady = 'true';

                const pageType = bannerSection.dataset.pageType;
                const bannerScroller = bannerSection.querySelector('[data-banner-scroller]');
                const bannerDots = bannerSection.querySelectorAll('[data-banner-dots] .banner-dot');

                bannerSection.querySelectorAll('[data-banner-click-endpoint]').forEach((slide) => {
                    slide.addEventListener('click', async () => {
                        if (slide.dataset.clickPending === 'true') return;
                        slide.dataset.clickPending = 'true';

                        try {
                            const response = await fetch(slide.dataset.bannerClickEndpoint, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            });

                            if (!response.ok) throw new Error('Banner click tracking failed');

                            const result = await response.json();
                            if (result.url) {
                                window.location.assign(result.url);
                                return;
                            }

                            slide.dataset.clickPending = 'false';
                        } catch (error) {
                            slide.dataset.clickPending = 'false';
                        }
                    });
                });

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                            const slide = entry.target;
                            if (slide.dataset.viewTracked === 'true') return;
                            slide.dataset.viewTracked = 'pending';
                            fetch('{{ url('/fsrapp/banner/view') }}/' + slide.dataset.bannerId + '/' + encodeURIComponent(pageType), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin'
                            }).then((response) => {
                                if (!response.ok) throw new Error('Banner view tracking failed');
                                slide.dataset.viewTracked = 'true';
                            }).catch(() => {
                                slide.dataset.viewTracked = 'false';
                            });
                        }
                    });
                }, {
                    root: bannerScroller,
                    threshold: 0.5
                });

                bannerSection.querySelectorAll('[data-banner-id]').forEach((slide) => observer.observe(slide));

                if (bannerScroller && bannerDots.length > 1) {
                let currentIndex = 0;
                const totalSlides = bannerDots.length;
                let isTicking = false;

                const updateDots = () => {
                    const slideWidth = bannerScroller.clientWidth || 1;
                    const activeIndex = Math.min(totalSlides - 1, Math.max(0, Math.round(bannerScroller.scrollLeft / slideWidth)));
                    currentIndex = activeIndex;

                    bannerDots.forEach((dot, index) => {
                        if (index === activeIndex) {
                            dot.classList.add('w-5');
                            dot.classList.remove('bg-white/30');
                            dot.style.background = '#f4bf32';
                        } else {
                            dot.classList.remove('w-5');
                            dot.classList.add('bg-white/30');
                            dot.style.background = '';
                        }
                    });
                    isTicking = false;
                };

                bannerScroller.addEventListener('scroll', () => {
                    if (!isTicking) {
                        requestAnimationFrame(updateDots);
                        isTicking = true;
                    }
                }, { passive: true });

                // Auto slide every 3.5s when page is active
                let autoSlide = setInterval(() => {
                    if (document.hidden) return;
                    currentIndex = (currentIndex + 1) % totalSlides;
                    bannerScroller.scrollTo({
                        left: currentIndex * (bannerScroller.clientWidth || 1),
                        behavior: 'smooth'
                    });
                }, 3500);

                bannerScroller.addEventListener('touchstart', () => {
                    clearInterval(autoSlide);
                }, { passive: true });

                bannerScroller.addEventListener('touchend', () => {
                    autoSlide = setInterval(() => {
                        if (document.hidden) return;
                        currentIndex = (currentIndex + 1) % totalSlides;
                        bannerScroller.scrollTo({
                            left: currentIndex * (bannerScroller.clientWidth || 1),
                            behavior: 'smooth'
                        });
                    }, 3500);
                }, { passive: true });
                }
            });
        });
    </script>
    @endpush
@endif 
