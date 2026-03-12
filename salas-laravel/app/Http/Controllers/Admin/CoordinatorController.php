<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CoordinatorController extends Controller
{
    public function index()
    {
        $coordinators = Coordinator::with('users')->orderBy('name')->get();

        $rows = $coordinators->map(function (Coordinator $c) {
            $user = $c->users->first();
            return [
                'coordinator_id' => $c->id,
                'coordinator_name' => $c->name,
                'user_id' => $user?->id,
                'email' => $user?->email,
                'active' => $user?->active ?? false,
            ];
        });

        return view('admin.coordinators.index', [
            'rows' => $rows,
            'navCurrent' => 'coordenadores',
        ]);
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $coord = Coordinator::create(['name' => $valid['name']]);

        User::create([
            'name' => $valid['name'],
            'email' => $valid['email'],
            'password_hash' => Hash::make($valid['password']),
            'role' => 'COORDINATOR',
            'coordinator_id' => $coord->id,
            'active' => true,
        ]);

        return redirect()->route('admin.coordinators.index')->with('success', 'Coordenador criado.');
    }

    public function resetPassword(Request $request)
    {
        $valid = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'new_password' => 'required|string|min:8',
        ]);

        $user = User::where('id', $valid['user_id'])->where('role', 'COORDINATOR')->firstOrFail();
        $user->password_hash = Hash::make($valid['new_password']);
        $user->save();

        return redirect()->route('admin.coordinators.index')->with('success', 'Senha alterada.');
    }
}
