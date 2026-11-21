<?php
declare(strict_types=1);

/**
 * Unit test bootstrap for ksf_FA_FormBuilder.
 *
 * Loads Composer's autoloader and stubs FA functions so the same test suite
 * runs outside FA.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('_')) {
    function _($string)
    {
        return $string;
    }
}

if (!defined('TB_PREF')) {
    define('TB_PREF', '0_');
}

if (!function_exists('db_query')) {
    $GLOBALS['KSF_DB_LAST_SQL'] = null;
    $GLOBALS['KSF_DB_RESULT_SETS'] = [];
    $GLOBALS['KSF_DB_RESULT_POS'] = [];
    $GLOBALS['KSF_DB_NEXT_RESULT_ID'] = 0;
    $GLOBALS['KSF_DB_INSERT_ID'] = 1;

    function db_query($sql, $errMsg = '')
    {
        $GLOBALS['KSF_DB_LAST_SQL'] = $sql;
        $resultId = (string)(++$GLOBALS['KSF_DB_NEXT_RESULT_ID']);
        $GLOBALS['KSF_DB_RESULT_SETS'][$resultId] = [];
        $GLOBALS['KSF_DB_RESULT_POS'][$resultId] = 0;
        return $resultId;
    }

    function db_fetch($result)
    {
        if (!is_string($result) || !isset($GLOBALS['KSF_DB_RESULT_SETS'][$result])) {
            return false;
        }
        $rows = &$GLOBALS['KSF_DB_RESULT_SETS'][$result];
        $pos = &$GLOBALS['KSF_DB_RESULT_POS'][$result];
        if ($pos < count($rows)) {
            return $rows[$pos++];
        }
        return false;
    }

    function db_insert_id()
    {
        return (int)($GLOBALS['KSF_DB_INSERT_ID'] ?? 1);
    }

    function db_escape($value)
    {
        return "'" . addslashes((string) $value) . "'";
    }
}

if (!function_exists('db_num_rows')) {
    function db_num_rows($result)
    {
        return 0;
    }
}

if (!function_exists('db_error')) {
    function db_error()
    {
        return '';
    }
}

$_SESSION = [];
