<?php

namespace App\Helpers\Users;

use App\Jobs\Checks\MoneyOfChecksJob;
use App\Helpers\Users\MoneyOfChecksAbstract;
use Carbon\Exceptions\InvalidFormatException;

class MoneyOfChecks extends MoneyOfChecksAbstract
{
    /**
     * Хранит объект алгоритма обработки данных
     *
     * @var MoneyOfChecksAlgorithmInterface $alg
     */
    protected $alg;

    public function __construct()
    {
        $this->alg = app()->make(MoneyOfChecksAlgorithmInterface::class);
    }

    /**
     * Запускает процесс вычисления отчета если отчета нет.
     * Возвращает результат расчетов в виде массива.
     *
     * @return array
     */
    public function calculate(): array
    {
        if (is_null($this->user()) || $this->locked()) {
            return [];
        }

        if (!$this->exists()) {

            $this->lock();

            dispatch(new MoneyOfChecksJob($this))->onQueue('sum_calculator');

            return [];
        }

        return $this->data();
    }

    /**
     * Выполнить обновление данных
     *
     * @return void
     */
    public function run(): void
    {
        try {
            $this->set($this->alg->handle($this->user(), $this->dates()));
        } catch(InvalidFormatException $e) {
            throw $e;
        }
    }
}
