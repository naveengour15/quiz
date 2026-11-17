@extends('admin.layouts.app')
@section('title', $editing ? 'Edit Quiz' : 'Create Quiz')

@push('styles')
    <style>
        /* ═══════════════════════════════════════════════
               CUSTOM TABS CSS
            ═══════════════════════════════════════════════ */
        .custom-tabs-wrapper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .custom-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .custom-tab-link {
            flex: 1;
            text-align: center;
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .custom-tab-link:hover {
            color: #495057;
            background: #e9ecef;
        }

        .custom-tab-link.active {
            color: #0d6efd;
            background: #fff;
            border-bottom-color: #0d6efd;
        }

        .custom-tab-link.disabled-tab {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f1f3f5;
        }

        .custom-tab-link.disabled-tab:hover {
            background: #f1f3f5;
            color: #6c757d;
        }

        .custom-tab-link i {
            font-size: 16px;
        }

        .custom-tab-content {
            padding: 30px;
            background: #fff;
            min-height: 400px;
        }

        .custom-tab-pane {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .custom-tab-pane.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: fadeInTab 0.4s ease forwards;
        }

        @keyframes fadeInTab {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .custom-tabs {
                flex-direction: column;
            }

            .custom-tab-link {
                border-bottom: 1px solid #e9ecef;
                border-left: 3px solid transparent;
                justify-content: flex-start;
                padding: 14px 20px;
            }

            .custom-tab-link.active {
                border-bottom: 1px solid #e9ecef;
                border-left-color: #0d6efd;
            }
        }
    </style>
@endpush

@section('content')

    @php
        $quizId = $editing?->id ?? request()->query('editing');
        $isEditing = !empty($quizId);
        $isFff = ($editing?->visibility === 'fastest_finger_first');
    @endphp

    <div class="container-fluid">

        <div class="custom-tabs-wrapper">
            <!-- Tabs Navigation -->
            <ul class="custom-tabs" id="quizTabs">
                <li class="nav-item" role="presentation">
                    <a class="custom-tab-link active" data-target="quiz" href="#quiz">
                        <i class="fas fa-info-circle"></i> 1. Quiz Details
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="custom-tab-link {{ !$isEditing ? 'disabled-tab' : '' }}" data-target="questions" href="#questions" {{ !$isEditing ? 'data-disabled=true' : '' }}>
                        <i class="fas fa-question-circle"></i> 2. Select Questions
                    </a>
                </li>
                @if(!$isFff)
                <li class="nav-item" role="presentation">
                    <a class="custom-tab-link {{ !$isEditing ? 'disabled-tab' : '' }}" data-target="rewards" href="#rewards" {{ !$isEditing ? 'data-disabled=true' : '' }}>
                        <i class="fas fa-gift"></i> 3. Rewards
                    </a>
                </li>
                @endif
            </ul>

            <!-- Tabs Content -->
            <div class="custom-tab-content" id="quizTabsContent">

                <!-- Tab 1: Quiz Details -->
                <div class="custom-tab-pane active" id="quiz">
                    @include('admin.quizzes.component.create-quiz')
                </div>

                <!-- Tab 2: Select Questions -->
                <div class="custom-tab-pane" id="questions">
                    @include('admin.quizzes.component.select_question')
                </div>

                @if(!$isFff)
                <!-- Tab 3: Rewards -->
                <div class="custom-tab-pane" id="rewards">
                    @include('admin.quizzes.component.rewards-quiz')
                </div>
                @endif

            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabLinks = document.querySelectorAll('.custom-tab-link');
    const tabPanes = document.querySelectorAll('.custom-tab-pane');

    function activateTab(targetId) {
        // 1. Remove active class from all links and panes
        tabLinks.forEach(link => link.classList.remove('active'));
        tabPanes.forEach(pane => pane.classList.remove('active'));

        // 2. Add active class to the target link and pane
        const targetLink = document.querySelector(`.custom-tab-link[data-target="${targetId}"]`);
        const targetPane = document.getElementById(targetId);

        if (targetLink && targetPane) {
            targetLink.classList.add('active');
            targetPane.classList.add('active');
            
            // Update URL hash without adding to browser history stack
            history.replaceState(null, null, `#${targetId}`);
        }
    }

    // Click event listener for tabs
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault(); 

            if (this.dataset.disabled === "true") {
                if (typeof showAlert === 'function') {
                    showAlert('warning', 'Step Locked', 'Please complete and save Step 1 (Quiz Details) first!');
                } else {
                    alert('Please complete and save Step 1 (Quiz Details) first!');
                }
                return;
            }

            const targetId = this.getAttribute('data-target');
            activateTab(targetId);
        });
    });

    // Smart Tab Activation Logic
    const hash = window.location.hash.replace('#', '');
    const activeTabFromSession = "{{ session('active_tab', '') }}";
    
    if (hash && document.getElementById(hash) && (!document.querySelector(`.custom-tab-link[data-target="${hash}"]`) || document.querySelector(`.custom-tab-link[data-target="${hash}"]`).dataset.disabled !== "true")) {
        activateTab(hash);
    } else if (activeTabFromSession && document.getElementById(activeTabFromSession)) {
        activateTab(activeTabFromSession);
    } else {
        @if ($isEditing)
            activateTab('questions');
        @else
            activateTab('quiz');
        @endif
    }
});
</script>
@endpush
