<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed FTC Award Templates first
        $this->call(FtcAwardTemplatesSeeder::class);

        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Mohamed Elkmeshi',
            'email' => 'melkmeshi@ftc.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        // Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@ftc.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        // Create Judge
        $judge = User::create([
            'name' => 'Islam Asmael',
            'email' => 'iasmael@ftc.test',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::Judge,
        ]);

        $this->command->info('✅ Created dev users:');
        $this->command->info('   Super Admin: melkmeshi@ftc.test / password');
        $this->command->info('   Admin: admin@ftc.test / password');
        $this->command->info('   Judge: iasmael@ftc.test / password');

        // Create Events
        $albydaEvent = Event::create([
            'name' => 'Albyda Tournament',
            'code' => 'ALBYDA-2026',
            'description' => 'FTC Tournament in Albyda region',
            'event_date' => now()->addMonths(1),
            'location' => 'البيضاء',
            'status' => 'judging',
            'is_active' => true,
        ]);

        $tripoliEvent = Event::create([
            'name' => 'Tripoli Tournament',
            'code' => 'TRIPOLI-2026',
            'description' => 'FTC Tournament in Tripoli region',
            'event_date' => now()->addMonths(2),
            'location' => 'طرابلس',
            'status' => 'judging',
            'is_active' => true,
        ]);

        // Initialize awards for both events
        $eventService = app(EventService::class);
        $eventService->initializeEvent($albydaEvent);
        $eventService->initializeEvent($tripoliEvent);

        // Assign users to events
        $albydaEvent->users()->attach($superAdmin->id, ['can_score' => true, 'can_deliberate' => true]);
        $albydaEvent->users()->attach($judge->id, ['can_score' => true, 'can_deliberate' => false]);

        $tripoliEvent->users()->attach($superAdmin->id, ['can_score' => true, 'can_deliberate' => true]);
        $tripoliEvent->users()->attach($judge->id, ['can_score' => true, 'can_deliberate' => false]);

        // Assign judge to all awards in both events
        foreach ($albydaEvent->awards as $award) {
            $award->judges()->attach($judge->id);
        }
        foreach ($tripoliEvent->awards as $award) {
            $award->judges()->attach($judge->id);
        }

        $this->command->info('✅ Created events: Albyda Tournament, Tripoli Tournament');

        // Albyda Tournament Teams
        $albydaTeams = [
            ['team_number' => 19354, 'team_name' => 'Bawader Robotics Team', 'city' => 'طبرق'],
            ['team_number' => 24806, 'team_name' => 'CPT Robotics Team', 'city' => 'طبرق'],
            ['team_number' => 19361, 'team_name' => 'LYBOTICS Balacris Team', 'city' => 'البيضاء'],
            ['team_number' => 26226, 'team_name' => 'Void Cosmos Robotics Team', 'city' => 'درنة'],
            ['team_number' => 26753, 'team_name' => 'LYBOTICS Cyrene Team', 'city' => 'شحات'],
            ['team_number' => 26405, 'team_name' => 'Al-Akhdar Robotics Team', 'city' => 'البيضاء'],
            ['team_number' => 26404, 'team_name' => 'LYBOTICS Impact Team', 'city' => 'البيضاء'],
            ['team_number' => 21412, 'team_name' => 'LYBOTICS Aces Team', 'city' => 'بنغازي'],
            ['team_number' => 21417, 'team_name' => 'LYBOTICS Spark Team', 'city' => 'بني وليد'],
            ['team_number' => 27383, 'team_name' => 'Ice Bot Team', 'city' => 'اجدابيا'],
            ['team_number' => 21470, 'team_name' => 'LYBOTICS Super Speed', 'city' => 'بنغازي'],
            ['team_number' => 27338, 'team_name' => 'LYBOTICS Silvium Team', 'city' => 'بنغازي'],
            ['team_number' => 18495, 'team_name' => 'LYBOTICS LumaTech Team', 'city' => 'طرابلس'],
            ['team_number' => 31933, 'team_name' => 'Wolfpack Robotics Team', 'city' => 'بنغازي'],
            ['team_number' => 31233, 'team_name' => 'Saturn Robotics Team', 'city' => 'البيضاء'],
            ['team_number' => 26302, 'team_name' => 'J5 Robotics Team', 'city' => 'بنغازي'],
            ['team_number' => 30640, 'team_name' => 'LYBOTICS Viren Team', 'city' => 'بنغازي'],
            ['team_number' => 29027, 'team_name' => 'LYBOTICS NeexGen Team', 'city' => 'بنغازي'],
            ['team_number' => 22172, 'team_name' => 'AMLY Tech Robotics', 'city' => 'طرابلس'],
            ['team_number' => 28549, 'team_name' => 'Alshat Scout Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 32018, 'team_name' => 'Asteroid X team', 'city' => 'بنغازي'],
        ];

        // Tripoli Tournament Teams
        $tripoliTeams = [
            ['team_number' => 26252, 'team_name' => 'TLC Cyber Knights Team', 'city' => 'طرابلس'],
            ['team_number' => 24805, 'team_name' => 'Alnoor Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 22786, 'team_name' => 'Basida Robotics Team', 'city' => 'زوارة'],
            ['team_number' => 26997, 'team_name' => 'Protobyte Robotics Team', 'city' => 'زوارة'],
            ['team_number' => 19557, 'team_name' => 'The astrolabe Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 27346, 'team_name' => 'B-Mo Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 25102, 'team_name' => 'MechMasters Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 24398, 'team_name' => 'LYBOTICS HYDRA Team', 'city' => 'طرابلس'],
            ['team_number' => 18498, 'team_name' => 'LYBOTICS Change Team', 'city' => 'طرابلس'],
            ['team_number' => 19409, 'team_name' => 'IKS Robotics Team', 'city' => 'مصراتة'],
            ['team_number' => 18432, 'team_name' => 'D-MO Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 19910, 'team_name' => 'ISM Tech Masters Team', 'city' => 'طرابلس'],
            ['team_number' => 21411, 'team_name' => 'LYBOTICS Taj Team', 'city' => 'تاجوراء'],
            ['team_number' => 18549, 'team_name' => 'LYBOTICS Quanta Team', 'city' => 'جنزور'],
            ['team_number' => 22231, 'team_name' => 'IKS.DX Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 28342, 'team_name' => 'LYBOTICS Air Wizards Team', 'city' => 'مصراتة'],
            ['team_number' => 26254, 'team_name' => 'LYBOTICS Scorpionz Team', 'city' => 'طرابلس'],
            ['team_number' => 19911, 'team_name' => 'BIT Robotics Team', 'city' => 'بني وليد'],
            ['team_number' => 19353, 'team_name' => 'LYBOTICS Geniuses Team', 'city' => 'طرابلس'],
            ['team_number' => 26823, 'team_name' => 'LYBOTICS Wolf Strikes Team', 'city' => 'طرابلس'],
            ['team_number' => 32670, 'team_name' => 'Ravens tech masters Team', 'city' => 'طرابلس'],
            ['team_number' => 30642, 'team_name' => 'LYBOTICS Glitchers Team', 'city' => 'طرابلس'],
            ['team_number' => 30707, 'team_name' => 'Scarlet Scorp Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 33554, 'team_name' => 'MS Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 21409, 'team_name' => 'MKS Tiger Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 33556, 'team_name' => 'LYBOTICS WALL-E Team', 'city' => 'بنغازي'],
            ['team_number' => 32933, 'team_name' => 'Hespereds Robotics Team', 'city' => 'بنغازي'],
            ['team_number' => 26228, 'team_name' => 'Void Vortex Robotics Team', 'city' => 'درج'],
            ['team_number' => 26253, 'team_name' => 'Cyber Gs Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 28787, 'team_name' => 'Almaaly Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 34206, 'team_name' => 'SMS Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 34208, 'team_name' => 'Akakus Robotics Team', 'city' => 'سبها'],
            ['team_number' => 34204, 'team_name' => 'Academy Robotics Team', 'city' => 'طرابلس'],
            ['team_number' => 30706, 'team_name' => 'Shadow Robotics Team', 'city' => 'طبرق'],
            ['team_number' => 19455, 'team_name' => 'LYBOTICS Zawia Team', 'city' => 'الزاوية'],
        ];

        // Create Albyda teams and attach to event
        foreach ($albydaTeams as $teamData) {
            $team = Team::create([
                'team_number' => $teamData['team_number'],
                'team_name' => $teamData['team_name'],
                'city' => $teamData['city'],
                'country' => 'Libya',
            ]);
            $team->events()->attach($albydaEvent->id, ['is_active' => true]);
        }

        $this->command->info('✅ Created '.count($albydaTeams).' teams for Albyda Tournament');

        // Create Tripoli teams and attach to event
        foreach ($tripoliTeams as $teamData) {
            $team = Team::create([
                'team_number' => $teamData['team_number'],
                'team_name' => $teamData['team_name'],
                'city' => $teamData['city'],
                'country' => 'Libya',
            ]);
            $team->events()->attach($tripoliEvent->id, ['is_active' => true]);
        }

        $this->command->info('✅ Created '.count($tripoliTeams).' teams for Tripoli Tournament');
    }
}
