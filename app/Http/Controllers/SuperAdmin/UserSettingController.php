<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\ElectricityZone;
use App\Models\QuizColor;
use App\Models\QuizGame;
use App\Models\QuizHobby;
use App\Models\QuizInterest;
use App\Models\SimNetwork;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserSettingController extends Controller
{
    private const TAXONOMIES = [
        'hobby' => [
            'label' => 'Hobby',
            'plural' => 'Hobbies',
            'table' => 'quiz_hobby',
            'model' => QuizHobby::class,
            'icon' => '🎨',
        ],
        'game' => [
            'label' => 'Game',
            'plural' => 'Games',
            'table' => 'quiz_game',
            'model' => QuizGame::class,
            'icon' => '🎮',
        ],
        'interest' => [
            'label' => 'Interest',
            'plural' => 'Interests',
            'table' => 'quiz_interest',
            'model' => QuizInterest::class,
            'icon' => '✨',
        ],
        'color' => [
            'label' => 'Color',
            'plural' => 'Colors',
            'table' => 'quiz_color',
            'model' => QuizColor::class,
            'icon' => '🌈',
        ],
        'states' => [
            'label' => 'State',
            'plural' => 'States',
            'table' => 'states',
            'model' => State::class,
            'icon' => '🗺️',
        ],
        'cities' => [
            'label' => 'City',
            'plural' => 'Cities',
            'table' => 'cities',
            'model' => City::class,
            'icon' => '🏙️',
        ],
        'sim_networks' => [
            'label' => 'SIM Network',
            'plural' => 'SIM Networks',
            'table' => 'sim_networks',
            'model' => SimNetwork::class,
            'icon' => '📶',
        ],
        'electricity_zones' => [
            'label' => 'Electricity Zone',
            'plural' => 'Electricity Zones',
            'table' => 'electricity_zones',
            'model' => ElectricityZone::class,
            'icon' => '⚡',
        ],
    ];

    private const TYPE_ALIASES = [
        'state' => 'states',
        'city' => 'cities',
        'sim_network' => 'sim_networks',
        'electricity_zone' => 'electricity_zones',
    ];

    public function index(Request $request)
    {
        $rawType = $request->query('type', 'hobby');
        $type = $this->normalizeType($rawType);
        $taxonomy = $this->taxonomy($type);
        $model = $taxonomy['model'];

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', Rule::in([2, 5, 10, 20, 30, 50, 100, 200, 500])],
            'state_id' => ['nullable', 'integer'],
        ]);
        $search = trim($validated['search'] ?? '');
        $stateFilter = $validated['state_id'] ?? null;

        $itemsQuery = $model::query();

        if ($type === 'cities') {
            $itemsQuery->with('state');
            if (!empty($stateFilter)) {
                $itemsQuery->where('state_id', $stateFilter);
            }
            if ($search !== '') {
                $itemsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('state', function ($sq) use ($search) {
                            $sq->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
        } elseif ($type === 'electricity_zones') {
            $itemsQuery->with('state');
            if (!empty($stateFilter)) {
                $itemsQuery->where('state_id', $stateFilter);
            }
            if ($search !== '') {
                $itemsQuery->where(function ($q) use ($search) {
                    $q->where('zone', 'like', '%' . $search . '%')
                        ->orWhere('zone_code', 'like', '%' . $search . '%')
                        ->orWhereHas('state', function ($sq) use ($search) {
                            $sq->where('name', 'like', '%' . $search . '%');
                        });
                });
            }
        } elseif ($type === 'sim_networks') {
            if ($search !== '') {
                $itemsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            }
        } else {
            // states, hobby, game, interest, color
            if ($search !== '') {
                $itemsQuery->where('name', 'like', '%' . $search . '%');
            }
        }

        $items = $itemsQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        $editing = $request->filled('edit')
            ? $model::query()->findOrFail($request->query('edit'))
            : null;

        $states = in_array($type, ['cities', 'electricity_zones'], true)
            ? State::where('status', 'active')->orderBy('name')->get()
            : collect();

        if ($editing && isset($editing->state_id) && !$states->contains('id', $editing->state_id)) {
            $editState = State::find($editing->state_id);
            if ($editState) {
                $states->push($editState);
                $states = $states->sortBy('name');
            }
        }

        return view('superadmin.user-settings.index', compact('type', 'taxonomy', 'items', 'editing', 'states'));
    }

    public function store(Request $request, string $type)
    {
        $type = $this->normalizeType($type);
        $taxonomy = $this->taxonomy($type);
        $model = $taxonomy['model'];

        if ($type === 'states') {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:191',
                    Rule::unique('states', 'name')->whereNull('deleted_at'),
                ],
                'country_id' => ['nullable', 'integer'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $validated['country_id'] = $request->input('country_id', 101) ?: 101;
            $model::query()->create($validated);
        } elseif ($type === 'cities') {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:191',
                    Rule::unique('cities', 'name')
                        ->where('state_id', $request->input('state_id'))
                        ->whereNull('deleted_at'),
                ],
                'state_id' => ['required', 'exists:states,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $model::query()->create($validated);
        } elseif ($type === 'sim_networks') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:191'],
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('sim_networks', 'code')->whereNull('deleted_at'),
                ],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $model::query()->create($validated);
        } elseif ($type === 'electricity_zones') {
            $validated = $request->validate([
                'zone' => ['required', 'string', 'max:191'],
                'zone_code' => ['nullable', 'string', 'max:191'],
                'state_id' => ['required', 'exists:states,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $model::query()->create($validated);
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique($taxonomy['table'], 'name')],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $model::query()->create($validated);
        }

        return redirect()
            ->route('superadmin.user-settings.index', ['type' => $type])
            ->with('success', $taxonomy['label'] . ' added successfully.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $type = $this->normalizeType($type);
        $taxonomy = $this->taxonomy($type);
        $model = $taxonomy['model'];
        $item = $model::query()->findOrFail($id);

        if ($type === 'states') {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:191',
                    Rule::unique('states', 'name')
                        ->ignore($item->getKey())
                        ->whereNull('deleted_at'),
                ],
                'country_id' => ['nullable', 'integer'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $validated['country_id'] = $request->input('country_id', $item->country_id ?? 101) ?: 101;
            $item->update($validated);
        } elseif ($type === 'cities') {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:191',
                    Rule::unique('cities', 'name')
                        ->ignore($item->getKey())
                        ->where('state_id', $request->input('state_id'))
                        ->whereNull('deleted_at'),
                ],
                'state_id' => ['required', 'exists:states,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $item->update($validated);
        } elseif ($type === 'sim_networks') {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:191'],
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('sim_networks', 'code')
                        ->ignore($item->getKey())
                        ->whereNull('deleted_at'),
                ],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $item->update($validated);
        } elseif ($type === 'electricity_zones') {
            $validated = $request->validate([
                'zone' => ['required', 'string', 'max:191'],
                'zone_code' => ['nullable', 'string', 'max:191'],
                'state_id' => ['required', 'exists:states,id'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $item->update($validated);
        } else {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique($taxonomy['table'], 'name')->ignore($item->getKey()),
                ],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);
            $item->update($validated);
        }

        return redirect()
            ->route('superadmin.user-settings.index', ['type' => $type])
            ->with('success', $taxonomy['label'] . ' updated successfully.');
    }

    private function normalizeType(string $type): string
    {
        return self::TYPE_ALIASES[$type] ?? $type;
    }

    private function taxonomy(string $type): array
    {
        $normalized = $this->normalizeType($type);
        abort_unless(isset(self::TAXONOMIES[$normalized]), 404);

        return self::TAXONOMIES[$normalized];
    }
}
