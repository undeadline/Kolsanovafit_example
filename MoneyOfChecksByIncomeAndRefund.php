<?php

namespace App\Helpers\Users;

use App\Helpers\Date\DateFormatter;
use App\User;
use App\Helpers\Users\MoneyOfChecksAlgorithmInterface;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use DB;
use Illuminate\Database\QueryException;

class MoneyOfChecksByIncomeAndRefund implements MoneyOfChecksAlgorithmInterface
{
    /**
     * Приводим копейки к рублям
     *
     * @var array $fieldsForRounding
     */
    protected $fieldsForRounding = [
        "payed_cash",
        "payed_cashless",
        "payed_consideration",
        "payed_credit",
        "payed_prepay",
        "refund_payed_cash",
        "refund_payed_cashless",
        "refund_payed_consideration",
        "refund_payed_credit",
        "refund_payed_prepay",
        "payed_total",
        "refund_total"
    ];

    /**
     * Алгоритм вычисления данных итогового отчета по всей таблице
     *
     * @param User $user
     * @param array
     * @return array
     */
    public function handle(User $user, array $dates): array
    {
        try {
            $result = $this->query($user, $dates);
        } catch(InvalidFormatException $e) {
            throw $e;
        } catch(QueryException $e) {
            throw $e;
        } catch(\Exception $e) {
            throw $e;
        }

        $result['payed_total'] = $result['payed_cash'] + $result['payed_cashless'] + $result['payed_consideration'];
        $result['refund_total'] = $result['refund_payed_cash'] + $result['refund_payed_cashless'] + $result['refund_payed_consideration'];
        $result['last_update'] = Carbon::now()->toW3cString();
        $result['date_from'] = $dates['from'];
        $result['date_to'] = $dates['to'];

        return $result;
    }

    /**
     * Форматирование дат и запрос для получения сумм
     *
     * @param User $user
     * @param array
     * @return array
     */
    protected function query(User $user, array $dates): array
    {
        try {
            $from = DateFormatter::parseToDateWithStartTime($dates['from'] ?? DateFormatter::getDefaultMinDate())->toDateTimeLocalString();
            $to = DateFormatter::parseToDateWithEndTime($dates['to'] ?? DateFormatter::getDefaultMaxDate())->toDateTimeLocalString();
        } catch(InvalidFormatException $e) {
            throw $e;
        }

        try {
            $result = collect(DB::select("SELECT
                sum(payed_cash) FILTER (WHERE check_type_id = 1) as payed_cash,
                sum(payed_cashless) FILTER (WHERE check_type_id = 1) as payed_cashless,
                sum(payed_consideration) FILTER (WHERE check_type_id = 1) as payed_consideration,
                sum(payed_credit) FILTER (WHERE check_type_id = 1) as payed_credit,
                sum(payed_prepay) FILTER (WHERE check_type_id = 1) as payed_prepay,
                sum(payed_cash) FILTER (WHERE check_type_id = 2) as refund_payed_cash,
                sum(payed_cashless) FILTER (WHERE check_type_id = 2) as refund_payed_cashless,
                sum(payed_consideration) FILTER (WHERE check_type_id = 2) as refund_payed_consideration,
                sum(payed_credit) FILTER (WHERE check_type_id = 2) as refund_payed_credit,
                sum(payed_prepay) FILTER (WHERE check_type_id = 2) as refund_payed_prepay,
                count(1) FILTER (WHERE check_type_id in (1,2)) as checks_count
                
                FROM (SELECT payed_cash, payed_cashless, payed_consideration, payed_credit, payed_prepay, check_type_id, status_id
                    FROM checks
                    WHERE user_ref = {$user->id} and date_create between '$from' and '$to' and status_id = 3
                LIMIT 300000) as checks"
            ))->first();

            $result = collect($result)->toArray();
            $this->convertNullsToZero($result); 

            return $result;
        } catch(QueryException $e) {
            throw $e;
        } catch(\Exception $e) {
            throw $e;
        }
    }

    /**
     * Конвертация null в 0 и перевод копеек в рубли
     *
     * @param array
     * @return void
     */
    private function convertNullsToZero(&$array)
    {
        foreach($array as $key => &$value) {
            if (is_null($value)) {
                $value = 0;
            } else {
                if (in_array($key, $this->fieldsForRounding)) {
                    $value = (int) $value / 100;
                }
            }
        }
    }
}