<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\SponsorRequest;
use App\Repositories\SponsorRepository;

class SponsorController extends Controller
{
    public function __construct(
        private SponsorRepository $sponsorRepository,
    ) {}

    public function index()
    {
        $sponsors = $this->sponsorRepository->all();

        return view('sponsors.index', compact('sponsors'));
    }

    public function create()
    {
        return view('sponsors.create');
    }

    public function store(SponsorRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('logo')) {
            $data['logo'] = ImageHelper::upload($request->file('logo'), 'sponsors');
        }

        $this->sponsorRepository->create($data);

        return redirect()->route('admin.sponsors.index')->with('success', 'Sponsor berhasil ditambahkan');
    }

    public function edit(int $id)
    {
        $sponsor = $this->sponsorRepository->findById($id);

        return view('sponsors.edit', compact('sponsor'));
    }

    public function update(int $id, SponsorRequest $request)
    {
        $sponsor = $this->sponsorRepository->findById($id);
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($sponsor->logo) {
                ImageHelper::delete($sponsor->logo);
            }
            $data['logo'] = ImageHelper::upload($request->file('logo'), 'sponsors');
        }

        $this->sponsorRepository->update($sponsor, $data);

        return redirect()->route('admin.sponsors.index')->with('success', 'Sponsor berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $sponsor = $this->sponsorRepository->findById($id);

        if ($sponsor->logo) {
            ImageHelper::delete($sponsor->logo);
        }

        $this->sponsorRepository->delete($sponsor);

        return redirect()->route('admin.sponsors.index')->with('success', 'Sponsor berhasil dihapus');
    }
}
