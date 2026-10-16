<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PushVapid extends Command
{
    protected $signature = 'push:vapid';

    protected $description = 'Web Push icin VAPID anahtar cifti uretir (.env satirlarini yazdirir)';

    public function handle(): int
    {
        if (! class_exists(\Minishlink\WebPush\VAPID::class)) {
            $this->error('Önce: composer require minishlink/web-push');

            return self::FAILURE;
        }
        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);

        return self::SUCCESS;
    }
}
