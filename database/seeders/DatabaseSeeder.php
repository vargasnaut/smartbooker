<?php

namespace DatabaseSeeders;

use App\Models\Business;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Usuario Administrador y Cliente
        $owner = User::create([
            'name'     => 'Carlos Ramos',
            'email'    => 'carlos@smartbooker.com',
            'password' => Hash::make('password123'),
            'phone'    => '987654321',
        ]);

        $customer = User::create([
            'name'     => 'Ana Gómez',
            'email'    => 'ana@gmail.com',
            'password' => Hash::make('password123'),
            'phone'    => '912345678',
        ]);

        // 2. Crear Negocio
        $business = Business::create([
            'owner_id'  => $owner->id,
            'name'      => 'Barbería & Spa Elite',
            'slug'      => 'barberia-spa-elite',
            'time_zone' => 'America/Lima',
            'is_active' => true,
        ]);

        // 3. Crear Servicio
        $service = Service::create([
            'business_id'      => $business->id,
            'name'             => 'Corte de Cabello VIP',
            'price'            => 45.00,
            'duration_minutes' => 45,
            'padding_minutes'  => 15,
            'is_active'        => true,
        ]);

        // 4. Crear Miembro del Personal (Staff)
        $staffUser = User::create([
            'name'     => 'Roberto Barber',
            'email'    => 'roberto@smartbooker.com',
            'password' => Hash::make('password123'),
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'user_id'     => $staffUser->id,
            'bio'         => 'Especialista en cortes modernos y barba.',
            'is_active'   => true,
        ]);

        // Asignar servicio al staff
        $staff->services()->attach($service->id);

        // 5. Configurar Horario Semanal del Staff (Lunes a Sábado de 09:00 a 18:00)
        // 0=Domingo, 1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado
        for ($day = 0; $day <= 6; $day++) {
            Schedule::create([
                'staff_id'       => $staff->id,
                'day_of_week'    => $day,
                'start_time'     => '09:00:00',
                'end_time'       => '18:00:00',
                'is_working_day' => ($day !== 0), // Domingo (0) no es laboral
            ]);
        }
    }
}