<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function store(\App\Http\Requests\OrganizationMemberRequest $request)
    {
        $this->organizationMemberRepository->create($request->validated());

        return redirect()->route('admin.organizations.index')->with('success', 'Anggota organisasi berhasil ditambahkan');
    }

    public function edit(int $id)
    {
        $member = $this->organizationMemberRepository->findById($id);

        return view('organizations.edit', compact('member'));
    }

    public function update(int $id, \App\Http\Requests\OrganizationMemberRequest $request)
    {
        $member = $this->organizationMemberRepository->findById($id);
        $this->organizationMemberRepository->update($member, $request->validated());

        return redirect()->route('admin.organizations.index')->with('success', 'Data anggota berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $member = $this->organizationMemberRepository->findById($id);
        $this->organizationMemberRepository->delete($member);

        return redirect()->route('admin.organizations.index')->with('success', 'Anggota berhasil dihapus');
    }
}
