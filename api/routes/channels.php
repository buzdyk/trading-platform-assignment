<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);

// Orders channel - any authenticated user can subscribe
Broadcast::channel('orders', fn ($user): bool => $user !== null);
