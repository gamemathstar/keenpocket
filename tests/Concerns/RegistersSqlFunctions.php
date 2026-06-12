<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Registers the MySQL-only SQL functions the core read endpoints rely on
 * (FORMAT, DATE_FORMAT, IF) as user functions on the sqlite test connection,
 * so those endpoints can be exercised in the test suite.
 */
trait RegistersSqlFunctions
{
    protected function registerSqlFunctions(): void
    {
        $conn = DB::connection();
        if ($conn->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $conn->getPdo();

        // FORMAT(number, decimals[, locale]) → comma-grouped string.
        $pdo->sqliteCreateFunction('FORMAT', function ($n, $d = 0) {
            return number_format((float) $n, (int) $d);
        }, -1);

        // IF(condition, then, else)
        $pdo->sqliteCreateFunction('IF', function ($cond, $a, $b) {
            return $cond ? $a : $b;
        }, 3);

        // DATE_FORMAT(date, mysql_format) → formatted date string.
        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $fmt) {
            if ($date === null) {
                return null;
            }
            $ts = is_numeric($date) ? (int) $date : strtotime($date);
            if ($ts === false) {
                return $date;
            }
            $map = [
                '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%d' => 'd', '%D' => 'jS',
                '%b' => 'M', '%M' => 'F', '%a' => 'D', '%W' => 'l',
                '%H' => 'H', '%h' => 'h', '%i' => 'i', '%s' => 's', '%p' => 'A',
            ];

            return date(strtr($fmt, $map), $ts);
        }, 2);
    }
}
