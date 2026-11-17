@extends('superadmin.layouts.app')

@section('title', 'Leaderboard')
<link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

@section('content')
@push('styles')
<style>
    .leader-badge-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 14px;
        background: #f3f4f6;
        color: #374151;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    .leader-badge-rank.gold { 
        background: linear-gradient(135deg, #fde68a, #fbbf24); 
        color: #78350f;
        box-shadow: 0 4px 12px rgba(251, 191, 36, 0.4);
    }
    .leader-badge-rank.silver { 
        background: linear-gradient(135deg, #e5e7eb, #9ca3af); 
        color: #1f2937;
        box-shadow: 0 4px 8px rgba(148, 163, 184, 0.3);
    }
    .leader-badge-rank.bronze { 
        background: linear-gradient(135deg, #fed7aa, #fb923c); 
        color: #7c2d12;
        box-shadow: 0 4px 8px rgba(251, 146, 60, 0.3);
    }
    .leader-badge-rank.empty { 
        background: #f1f5f9; 
        color: #94a3b8;
        border: 2px dashed #e2e8f0;
    }
    
    .leader-stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .leader-stat-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 18px;
        position: relative;
        overflow: hidden;
        transition: all 0.3s;
    }
    
    .leader-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--indigo), #a855f7);
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .leader-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    
    .leader-stat-card:hover::before {
        opacity: 1;
    }
    
    .leader-stat-card .lbl {
        font-size: 11px;
        font-weight: 700;
        color: var(--ink-faint);
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .leader-stat-card .val {
        font-size: 24px;
        font-weight: 800;
        color: var(--ink);
    }
    
    .cell-user {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: center;
    }
    
    .user-thumb {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid var(--line);
    }
    
    .cell-av {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--indigo), #7C3AED);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 15px;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
    }
    
    .user-info-box {
        text-align: left;
    }
    
    .user-info-box b {
        font-size: 14px;
        color: var(--ink);
    }
    
    .user-info-box p {
        color: var(--ink-soft);
        font-size: 12px;
        margin: 2px 0;
    }
    
    .user-info-box small {
        color: var(--ink-faint);
        font-size: 11px;
    }
    
    .stat-mini {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    
    .stat-mini.reward { 
        background: #ede9fe; 
        color: #6d28d9; 
        border: 1px solid #c4b5fd; 
    }
    
    .podium-row {
        display: grid;
        grid-template-columns: 1fr 1.3fr 1fr;
        gap: 16px;
        margin-bottom: 28px;
        align-items: end;
    }
    
    .podium-card {
        background: #fff;
        border: 2px solid var(--line);
        border-radius: 16px;
        padding: 20px 16px;
        text-align: center;
        position: relative;
        transition: all 0.3s;
    }
    
    .podium-card:hover { 
        transform: translateY(-6px);
        box-shadow: 0 12px 32px rgba(0,0,0,0.12);
    }
    
    .podium-card.gold { 
        border-color: #fbbf24; 
        box-shadow: 0 8px 24px rgba(251, 191, 36, 0.25); 
        margin-top: -24px;
        padding: 24px 16px;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%);
    }
    
    .podium-card.silver { 
        border-color: #9ca3af;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }
    
    .podium-card.bronze { 
        border-color: #fb923c;
        background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    }
    
    .podium-medal {
        font-size: 42px;
        display: block;
        margin-bottom: 10px;
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.15));
    }
    
    .podium-name {
        font-size: 15px;
        font-weight: 700;
        color: var(--ink);
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .podium-points {
        display: inline-block;
        padding: 4px 14px;
        background: linear-gradient(135deg, var(--indigo), #7C3AED);
        color: #fff;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 800;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
    }

    .empty-rank-row {
        background: #fafbfc;
        opacity: 0.6;
    }
    
    .empty-rank-row:hover {
        opacity: 0.85;
    }
    
    .empty-rank-row td {
        color: #94a3b8 !important;
        font-style: italic;
    }

    .blank-badge {
        display: inline-block;
        padding: 6px 14px;
        background: #f1f5f9;
        color: #94a3b8;
        border-radius: 8px;
        font-size: 12px;
        font-style: italic;
        border: 1px dashed #cbd5e1;
    }

    .users-table-wrapper tbody tr {
        transition: all 0.2s;
    }
    
    .users-table-wrapper tbody tr:not(.empty-rank-row):hover {
        background: linear-gradient(90deg, rgba(124, 58, 237, 0.02) 0%, rgba(79, 70, 229, 0.05) 100%);
    }

    .user-points-display {
        display: inline-flex;
        align-items: baseline;
        gap: 3px;
    }
    
    .user-points-display b {
        color: var(--indigo);
        font-size: 16px;
        font-weight: 800;
    }
    
    .user-points-display span {
        font-size: 11px;
        color: var(--ink-soft);
    }

    /* Sponsored By Edit Button */
    .sponsored-cell {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .edit-sponsor-btn {
        background: none;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 4px 8px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
        color: #6b7280;
    }

    .edit-sponsor-btn:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
        color: #374151;
    }

    /* Modal Styles */
    .sponsor-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .sponsor-modal.active {
        display: flex;
    }

    .sponsor-modal-content {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .sponsor-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .sponsor-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: var(--ink);
    }

    .sponsor-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #9ca3af;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .sponsor-modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }

    .sponsor-input {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        margin-bottom: 16px;
        transition: all 0.2s;
    }

    .sponsor-input:focus {
        outline: none;
        border-color: var(--indigo);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .sponsor-modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
           .leaderboard-search-filter-box{
                width:100%;
                display: flex;
                gap: 10px;
                align-items: flex-end;
            }

            .leaderboard-search-filter-wrapper{
                width:100%;
                display: grid;
                grid-template-columns: repeat(1, 1fr);
                gap: 10px;
            }

            .leaderboard-filter-btn{
                width: 100%;
                display: flex;
                gap: 10px;
            }

            .leaderboard-filter-btn .btn{
              width: 100%;
              display: flex;
              justify-content: center;
            }

    @media(max-width: 991px) {
        .leader-stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 767px) {
        .podium-row { 
            grid-template-columns: 1fr; 
            gap: 12px;
        }
        
        .podium-card.gold { 
            margin-top: 0; 
            order: -1;
        }
        
        .leader-stats-row { 
            grid-template-columns: 1fr; 
        }
        
        .podium-medal {
            font-size: 36px;
        }
    }
    
    @media(max-width:575px){
        .leaderboard-search-filter-box{
             flex-direction: column;
        }
    }
</style>
@endpush

<div class="panel active">
    
    {{-- Header --}}
    <div class="page-head" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
        <div>
            <h1>🏆 Leaderboard</h1>
            <p style="color: var(--ink-soft); font-size: 14px; margin-top: 4px;">
                Complete leaderboard rankings with reward distribution
            </p>
        </div>
        <div>
            <button type="button" id="updateUserLeaderboardBtn" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px;">
                <span id="lbUpdateBtnIcon">⚡</span>
                <span id="lbUpdateBtnText">Update Leaderboard</span>
            </button>
        </div>
    </div>

    {{-- Top 3 Podium --}}
    @if($top3->count() > 0)
    <div class="podium-row">
        @if(isset($top3[1]))
        <div class="podium-card silver">
            <span class="podium-medal">🥈</span>
            <div class="podium-name">{{ $top3[1]->name }}</div>
            <div class="podium-points">{{ number_format($top3[1]->lb_points) }} pts</div>
        </div>
        @else <div></div> @endif

        @if(isset($top3[0]))
        <div class="podium-card gold">
            <span class="podium-medal">🥇</span>
            <div class="podium-name">{{ $top3[0]->name }}</div>
            <div class="podium-points">{{ number_format($top3[0]->lb_points) }} pts</div>
        </div>
        @else <div></div> @endif

        @if(isset($top3[2]))
        <div class="podium-card bronze">
            <span class="podium-medal">🥉</span>
            <div class="podium-name">{{ $top3[2]->name }}</div>
            <div class="podium-points">{{ number_format($top3[2]->lb_points) }} pts</div>
        </div>
        @else <div></div> @endif
    </div>
    @endif

    {{-- Summary Stats --}}
    <div class="leader-stats-row">
        <div class="leader-stat-card">
            <div class="lbl"><span>📊</span> Total Ranks</div>
            <div class="val">{{ number_format($totalEntries) }}</div>
        </div>
        <div class="leader-stat-card">
            <div class="lbl"><span>👥</span> Filled Ranks</div>
            <div class="val">{{ number_format($totalPlayers) }}</div>
        </div>
        <div class="leader-stat-card">
            <div class="lbl"><span>⭐</span> Top Player</div>
            <div class="val">{{ number_format($top3[0]->lb_points ?? 0) }}</div>
        </div>
        <div class="leader-stat-card">
            <div class="lbl"><span>💎</span> Points Distributed</div>
            <div class="val">{{ number_format($totalPointsDistributed) }}</div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="search-filter-box">
        <form method="GET" class="toolbar">
            <div class="leaderboard-search-filter-box">
                <div class="leaderboard-search-filter-wrapper">
                    <div class="input-field">
                        <label class="lbl" for="search">Search Player</label>
                        <input class="tb-input" name="search" value="{{ request('search') }}" placeholder="Rank, Name, email, mobile…">
                    </div>
                </div>
                <div class="leaderboard-filter-btn">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('superadmin.leaderboard.index') }}" class="btn btn-ghost">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Leaderboard Table --}}
    <div class="table-wrap table-wrap-res-01">
        <div class="users-table-box">
            <table class="users-table-wrapper">
                <thead>
                    <tr>
                        <th style="width:70px;">Rank</th>
                        <th style="text-align:center;">Reward</th>
                        <th style="text-align:center;">Username</th>
                        <th style="text-align:center;">Leaderboard Marks</th>
                        <th style="text-align:center;">Sponsored By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaderboard as $entry)
                        @if($entry->has_user)
                            <tr data-entry-id="{{ $entry->lb_id }}">
                                <td data-label="Rank" style="text-align:center;">
                                    @if($entry->lb_rank == 1)
                                        <span class="leader-badge-rank gold">🥇</span>
                                    @elseif($entry->lb_rank == 2)
                                        <span class="leader-badge-rank silver">🥈</span>
                                    @elseif($entry->lb_rank == 3)
                                        <span class="leader-badge-rank bronze">🥉</span>
                                    @else
                                        <span class="leader-badge-rank">#{{ $entry->lb_rank }}</span>
                                    @endif
                                </td>

                                <td data-label="Reward" style="text-align:center;">
                                    <span class="stat-mini reward">
                                        {{ number_format($entry->lb_points ?? 0) }}
                                    </span>
                                </td>

                                <td data-label="Username" style="text-align:center;">
                                    <div class="cell-user">
                                        <div class="user-info-box">
                                            <b>{{ $entry->name }}</b>
                                            <p>{{ $entry->mobile ?? '—' }}</p>
                                            <small>{{ $entry->email ?? '—' }}</small>
                                            <div style="margin-top: 4px;">
                                                <span class="badge info" style="font-size:10px;">
                                                    📅 {{ $entry->created_at ? \Carbon\Carbon::parse($entry->created_at)->format('d M Y') : '—' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td data-label="Leaderboard Point" style="text-align:center;">
                                    <div class="user-points-display">
                                        <b>{{ number_format($entry->leader_board_point ?? 0) }}</b>
                                        <span></span>
                                    </div>
                                </td>

                                <td data-label="Sponsored By" style="text-align:center;">
                                    <div class="sponsored-cell">
                                        <span class="badge warn sponsored-text" style="font-size:11px;">
                                            {{ $entry->sponsored_by ?? 'Chalo Online' }}
                                        </span>
                                        <button class="edit-sponsor-btn" 
                                                data-id="{{ $entry->lb_id }}"
                                                data-current="{{ $entry->sponsored_by ?? 'Chalo Online' }}">
                                            ✏️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr class="empty-rank-row" data-entry-id="{{ $entry->lb_id }}">
                                <td data-label="Rank" style="text-align:center;">
                                    <span class="leader-badge-rank empty">#{{ $entry->lb_rank }}</span>
                                </td>
                                <td data-label="Reward" style="text-align:center;">
                                    <span style="color:#94a3b8; font-size:13px;">
                                        {{ number_format($entry->lb_points ?? 0) }} 
                                    </span>
                                </td>
                                <td data-label="Username" style="text-align:center;">
                                    <span class="blank-badge">🚫 No player assigned</span>
                                </td>
                                <td data-label="Leaderboard Point" style="text-align:center;">
                                    <span style="color:#94a3b8; font-size:13px;">0</span>
                                </td>
                                <td data-label="Sponsored By" style="text-align:center;">
                                    <div class="sponsored-cell">
                                        <span class="badge warn sponsored-text" style="font-size:11px;">
                                            {{ $entry->sponsored_by ?? 'Chalo Online' }}
                                        </span>
                                        <button class="edit-sponsor-btn" 
                                                data-id="{{ $entry->lb_id }}"
                                                data-current="{{ $entry->sponsored_by ?? 'Chalo Online' }}">
                                            ✏️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" style="text-align:center; color:var(--ink-faint); padding:60px 40px;">
                                <div style="font-size:48px; opacity:0.3; margin-bottom:16px;">🏆</div>
                                <div style="font-size:16px; font-weight:600; margin-bottom:8px;">No leaderboard entries found</div>
                                <div style="font-size:13px;">Try adjusting your filters or check back later</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            @include('common.pagination.superadmin_pagination', ['pageData' => $leaderboard])
        </div>
    </div>
</div>

{{-- Sponsored By Update Modal --}}
<div class="sponsor-modal" id="sponsorModal">
    <div class="sponsor-modal-content">
        <div class="sponsor-modal-header">
            <h3>Update Sponsored By</h3>
            <button class="sponsor-modal-close" id="closeSponsorModal">&times;</button>
        </div>
        <form id="sponsorForm">
            <input type="hidden" id="sponsorEntryId" name="entry_id">
            <input type="text" 
                   class="sponsor-input" 
                   id="sponsorInput" 
                   name="sponsored_by" 
                   placeholder="Enter sponsor name..."
                   required>
            <div class="sponsor-modal-actions">
                <button type="button" class="btn btn-ghost" id="cancelSponsor">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Leaderboard script loaded');
    
    // Open modal on edit button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-sponsor-btn')) {
            const btn = e.target.closest('.edit-sponsor-btn');
            const entryId = btn.getAttribute('data-id');
            const currentValue = btn.getAttribute('data-current');
            
            console.log('Edit clicked - ID:', entryId, 'Value:', currentValue);
            
            document.getElementById('sponsorEntryId').value = entryId;
            document.getElementById('sponsorInput').value = currentValue;
            document.getElementById('sponsorModal').classList.add('active');
            
            setTimeout(() => {
                document.getElementById('sponsorInput').focus();
            }, 100);
        }
    });
    
    // Close modal functions
    function closeModal() {
        document.getElementById('sponsorModal').classList.remove('active');
        document.getElementById('sponsorForm').reset();
    }
    
    // Close on close button
    document.getElementById('closeSponsorModal').addEventListener('click', closeModal);
    document.getElementById('cancelSponsor').addEventListener('click', closeModal);
    
    // Close on backdrop click
    document.getElementById('sponsorModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('sponsorModal').classList.contains('active')) {
            closeModal();
        }
    });
    
    // Submit form via AJAX
    document.getElementById('sponsorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const entryId = document.getElementById('sponsorEntryId').value;
        const sponsoredBy = document.getElementById('sponsorInput').value;
        
        console.log('Submitting - ID:', entryId, 'Sponsor:', sponsoredBy);
        
        fetch('{{ route("superadmin.leaderboard.updateSponsor") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                entry_id: entryId,
                sponsored_by: sponsoredBy
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            
            if (data.success) {
                // Update the badge text
                const row = document.querySelector(`tr[data-entry-id="${entryId}"]`);
                if (row) {
                    const badge = row.querySelector('.sponsored-text');
                    if (badge) {
                        badge.textContent = sponsoredBy;
                    }
                    
                    const editBtn = row.querySelector('.edit-sponsor-btn');
                    if (editBtn) {
                        editBtn.setAttribute('data-current', sponsoredBy);
                    }
                }
                
                // Show success alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Sponsor updated successfully!',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else if (typeof showAlert === 'function') {
                    showAlert('success', 'Success', 'Sponsor updated successfully!');
                } else {
                    alert('✅ Sponsor updated successfully!');
                }
                
                closeModal();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to update sponsor',
                        icon: 'error'
                    });
                } else {
                    alert('❌ ' + (data.message || 'Failed to update sponsor'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error'
                });
            } else {
                alert('❌ Something went wrong. Please try again.');
            }
        });
    });

    // Update User Leaderboard SweetAlert AJAX call
    const updateLbBtn = document.getElementById('updateUserLeaderboardBtn');
    if (updateLbBtn) {
        updateLbBtn.addEventListener('click', function() {
            const executeUpdate = function() {
                const iconSpan = document.getElementById('lbUpdateBtnIcon');
                const textSpan = document.getElementById('lbUpdateBtnText');
                
                updateLbBtn.disabled = true;
                updateLbBtn.style.opacity = '0.7';
                if (iconSpan) iconSpan.textContent = '⏳';
                if (textSpan) textSpan.textContent = 'Updating...';

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Updating Leaderboard...',
                        text: 'Please wait while rankings are being recalculated.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                }

                fetch('{{ route("superadmin.leaderboard.updateUserLeaderboard") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message || 'User leaderboard updated successfully!',
                                icon: 'success',
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            if (typeof showAlert === 'function') {
                                showAlert('success', 'Success', data.message || 'User leaderboard updated successfully!');
                            } else {
                                alert('✅ ' + (data.message || 'User leaderboard updated successfully!'));
                            }
                            setTimeout(() => window.location.reload(), 1200);
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message || 'Failed to update leaderboard',
                                icon: 'error'
                            });
                        } else {
                            alert('❌ ' + (data.message || 'Failed to update leaderboard'));
                        }
                        updateLbBtn.disabled = false;
                        updateLbBtn.style.opacity = '1';
                        if (iconSpan) iconSpan.textContent = '⚡';
                        if (textSpan) textSpan.textContent = 'Update Leaderboard';
                    }
                })
                .catch(error => {
                    console.error('Error updating leaderboard:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Something went wrong while updating leaderboard.',
                            icon: 'error'
                        });
                    } else {
                        alert('❌ Something went wrong while updating leaderboard.');
                    }
                    updateLbBtn.disabled = false;
                    updateLbBtn.style.opacity = '1';
                    if (iconSpan) iconSpan.textContent = '⚡';
                    if (textSpan) textSpan.textContent = 'Update Leaderboard';
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Recalculate Leaderboard?',
                    text: 'Are you sure you want to recalculate and update user leaderboard rankings?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, Update Now!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        executeUpdate();
                    }
                });
            } else {
                if (confirm('Are you sure you want to recalculate and update user leaderboard rankings?')) {
                    executeUpdate();
                }
            }
        });
    }
});
</script>
@endpush