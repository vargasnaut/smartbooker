<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Usuario Dueño de Negocio
        $owner = User::create([
            'name' => 'Admin SmartBooker',
            'email' => 'admin@smartbooker.com',
            'password' => bcrypt('password123'),
            'phone' => '+51987654321',
        ]);

        // 2. Crear Negocio
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Barbería & Spa Elite',
            'slug' => 'barberia-elite',
            'time_zone' => 'America/Lima',
        ]);

        // 3. Crear Servicio
        $service = Service::create([
            'business_id' => $business->id,
            'name' => 'Corte de Cabello + Barba',
            'price' => 45.00,
            'duration_minutes' => 45,
            'padding_minutes' => 15,
        ]);

        // 4. Crear Usuario Staff y vincularlo
        $staffUser = User::create([
            'name' => 'Carlos Barber',
            'email' => 'carlos@smartbooker.com',
            'password' => bcrypt('password123'),
        ]);

        $staff = Staff::create([
            'business_id' => $business->id,
            'user_id' => $staffUser->id,
        ]);

        // Asignar el servicio al staff
        $staff->services()->attach($service->id);
    }
}