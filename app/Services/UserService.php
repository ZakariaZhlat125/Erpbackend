<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class UserService extends BaseService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Model
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = parent::create($data);

        if (!empty($roles)) {
            $user->syncRoles($roles);
        }

        return $user;
    }

    public function update(int $id, array $data): bool
    {
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        $updated = parent::update($id, $data);

        if ($updated && $roles !== null) {
            $user = $this->findById($id);
            if ($user) {
                $user->syncRoles($roles);
            }
        }

        return $updated;
    }
}
