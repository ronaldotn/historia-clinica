<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Patient, Organization, Practitioner, User, 
    Encounter, Condition, Observation, 
    DiagnosticReport, Consent, AuditEvents
};

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // 1. Crear Roles
        $this->createRoles();
        
        // 2. Crear Organizaciones (10)
        Organization::factory()->count(10)->create();
        
        // 3. Crear Pacientes (50)
        Patient::factory()->count(50)->create();
        
        // 4. Crear Practicantes/Médicos (20)
        Practitioner::factory()->count(20)->create();
        
        // 5. Crear Usuarios (20) + 1 Admin
        User::factory()->admin()->create(); // Usuario admin
        User::factory()->count(20)->active()->create();
        
        // 6. Asignar roles a usuarios
        $this->assignRolesToUsers();
        
        // 7. Crear Encuentros (100)
        Encounter::factory()->count(80)->create();
        Encounter::factory()->consultation()->count(15)->create();
        Encounter::factory()->emergency()->count(5)->create();
        
        // 8. Crear Condiciones (150)
        Condition::factory()->count(150)->create();
        
        // 9. Crear Observaciones (200)
        Observation::factory()->count(200)->create();
        
        // 10. Crear Reportes Diagnósticos (50)
        DiagnosticReport::factory()->count(50)->create();
        
        // 11. Crear Consentimientos (60)
        Consent::factory()->count(50)->create();
        Consent::factory()->active()->count(10)->create();
        
        // 12. Crear Eventos de Auditoría (200)
        AuditEvents::factory()->count(200)->create();
        
        $this->command->info('✅ Base de datos poblada exitosamente!');
        $this->command->info('📊 Resumen:');
        $this->command->table(
            ['Tabla', 'Registros'],
            [
                ['Roles', Role::count()],
                ['Organizaciones', Organization::count()],
                ['Pacientes', Patient::count()],
                ['Médicos', Practitioner::count()],
                ['Usuarios', User::count()],
                ['Encuentros', Encounter::count()],
                ['Condiciones', Condition::count()],
                ['Observaciones', Observation::count()],
                ['Reportes', DiagnosticReport::count()],
                ['Consentimientos', Consent::count()],
                ['Auditorías', AuditEvents::count()],
            ]
        );
    }

    private function createRoles(): void
    {
        $roles = [
            ['name' => 'admin'], //'description' => 'Administrador del sistema'
            ['name' => 'doctor'], //'description' => 'Médico con acceso completo'
            ['name' => 'nurse'], //'description' => 'Enfermero con acceso limitado'
            ['name' => 'receptionist'], //'description' => 'Recepcionista',
            ['name' => 'lab_technician'], //'description' => 'Técnico de laboratorio'
            ['name' => 'rrhh'] // Rol para Recursos Humanos
        ];

        foreach ($roles as $role) {
            // dd($role);
            Role::create($role);
        }
    }

    private function assignRolesToUsers(): void
    {
        $users = User::all();
        $roles = Role::all();

        $roles = Role::where('name', '!=', 'admin')->get(); // Excluimos el rol de admin para asignaciones aleatorias
 
        foreach ($users as $user) {
            // Admin siempre tiene rol admin
            if ($user->username === 'admin') {
                $user->assignRole('admin');
                continue;
            }

            // Otros usuarios obtienen roles aleatorios
            // $roleId = $roles->random()->role_id;
            // DB::table('user_roles')->insert([
            //     'user_id' => $user->user_id,
            //     'role_id' => $roleId,
            // ]);

            $roleName = $roles->random()->name;
            $user->assignRole($roleName);

            // Asignar un rol aleatorio (no-admin) a los demás usuarios
            $user->assignRole($roles->random()->name);
        }
    }
}
