<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User private notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Quiz channel for real-time start, timer sync & stage updates
Broadcast::channel('quiz.{quizId}', function ($user, $quizId) {
    return true;
});

// FFF Quiz channel for real-time start, timer sync & stage updates
Broadcast::channel('fff.quiz.{quizId}', function ($user, $quizId) {
    return true;
});

// Admin live quiz monitor channel
Broadcast::channel('admin.quiz.{quizId}', function ($user, $quizId) {
    return $user && ($user->is_superadmin || $user->is_admin);
});
