<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class QueuedEmailVerification extends VerifyEmail implements ShouldBeEncrypted, ShouldQueueAfterCommit {}
