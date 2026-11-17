<!-- ===== Quiz Categories Component ===== -->
<div class="flex items-center justify-between mb-3">
    <h2 class="font-display text-base font-bold" style="color:#e8ddff;">Quiz Categories</h2>
    <span class="text-[12px] font-semibold" style="color:#e879f9;">See all</span>
</div>
<div class="fsr-scroll-row mb-6">
    @if ($categories && $categories->count() > 0)
        @foreach ($categories as $category)
            <div class="fsr-cat-card" style="width:122px;">
                <div class="fsr-cat-icon"
                    style="background:linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08));">
                    <span class="material-symbols-outlined text-[19px]"
                        style="color:#ffd76a;">{{ $category->icon ?? 'public' }}</span>
                </div>
                <div>
                    <span class="text-[12px] font-semibold block truncate"
                        style="color:#e8ddff;">{{ $category->name }}</span>
                    <span class="fsr-count-badge mt-1 inline-block">{{ $category->quizzes_count ?? 0 }}
                        quizzes</span>
                </div>
            </div>
        @endforeach
    @else
        <div class="fsr-cat-card" style="width:122px;">
            <div class="fsr-cat-icon"
                style="background:linear-gradient(160deg, rgba(244,191,50,0.3), rgba(244,191,50,0.08));">
                <span class="material-symbols-outlined text-[19px]" style="color:#ffd76a;">public</span>
            </div>
            <div>
                <span class="text-[12px] font-semibold block" style="color:#e8ddff;">GK</span>
                <span class="fsr-count-badge mt-1 inline-block">0 quizzes</span>
            </div>
        </div>
    @endif
</div>
