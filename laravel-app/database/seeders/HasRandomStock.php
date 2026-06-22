<?php

namespace Database\Seeders;

trait HasRandomStock
{
    // Distribusi: 20% habis, 30% = stok minimum, 50% = 2x atau 3x stok minimum
    private function randomQtyStok(int $stokMinimum): int
    {
        $rand = rand(1, 10);

        if ($rand <= 2) {
            return 0;                             // 20% stok habis
        } elseif ($rand <= 5) {
            return $stokMinimum;                  // 30% tepat stok minimum
        } else {
            return $stokMinimum * rand(2, 3);     // 50% 2x atau 3x stok minimum
        }
    }
}
