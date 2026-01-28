<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RadiusService;
use Illuminate\Http\Request;

class RadiusController extends Controller
{
    protected $radius;

    public function __construct(RadiusService $radius)
    {
        $this->radius = $radius;
    }

    public function index()
    {
        $enabled = $this->radius->isEnabled();
        $onlineUsers = $enabled ? $this->radius->getOnlineUsers() : [];
        
        return view('admin.radius.index', compact('enabled', 'onlineUsers'));
    }

    public function users()
    {
        if (!$this->radius->isEnabled()) {
            return back()->with('error', 'RADIUS tidak aktif');
        }

        // Get users from radcheck table
        $users = \DB::connection('radius')->table('radcheck')
            ->where('attribute', 'Cleartext-Password')
            ->select('username', 'value as password')
            ->paginate(20);

        return view('admin.radius.users', compact('users'));
    }

    public function groups()
    {
        if (!$this->radius->isEnabled()) {
            return back()->with('error', 'RADIUS tidak aktif');
        }

        $groups = \DB::connection('radius')->table('radgroupreply')
            ->select('groupname', 'attribute', 'value')
            ->get()
            ->groupBy('groupname');

        return view('admin.radius.groups', compact('groups'));
    }


    public function storeUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:64',
            'password' => 'required|string|min:6',
            'groupname' => 'nullable|string|max:64',
        ]);

        $result = $this->radius->createUser($request->username, $request->password);
        
        if ($result && $request->groupname) {
            $this->radius->assignGroup($request->username, $request->groupname);
        }

        return $result
            ? back()->with('success', 'User RADIUS berhasil dibuat')
            : back()->with('error', 'Gagal membuat user RADIUS');
    }

    public function storeGroup(Request $request)
    {
        $request->validate([
            'groupname' => 'required|string|max:64',
            'download_limit' => 'required|string',
            'upload_limit' => 'required|string',
        ]);

        $result = $this->radius->createGroup(
            $request->groupname,
            $request->download_limit,
            $request->upload_limit
        );

        return $result
            ? back()->with('success', 'Group RADIUS berhasil dibuat')
            : back()->with('error', 'Gagal membuat group RADIUS');
    }

    public function disconnect(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $result = $this->radius->disconnectUser($request->username);

        return $result
            ? back()->with('success', 'User berhasil di-disconnect')
            : back()->with('error', 'Gagal disconnect user');
    }

    public function suspend(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $result = $this->radius->suspendUser($request->username);

        return $result
            ? back()->with('success', 'User berhasil di-suspend')
            : back()->with('error', 'Gagal suspend user');
    }

    public function unsuspend(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $result = $this->radius->unsuspendUser($request->username);

        return $result
            ? back()->with('success', 'User berhasil di-unsuspend')
            : back()->with('error', 'Gagal unsuspend user');
    }

    public function history($username)
    {
        $history = $this->radius->getUserHistory($username);
        return view('admin.radius.history', compact('username', 'history'));
    }

    public function deleteUser(Request $request)
    {
        $request->validate(['username' => 'required|string']);
        
        $result = $this->radius->deleteUser($request->username);

        return $result
            ? back()->with('success', 'User RADIUS berhasil dihapus')
            : back()->with('error', 'Gagal menghapus user RADIUS');
    }
}
