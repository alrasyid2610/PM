<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request)
    {
        $id = Auth::id();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
        ]);

        $before = (array) DB::table('users')->where('id', $id)->first();

        DB::table('users')->where('id', $id)->update([
            'name'       => $request->name,
            'email'      => $request->email,
            'updated_at' => now(),
        ]);

        $after = (array) DB::table('users')->where('id', $id)->first();
        saveAudit('users', $id, 'update', json_encode($before), json_encode($after));

        return response()->json(['success' => true, 'message' => 'Data diri berhasil diperbarui']);
    }

    public function updatePassword(Request $request)
    {
        $id = Auth::id();

        $request->validate([
            'current_password'     => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = DB::table('users')->where('id', $id)->first();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password saat ini salah'], 422);
        }

        DB::table('users')->where('id', $id)->update([
            'password'   => Hash::make($request->password),
            'updated_at' => now(),
        ]);

        saveAudit('users', $id, 'update', json_encode(['password' => '***']), json_encode(['password' => '*** (changed)']));

        return response()->json(['success' => true, 'message' => 'Password berhasil diubah']);
    }

    public function updateAvatar(Request $request)
    {
        $id = Auth::id();

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = DB::table('users')->where('id', $id)->first();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $fileName = 'avatar_' . $id . '_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
        $path = $request->file('avatar')->storeAs('avatars', $fileName, 'public');

        DB::table('users')->where('id', $id)->update([
            'avatar'     => $path,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Foto profil berhasil diperbarui', 'avatar_url' => Storage::url($path)]);
    }
}
