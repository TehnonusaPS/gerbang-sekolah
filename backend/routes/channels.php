<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('feedback.{schoolId}', function ($user, $schoolId) {
    return (int) $user->school_id === (int) $schoolId 
        && $user->hasAnyRole(['kepala_sekolah', 'admin_sekolah']);
});

Broadcast::channel('persuratan.{schoolId}', function ($user, $schoolId) {
    return (int) $user->school_id === (int) $schoolId 
        && $user->hasAnyRole(['tata_usaha', 'kepala_sekolah', 'admin_sekolah']);
});
