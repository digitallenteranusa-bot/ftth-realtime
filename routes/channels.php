<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('network-monitoring', function ($user) {
    return true;
});

Broadcast::channel('traffic.{mikrotikId}', function ($user, $mikrotikId) {
    return true;
});
