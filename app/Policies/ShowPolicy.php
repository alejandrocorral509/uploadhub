<?php

namespace App\Policies;

use App\Models\Show;
use App\Models\User;

class ShowPolicy
{
    public function update(User $user, Show $show): bool
    {
        return $user->id === $show->user_id;
    }

    public function delete(User $user, Show $show): bool
    {
        return $user->id === $show->user_id;
    }
}
