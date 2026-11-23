<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class QueuedPasswordReset extends ResetPassword implements ShouldBeEncrypted, ShouldQueueAfterCommit {}
