<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiService
{
    public function allUsers(): array
    {
        return User::all()->toArray();
    }

    public function findUserById(int $id): ?User
    {
        return User::find($id);
    }

    public function createUser(array $data): User
    {
        return User::create($data);
    }

    public function updateUser(int $id, array $data): ?User
    {
        $user = User::find($id);

        if (! $user) {
            return null;
        }

        $user->update($data);

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        $user = User::find($id);

        if (! $user) {
            return false;
        }

        return $user->delete();
    }

    public function paginateUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::paginate($perPage);
    }
}