<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'user:make-superadmin
                            {--email= : The email address for the superadmin}
                            {--name= : The name for the superadmin}
                            {--password= : The password (auto-generated and printed if omitted)}';

    /**
     * The console command description.
     */
    protected $description = 'Create a superadmin user or promote an existing user to superadmin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->ensureRolesExist();

        $email = $this->option('email') ?? $this->ask('Email address');

        $user = User::where('email', $email)->first();

        if ($user) {
            return $this->promoteExisting($user);
        }

        return $this->createNew($email);
    }

    private function ensureRolesExist(): void
    {
        if (! Role::where('name', 'superadmin')->exists()) {
            $this->info('Roles not found. Seeding roles and permissions...');
            $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class]);
        }
    }

    private function promoteExisting(User $user): int
    {
        if ($user->hasRole('superadmin')) {
            $this->warn("User {$user->email} is already a superadmin.");
            return self::SUCCESS;
        }

        $user->assignRole('superadmin');
        $this->info("User {$user->email} promoted to superadmin.");

        return self::SUCCESS;
    }

    private function createNew(string $email): int
    {
        $name = $this->option('name') ?? $this->ask('Name', 'Super Admin');

        $generated = false;
        $password = $this->option('password');
        if (! $password) {
            $password = \Illuminate\Support\Str::password(16);
            $generated = true;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole('superadmin');

        $this->info("Superadmin created: {$user->email}");

        if ($generated) {
            $this->line("Password:          <fg=yellow>{$password}</>");
            $this->warn('Save this password — it will not be shown again.');
        }

        return self::SUCCESS;
    }
}
