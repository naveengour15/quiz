{{-- ===========================================
     Quiz Schedule Timer Component
=========================================== --}}
@php
    $now         = now();
    $scheduledAt = $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at)  : null;
    // $availStart  = $quiz->availability_start ? \Carbon\Carbon::parse($quiz->availability_start) : null;
    $availStart  = $quiz->scheduled_at ? \Carbon\Carbon::parse($quiz->scheduled_at) : null;
    $availEnd    = $quiz->availability_end   ? \Carbon\Carbon::parse($quiz->availability_end)   : null;

    // LIVE hai? (availability_start aur availability_end ke bich)
    $isLive = $availStart
              && $now->gte($availStart)
              && (!$availEnd || $now->lte($availEnd));

    // Countdown target: availability_start, warna scheduled_at
    $target     = $availStart ?? $scheduledAt;
    $isUpcoming = !$isLive && $target && $target->isFuture();
@endphp

<td data-label="Schedule Time">
    @if($isLive)
        {{-- ✅ LIVE: Green "Started" Pill --}}
        <div class="schedule-timer">
            <div class="countdown-pill pill-live">
                <span class="live-dot"></span>
                <span class="countdown-time">Started</span>
            </div>
            @if($availEnd)
                <div class="schedule-date">Ends: {{ $availEnd->format('d M Y, h:i A') }}</div>
            @endif
        </div>

    @elseif($isUpcoming)
        {{--  UPCOMING: Yellow Countdown --}}
        <div class="schedule-timer"
             data-schedule="{{ $target->toISOString() }}"
             data-end="{{ $availEnd ? $availEnd->toISOString() : '' }}">
            <div class="countdown-pill">
                <span class="live-dot"></span>
                <span class="countdown-time" id="timer-{{ $quiz->id }}">--:--:--</span>
            </div>
            <div class="schedule-date">{{ $target->format('d M Y, h:i A') }}</div>
        </div>

    @elseif($scheduledAt)
        {{-- ⚪ PAST / ENDED: Sirf Schedule Time --}}
        <div class="schedule-date">{{ $scheduledAt->format('d M Y, h:i A') }}</div>

    @else
        <span class="badge success">Play Anytime</span>
    @endif
</td>


<style>
    /* Single-line Countdown Pill */
    .schedule-timer {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }

    .countdown-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background-color: #6366f1; /* 🔵 Indigo (New Color) */
        color: #fff;
        padding: 5px 14px;
        border-radius: 999px;
        white-space: nowrap;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    .live-dot {
        width: 6px;
        height: 6px;
        background: #fff;
        border-radius: 50%;
        animation: dotPulse 1s ease-in-out infinite;
    }

    @keyframes dotPulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.8); }
    }

    .countdown-time {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1px;
    }
        /* 🟡 Urgent: 1 hour left - Pure Yellow + Blink */
        .countdown-pill.urgent {
            background-color: #ffd700; /* Pure Yellow (Gold) */
            color: #451a03;            /* Dark text - yellow par white text nahi dikhta */
            animation: urgentBlink 1.5s ease-in-out infinite;
        }

        .countdown-pill.urgent .live-dot {
            background: #451a03;
        }
    .countdown-pill.urgent .live-dot {
        background: #fef3c7;
    }

    @keyframes urgentBlink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.75; }
    }

    /* 🟢 Live / Started: Solid Green Pill */
    .countdown-pill.pill-live {
        background-color: #22c55e;
        color: #fff;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    }

    .countdown-pill.pill-live .live-dot {
        background: #fff;
    }

    .schedule-date {
        font-size: 11px;
        color: var(--ink-faint);
    }
</style>
<script>
(function() {
    // ✅ Agar pehle se declare hai to skip karo (duplicate prevent)
    if (window.CountdownManager) return;

    window.CountdownManager = {
        timers: new Map(),

        init() {
            document.querySelectorAll('.schedule-timer[data-schedule]').forEach(el => {
                const timeEl = el.querySelector('.countdown-time');
                if (timeEl && el.dataset.schedule && !this.timers.has(timeEl.id)) {
                    this.startTimer(timeEl, el);
                }
            });
        },

        startTimer(timeEl, container) {
            const pill   = container.querySelector('.countdown-pill');
            const target = new Date(container.dataset.schedule).getTime();
            const end    = container.dataset.end ? new Date(container.dataset.end).getTime() : null;

            const update = () => {
                const now  = Date.now();
                const diff = target - now;

                if (diff <= 0) {
                    this.stopTimer(timeEl.id);
                    if (pill) pill.classList.remove('urgent');

                    // Availability window ke andar? -> Green "Started"
                    if (!end || now <= end) {
                        if (pill) {
                            pill.classList.add('pill-live');
                            pill.innerHTML = '<span class="live-dot"></span><span class="countdown-time">Started</span>';
                        }
                    } else {
                        // Window khatam -> Ended badge
                        if (pill) pill.outerHTML = '<span class="badge neutral">Ended</span>';
                    }
                    return;
                }

                if (timeEl) timeEl.textContent = this.format(diff);
                if (pill) pill.classList.toggle('urgent', diff < 3600000); // 1 hour left
            };

            update();
            this.timers.set(timeEl.id, setInterval(update, 1000));
        },

        format(diff) {
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            const pad = n => String(n).padStart(2, '0');
            return d > 0 ? `${d}d ${pad(h)}:${pad(m)}:${pad(s)}` : `${pad(h)}:${pad(m)}:${pad(s)}`;
        },

        stopTimer(id) {
            if (this.timers.has(id)) {
                clearInterval(this.timers.get(id));
                this.timers.delete(id);
            }
        }
    };

    // ✅ DOM Ready check (multiple calls handle karega)
    function initWhenReady() {
        if (window.CountdownManager) {
            window.CountdownManager.init();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenReady);
    } else {
        initWhenReady();
    }
})();
</script>
