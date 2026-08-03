<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipPlanRequest;
use App\Models\MembershipHistory;
use App\Models\Participant;
use App\Repositories\MembershipPlanRepository;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepository,
    ) {}

    public function index(Request $request)
    {
        $plans = $this->membershipPlanRepository->paginateFiltered(
            $request->input('search'),
            $request->input('status'),
        );
        $nextSortOrder = $this->membershipPlanRepository->nextSortOrder();

        return view('membership_plans.index', compact('plans', 'nextSortOrder'));
    }

    public function store(MembershipPlanRequest $request)
    {
        $this->membershipPlanRepository->create($this->validatedData($request));

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plan membership berhasil dibuat');
    }

    public function update(int $id, MembershipPlanRequest $request)
    {
        $plan = $this->membershipPlanRepository->findById($id);
        $this->membershipPlanRepository->update($plan, $this->validatedData($request));

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plan membership berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $plan = $this->membershipPlanRepository->findById($id);

        $used = MembershipHistory::where('membership_type', $plan->key)->exists()
            || Participant::where('membership_type', $plan->key)->exists();

        if ($used) {
            return redirect()->route('admin.membership-plans.index')
                ->with('error', 'Plan tidak bisa dihapus karena sudah dipakai oleh peserta.');
        }

        $this->membershipPlanRepository->delete($plan);

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plan membership berhasil dihapus');
    }

    private function validatedData(MembershipPlanRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
