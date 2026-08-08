<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMemberRequest;
use App\Repositories\OrganizationMemberRepository;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationMemberRepository $organizationMemberRepository,
    ) {}

    public function index()
    {
        $members = $this->organizationMemberRepository->all();

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
            $data['avatar'] = ImageHelper::upload($request->file('avatar'), 'organizations');
        }

        $this->organizationMemberRepository->create($data);

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
            if ($member->avatar) {
                ImageHelper::delete($member->avatar);
            }
            $data['avatar'] = ImageHelper::upload($request->file('avatar'), 'organizations');
        }

        $this->organizationMemberRepository->update($member, $data);

        return redirect()->route('admin.organizations.index')->with('success', 'Data anggota berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $member = $this->organizationMemberRepository->findById($id);

        if ($member->avatar) {
            ImageHelper::delete($member->avatar);
        }

        $this->organizationMemberRepository->delete($member);

        return redirect()->route('admin.organizations.index')->with('success', 'Anggota berhasil dihapus');
    }
}
