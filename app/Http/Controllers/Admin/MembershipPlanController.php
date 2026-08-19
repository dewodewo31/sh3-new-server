<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipPlanRequest;
use App\Repositories\MembershipPlanRepository;
use App\Services\MembershipService;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepository,
        private MembershipService $membershipService,
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
        $this->membershipService->invalidatePlansCache();

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plan membership berhasil dibuat');
    }

    public function update(int $id, MembershipPlanRequest $request)
    {
        $plan = $this->membershipPlanRepository->findById($id);
        $this->membershipPlanRepository->update($plan, $this->validatedData($request));
        $this->membershipService->invalidatePlansCache();

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plan membership berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $plan = $this->membershipPlanRepository->findById($id);

        if (! $this->membershipService->canDeletePlan($plan->key)) {
            return redirect()->route('admin.membership-plans.index')
                ->with('error', 'Plan tidak bisa dihapus karena sudah dipakai oleh peserta.');
        }

        $this->membershipPlanRepository->delete($plan);
        $this->membershipService->invalidatePlansCache();

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
