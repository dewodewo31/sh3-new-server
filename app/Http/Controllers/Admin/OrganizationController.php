<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMemberRequest;
use App\Repositories\OrganizationMemberRepository;
use App\Services\FileService;
use Illuminate\Support\Facades\Cache;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationMemberRepository $organizationMemberRepository,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $members = $this->organizationMemberRepository->allSorted(
            ['name', 'position', 'sort_order', 'is_active', 'period_start'],
            'sort_order',
            'asc',
        );

        return view('organizations.index', compact('members'));
    }

    public function create()
    {
        return view('organizations.create');
    }

    public function store(OrganizationMemberRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->fileService->upload($request->file('avatar'), 'organizations');
        }

        $this->organizationMemberRepository->create($data);
        Cache::forget('api:org:tree');
        Cache::forget('api:org:years');

        return redirect()->route('admin.organizations.index')->with('success', 'Anggota organisasi berhasil ditambahkan');
    }

    public function edit(int $id)
    {
        $member = $this->organizationMemberRepository->findById($id);

        return view('organizations.edit', compact('member'));
    }

    public function update(int $id, OrganizationMemberRequest $request)
    {
        $member = $this->organizationMemberRepository->findById($id);
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->fileService->uploadOrReplace(
                $member->avatar,
                $request->file('avatar'),
                'organizations',
            );
        }

        $this->organizationMemberRepository->update($member, $data);
        Cache::forget('api:org:tree');
        Cache::forget('api:org:years');

        return redirect()->route('admin.organizations.index')->with('success', 'Data anggota berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $member = $this->organizationMemberRepository->findById($id);

        $this->fileService->delete($member->avatar);

        $this->organizationMemberRepository->delete($member);
        Cache::forget('api:org:tree');
        Cache::forget('api:org:years');

        return redirect()->route('admin.organizations.index')->with('success', 'Anggota berhasil dihapus');
    }
}
