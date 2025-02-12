<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateFilamentUser extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:filament-user {--name=} {--email=} {--password=} {--roleid=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or Update a new Filament user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating Filament user...');

        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');
        $role_id = $this->option('roleid');

        $this->info('Name: ' . $name);

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info('User already exists. Updating user...');
            $user->update([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'role_id' => $role_id,
            ]);
        } else {
            $this->info('User does not exist. Creating user...');
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'role_id' => $role_id,
            ]);
        }
    }
}
