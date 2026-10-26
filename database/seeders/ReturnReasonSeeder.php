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
                'reason_id' => '01a11f3f-79f9-7386-9097-067c1476944e',
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
            ReturnReason::updateOrCreate(['name' => $reason['name']], $reason);
        }
    }
}
