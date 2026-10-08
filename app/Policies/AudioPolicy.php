<?php

namespace App\Policies;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AudioPolicy
{
    // Ver, escuchar, descargar, comentar y dar me gusta: público o propio.
    // Un episodio privado ajeno responde 404, para no revelar siquiera que existe.
    public function view(?User $user, Audio $audio): Response
    {
        return $audio->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Audio $audio): bool
    {
        return $user->id === $audio->user_id;
    }

    public function delete(User $user, Audio $audio): bool
    {
        return $user->id === $audio->user_id;
    }

    // Las estadísticas solo las ve el autor
    public function viewStats(User $user, Audio $audio): bool
    {
        return $user->id === $audio->user_id;
    }
}
