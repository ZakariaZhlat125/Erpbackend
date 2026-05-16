<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;

class UserService extends BaseService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data)
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = parent::create($data);

        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        return $user;
    }

    public function update(int $id, array $data)
    {
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $user = parent::update($id, $data);

        if ($roles !== null) {
            $user->syncRoles($roles);
        }

        return $user;
    }
}
