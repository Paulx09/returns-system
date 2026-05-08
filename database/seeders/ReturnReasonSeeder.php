<?php

namespace Database\Seeders;

use App\Models\ReturnReason;
use Illuminate\Database\Seeder;

class ReturnReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = [
            [
                'name' => 'Producto Dañado',
                'description' => 'El producto llegó con daños físicos o de fábrica.',
            ],
            [
                'name' => 'Producto Equivocado',
                'description' => 'El producto recibido no corresponde al comprado.',
            ],
            [
                'name' => 'Faltan Accesorios',
                'description' => 'El producto llegó incompleto.',
            ],
            [
                'name' => 'Retracto',
                'description' => 'Desistimiento de la compra dentro del plazo legal.',
            ],
        ];

        foreach ($reasons as $reason) {
            ReturnReason::create($reason);
        }
    }
}
