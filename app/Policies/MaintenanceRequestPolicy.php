<?php

namespace App\Policies;

use App\Models\MaintenanceRequest as MR;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MaintenanceRequestPolicy
{
    use HandlesAuthorization;

    public function before(User $user): bool|null
    {
        if ($user->isAdmin()) {
            return true;
        }
        return null;
    }

    public function view(User $user, MR $req): Response
    {
        if ($user->isTechnician() && (int)$req->technician_id === (int)$user->id) {
            return Response::allow();
        }
        if ((int)$req->reporter_id === (int)$user->id) {
            return Response::allow();
        }
        return Response::deny('อนุญาตให้ดูเฉพาะงานของตนเองหรือที่ได้รับมอบหมายเท่านั้น');
    }

    public function update(User $user, MR $req): Response
    {
        if ($user->isTechnician() && (int)$req->technician_id === (int)$user->id) {
            return Response::allow();
        }
        if ((int)$req->reporter_id === (int)$user->id) {
            return Response::allow();
        }
        return Response::deny('ไม่มีสิทธิ์แก้ไขงานนี้');
    }

    public function transition(User $user, MR $req): Response
    {
        if ($user->isTechnician() && (int)$req->technician_id === (int)$user->id) {
            return Response::allow();
        }
        return Response::deny('อนุญาตให้เปลี่ยนสถานะเฉพาะช่างที่รับผิดชอบหรือผู้ดูแลระบบเท่านั้น');
    }

    public function attach(User $user, MR $req): Response
    {
        return $this->update($user, $req);
    }

    public function deleteAttachment(User $user, MR $req): Response
    {
        return $this->update($user, $req);
    }
}
