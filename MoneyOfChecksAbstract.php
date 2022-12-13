<?php

namespace App\Helpers\Users;

use App\User;
use App\Models\Check;
use Illuminate\Support\Facades\Cache;

abstract class MoneyOfChecksAbstract
{    
    /**
     * Хранит объект пользователя
     *
     * @var User $user
     */
    protected $user = null;

    /**
     * Хранит массив дат для фильтрации
     *
     * @var User $user
     */
    protected $dates = [];

    /**
     * Возвращает результат расчетов в виде массива
     *
     * @return array
     */
    abstract public function calculate(): array;
    
    /**
     * Метод для вставки новых данных отчета
     *
     * @param  array $data
     * @return void
     */
    public function set(array $data): void
    {
        Cache::set("users:total:{$this->user->id}", $data);
    }

    /**
     * Метод вставки объекта пользователя
     *
     * @param  User $user
     * @return void
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Метод вставки дат для фильтрации
     *
     * @param  array $dates
     * @return void
     */
    public function setDates(array $dates): void
    {
        $this->dates = $dates;
    }

    /**
     * Снять блокировку на обновление данных
     *
     * @return void
     */
    public function unlock(): void
    {
        Cache::delete("users:total:{$this->user->id}:lock");
    }

    /**
     * Проверка наличия записи с кэше
     *
     * @return bool
     */
    public function exists(): bool
    {
        return Cache::has("users:total:{$this->user->id}");
    }

    /**
     * Удаление данных их кэша
     *
     * @return bool
     */
    public function clear(): bool
    {
        return Cache::delete("users:total:{$this->user->id}");
    }
    
    /**
     * Проверка наличия пользователя и возврат его при наличии
     *
     * @return User|null
     */
    protected function user()
    {
        if (is_null($this->user)) {
            return null;
        }

        return $this->user;
    }

    /**
     * Вернуть даты фильтрации
     *
     * @return array
     */
    protected function dates()
    {
        return $this->dates;
    }

    /**
     * Возвращает true если вычисление в процессе выполнения
     * и false если вычисление закончилось
     *
     * @return bool
     */
    protected function locked(): bool
    {
        return Cache::has("users:total:{$this->user->id}:lock");
    }
    
    /**
     * Вернуть данные отчета
     *
     * @return array
     */
    protected function data(): array
    {
        $list = Cache::get("users:total:{$this->user->id}");

        return is_null($list) ? [] : $list;
    }
    
    /**
     * Поставить блокировку на обновление данных
     *
     * @return void
     */
    protected function lock(): void
    {
        Cache::set("users:total:{$this->user->id}:lock", 1);
    }
}