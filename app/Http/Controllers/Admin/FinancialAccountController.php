<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialAccountRequest;
use App\Repositories\FinancialAccountRepository;
use App\Services\FileService;
use App\Services\UserService;

class FinancialAccountController extends Controller
{
    public function __construct(
        private FinancialAccountRepository $financialAccountRepository,
        private UserService $userService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $financialAccounts = $this->financialAccountRepository->findActive();

        return view('financial-accounts.index', compact('financialAccounts'));
    }

    public function create()
    {
        return view('financial-accounts.create');
    }

    public function store(FinancialAccountRequest $request)
    {
        $data = $request->validated();

        $financialAccount = $this->financialAccountRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_financial_account', ['financial_account_id' => $financialAccount->id, 'name' => $financialAccount->name]);

        return redirect()->route('admin.financial-accounts.index')->with('success', 'Akun keuangan berhasil dibuat');
    }

    public function edit(int $id)
    {
        $financialAccount = $this->financialAccountRepository->findById($id);

        return view('financial-accounts.edit', compact('financialAccount'));
    }

    public function update(int $id, FinancialAccountRequest $request)
    {
        $financialAccount = $this->financialAccountRepository->findById($id);
        $data = $request->validated();

        $this->financialAccountRepository->update($financialAccount, $data);

        $this->userService->logActivity(auth()->user(), 'update_financial_account', ['financial_account_id' => $financialAccount->id, 'name' => $financialAccount->name]);

        return redirect()->route('admin.financial-accounts.index')->with('success', 'Akun keuangan berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $financialAccount = $this->financialAccountRepository->findById($id);

        $this->financialAccountRepository->delete($financialAccount);

        $this->userService->logActivity(auth()->user(), 'delete_financial_account', ['financial_account_id' => $id, 'name' => $financialAccount->name]);

        return redirect()->route('admin.financial-accounts.index')->with('success', 'Akun keuangan berhasil dihapus');
    }
}
