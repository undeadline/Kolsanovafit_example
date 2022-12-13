<?php

namespace App\Helpers\Users;

use App\User;

interface MoneyOfChecksAlgorithmInterface
{    
    /**
     * Алгоритм вычисления данных итогового отчета по всей таблице
     *
     * @param User $user
     * @param array
     * @return array
     */
    public function handle(User $user, array $dates): array;
}