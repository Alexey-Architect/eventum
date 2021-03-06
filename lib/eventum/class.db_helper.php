<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\Adapter\NullAdapter;
use Eventum\Db\DatabaseException;

/**
 * Class to manage all tasks related to the DB abstraction module. This is only
 * useful to maintain a data dictionary of the current database schema tables.
 */
class DB_Helper
{
    const DEFAULT_ADAPTER = 'Pear';

    /**
     * @param bool $fallback
     * @return AdapterInterface
     * @throws DatabaseException
     * @throws Exception
     */
    public static function getInstance($fallback = true)
    {
        static $instance;
        if ($instance !== null) {
            return $instance;
        }

        // initialize value to avoid recursion
        $instance = false;

        $config = self::getConfig();
        $className = self::getAdapterClass($config);

        try {
            $instance = new $className($config);
        } catch (DatabaseException $e) {
            // set dummy provider in as offline.php uses db methods
            $instance = new NullAdapter($config);

            if (!$fallback) {
                throw $e;
            }
            /** @global $error_type */
            $error_type = 'db';
            require APP_PATH . '/htdocs/offline.php';
            exit(2);
        }

        return $instance;
    }

    private static function getAdapterClass($config)
    {
        $classname = isset($config['classname']) ? $config['classname'] : self::DEFAULT_ADAPTER;

        // legacy class name started with 'Db'
        if (substr($classname, 0, 2) == 'Db') {
            $classname = substr($classname, 2);
        }

        // append Adapter to classname
        if (substr($classname, -7) != 'Adapter') {
            $classname .= 'Adapter';
        }

        return 'Eventum\\Db\\Adapter\\' . $classname;
    }

    /**
     * Get database config.
     * load it from setup, fall back to legacy config.php constants
     *
     * @return array
     */
    public static function getConfig()
    {
        $setup = Setup::get();

        if (isset($setup['database'])) {
            $config = $setup['database']->toArray();
        } else {
            // legacy: import from constants
            $config = array(
                // database driver
                'driver'  => APP_SQL_DBTYPE,

                // connection info
                'hostname' => APP_SQL_DBHOST,
                'database' => APP_SQL_DBNAME,
                'username' => APP_SQL_DBUSER,
                'password' => APP_SQL_DBPASS,
                'port'     => APP_SQL_DBPORT,

                // table prefix
                'table_prefix' => APP_TABLE_PREFIX,

                /**
                 * @deprecated APP_DEFAULT_DB is deprecated (same as APP_SQL_DBNAME)
                 */
                //'default_db' => APP_DEFAULT_DB,
            );

            // save it back. this will effectively do the migration
            Setup::save(array('database' => $config));
        }

        return $config;
    }

    // assumed default if can't query from database
    const max_allowed_packet = 8387584;

    /**
     * query database for 'max_allowed_packet'
     *
     * @return int
     */
    public static function getMaxAllowedPacket()
    {
        try {
            $stmt = "show variables like 'max_allowed_packet'";
            $res = self::getInstance(false)->getPair($stmt);
            $max_allowed_packet = (int) $res['max_allowed_packet'];
        } catch (DatabaseException $e) {
        }

        if (empty($max_allowed_packet)) {
            return self::max_allowed_packet;
        }

        return $max_allowed_packet;
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     * Tokens enclosed within double curly brackets are treated as table names, while
     * tokens enclosed within double square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced
     * with [[tablePrefix]].
     *
     * @param AdapterInterface $db
     * @param string $tablePrefix
     * @param string $sql
     * @return string
     * @see https://github.com/yiisoft/yii2/blob/2.0.0/framework/db/Connection.php#L761-L783
     * @internal
     */
    public static function quoteTableName(AdapterInterface $db, $tablePrefix, $sql)
    {
        $sql = preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) use ($db, $tablePrefix) {
                if (isset($matches[3])) {
                    return $db->quoteIdentifier($matches[3]);
                } else {
                    return str_replace('%', $tablePrefix, $db->quoteIdentifier($matches[2]));
                }
            },
            $sql
        );

        return $sql;
    }

    /**
     * Strip consecutive whitespace from query
     *
     * @param string $query
     * @return string
     */
    public static function filterQuery($query)
    {
        return preg_replace('/\s+/', ' ', $query);
    }

    /**
     * Method used to get the last inserted ID. This is a simple
     * wrapper to the mysql_insert_id function, as a work around to
     * the somewhat annoying implementation of PEAR::DB to create
     * separate tables to host the ID sequences.
     *
     * @return  integer The last inserted ID
     */
    public static function get_last_insert_id()
    {
        $stmt = 'SELECT last_insert_id()';
        $res = (integer) self::getInstance()->getOne($stmt);

        return $res;
    }

    /**
     * Returns the escaped version of the given string.
     *
     * @param   string $str The string that needs to be escaped
     * @param   bool $add_quotes Whether to add quotes around result as well
     * @return  string The escaped string
     */
    public static function escapeString($str, $add_quotes = false)
    {
        $db = self::getInstance();
        if ($db) {
            $res = $db->escapeSimple($str);
        } else {
            // as this is so low level (handled by offline page)
            // supply some fallback
            $res = null;
        }

        if ($add_quotes) {
            $res = "'" . $res . "'";
        }

        return $res;
    }

    /**
     * Helper to build SQL queries with variable length parameters
     *
     * @param array $params
     * @return string A SQL statement partial with placeholders: field1=?, field2=?, field3=? ...
     */
    public static function buildSet($params)
    {
        $partial = array();
        foreach (array_keys($params) as $key) {
            $partial[] = "$key=?";
        }

        return implode(', ', $partial);
    }

    /**
     * Helper to build SQL queries with SET of parameters
     *
     * @param array $params
     * @return string A SQL statement partial with placeholders: ?, ?, ? ...
     */
    public static function buildList($params)
    {
        return implode(', ', array_fill(0, count($params), '?'));
    }

    /**
     * Give valid ORDER BY parameter
     *
     * @param string $order
     * @param string $default
     * @return string
     */
    public static function orderBy($order, $default = 'DESC')
    {
        if (!in_array(strtoupper($order), array('ASC', 'DESC'))) {
            return $default;
        }

        return $order;
    }

    /**
     * Returns the SQL used to calculate the difference of 2 dates, not counting weekends.
     * This thing is truly a work of art, the type of art that throws lemon juice in your eye and then laughs.
     * If $end_date_field is null, the current date is used instead.
     *
     * @param   string $start_date_field The name of the field the first date is.
     * @param   string $end_date_field The name of the field where the second date is.
     * @return  string The SQL used to compare the 2 dates.
     */
    public static function getNoWeekendDateDiffSQL($start_date_field, $end_date_field = null)
    {
        if (!$end_date_field) {
            $end_date_field = "'" . Date_Helper::getCurrentDateGMT() . "'";
        }

        // this is crazy, but it does work. Anyone with a better solution email balsdorf@gmail.com
        $sql
            = "((UNIX_TIMESTAMP($end_date_field) - UNIX_TIMESTAMP($start_date_field)) - (CASE
            WHEN DAYOFWEEK($start_date_field) = 1 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))-1)/7) * 86400 * 2)
            WHEN DAYOFWEEK($start_date_field) = 2 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field)))/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 3 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+1)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 4 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+2)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 5 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+3)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 6 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+4)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 7 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))-2)/7) * 86400 *2)
        END) - (CASE
            WHEN DAYOFWEEK($start_date_field) = 7 THEN (86400 + (86400 - time_to_sec($start_date_field)))
            WHEN DAYOFWEEK($start_date_field) = 1 THEN (86400 - time_to_sec($start_date_field))
            ELSE 0
        END) - CASE
            WHEN DAYOFWEEK($end_date_field) = 7 THEN time_to_sec($end_date_field)
            WHEN DAYOFWEEK($end_date_field) = 1 THEN (86400 + time_to_sec($end_date_field))
            ELSE 0
        END)";

        return str_replace("\n", ' ', $sql);
    }
}
