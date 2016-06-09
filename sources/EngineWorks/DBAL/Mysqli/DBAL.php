<?php namespace EngineWorks\DBAL\Mysqli;

use mysqli;
use EngineWorks\DBAL\DBAL as AbstractDBAL;

/**
 * Mysqli implementation
 * @package EngineWorks\DBAL\Mysqli
 */
class DBAL extends AbstractDBAL
{

    /**
     * Contains the connection resource for mysqli
     * @var mysqli
     */
    protected $mysqli = null;

    /**
     * Contains the transaction level to do nested transactions
     * @var integer
     */
    protected $translevel = 0;

    public function connect()
    {
        // disconnect
        if ($this->isConnected()) {
            $this->disconnect();
        }
        // create the mysqli object without error reporting
        $prevErrorReporting = error_reporting(0);
//        This code result in problems with mysqli after disconnect
        $this->mysqli = mysqli_init();
        $this->mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $this->settings->get('connect-timeout'));
        $this->mysqli->real_connect(
            $this->settings->get('host'),
            $this->settings->get('user'),
            $this->settings->get('password'),
            $this->settings->get('database'),
            $this->settings->get('port'),
            $this->settings->get('socket'),
            $this->settings->get('flags')
        );
        // $this->mi = new mysqli($this->configHost, $this->configUser, $this->configPassword, $this->configCatalog);
        error_reporting($prevErrorReporting);
        // check for a instance of mysqli
        if (!$this->mysqli instanceof mysqli) {
            $this->logger->info("-- Connection fail");
            $this->logger->error("Cannot create mysqli object");
            return false;
        }
        // check there is not connection errors
        if ($this->mysqli->connect_errno) {
            $errormsg = "Connection fail [{$this->mysqli->connect_errno}] {$this->mysqli->connect_error}";
            $this->logger->info("-- " . $errormsg);
            $this->logger->error($errormsg);
            $this->mysqli = null;
            return false;
        }
        // OK, we are connected
        $this->logger->info("-- Connect and database select OK");
        // set encoding if needed
        if ('' !== $encoding = $this->settings->get('encoding')) {
            $this->logger->info("-- Setting encoding to $encoding;");
            $this->mysqli->query("SET character_set_client = $encoding;");
            $this->mysqli->query("SET character_set_results = $encoding;");
            $this->mysqli->query("SET character_set_connection = $encoding;");
            $this->mysqli->query("SET names $encoding;");
        }
        return true;
    }

    public function disconnect()
    {
        if ($this->mysqli instanceof mysqli) {
            $this->logger->info("-- Disconnection");
            @$this->mysqli->close();
        }
        $this->translevel = 0;
        $this->mysqli = null;
    }

    public function isConnected()
    {
        return ($this->mysqli instanceof mysqli); // and $this->mi->ping();
    }

    public function lastInsertedID()
    {
        return doubleval($this->mysqli->insert_id);
    }

    public function sqlString($variable)
    {
        if ($this->isConnected()) {
            return $this->mysqli->escape_string($variable);
        }
        // there are no function to escape without a link
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            $variable
        );
    }

    /**
     * Executes a query and return an object or resource native to the driver
     * This is the internal function to do the query according to the database functions
     * It's used by queryResult and queryAffectedRows methods
     * @param string $query
     * @return mixed
     */
    protected function queryDriver($query)
    {
        $this->logger->debug($query);
        if (false === $result = $this->mysqli->query($query)) {
            $this->logger->info("-- Query fail with SQL: $query");
            $this->logger->error("FAIL: $query\nLast message:" . $this->getLastMessage());
            return false;
        }
        return $result;
    }

    public function queryResult($query)
    {
        if (false !== $result = $this->queryDriver($query)) {
            return new Result($result);
        }
        return false;
    }

    protected function queryAffectedRows($query)
    {
        if (false !== $this->queryDriver($query)) {
            return $this->mysqli->affected_rows;
        }
        return false;
    }

    protected function getLastErrorMessage()
    {
        return (($this->isConnected())
            ? "[" . $this->mysqli->errno . "] " . $this->mysqli->error
            : "Cannot get the error because there are no active connection");
    }

    protected function sqlTableEscape($tablename, $astable)
    {
        return chr(96) . $tablename . chr(96) . (($astable) ? " AS " . $astable : "");
    }

    public function sqlConcatenate(...$strings)
    {
        if (!count($strings)) {
            return $this->sqlQuote("", self::TTEXT);
        }
        return "CONCAT(" . implode(", ", $strings) . ")";
    }


    public function sqlDatePart($part, $expression)
    {
        $format = "";
        switch (strtoupper($part)) {
            case "YEAR":
                $format = "%Y";
                break;
            case "MONTH":
                $format = "%m";
                break;
            case "FDOM":
                $format = "%Y-%m-01";
                break;
            case "FYM":
                $format = "%Y-%m";
                break;
            case "FYMD":
                $format = "%Y-%m-%d";
                break;
            case "DAY":
                $format = "%d";
                break;
            case "HOUR":
                $format = "%H";
                break;
            case "MINUTE":
                $format = "%i";
                break;
            case "SECOND":
                $format = "%s";
                break;
        }
        $sql = "";
        if ($format) {
            $sql = "DATE_FORMAT(" . $expression . ", '" . $format . "')";
        }
        return $sql;
    }

    public function sqlIf($condition, $truepart, $falsepart)
    {
        return "IF(" . $condition . ", " . $truepart . ", " . $falsepart . ")";
    }

    public function sqlIfNull($fieldname, $nullvalue)
    {
        return "IFNULL(" . $fieldname . ", " . $nullvalue . ")";
    }

    public function sqlIsNull($fieldvalue, $positive = true)
    {
        return $fieldvalue . " IS" . ((!$positive) ? " NOT" : "") . " NULL";
    }

    public function sqlLike($fieldName, $searchString, $wildcardBegin = true, $wildcardEnd = true)
    {
        return $fieldName . " LIKE '"
        . (($wildcardBegin) ? "%" : "") . $this->sqlString($searchString) . (($wildcardEnd) ? "%" : "") . "'";
    }

    public function sqlLimit($query, $requestedpage, $recordsperpage = 20)
    {
        $rpp = max(1, $recordsperpage);
        $query = rtrim($query, "; \t\n\r\0\x0B")
            . " LIMIT " . $this->sqlQuote($rpp * (max(1, $requestedpage) - 1), self::TINT)
            . ", " . $this->sqlQuote($rpp, self::TINT);
        return $query;
    }

    public function sqlQuote($variable, $commontype = self::TTEXT, $includenull = false)
    {
        if ($includenull and is_null($variable)) {
            return "NULL";
        }
        switch (strtoupper($commontype)) {
            case self::TTEXT: // is the most common type, put the case to avoid extra comparisons
                $return = "'" . $this->sqlString($variable) . "'";
                break;
            case self::TINT:
                $return = intval(str_replace([",", "$"], "", $variable), 10);
                break;
            case self::TNUMBER:
                $return = floatval(str_replace([",", "$"], "", $variable));
                break;
            case self::TBOOL:
                $return = ($variable) ? 1 : 0;
                break;
            case self::TDATE:
                $return = "'" . date("Y-m-d", $variable) . "'";
                break;
            case self::TTIME:
                $return = "'" . date("H:i:s", $variable) . "'";
                break;
            case self::TDATETIME:
                $return = "'" . date("Y-m-d H:i:s", $variable) . "'";
                break;
            default:
                $return = "'" . $this->sqlString($variable) . "'";
        }
        return strval($return);
    }

    public function sqlQuoteIn($array, $commontype = self::TTEXT, $includenull = false)
    {
        if (!is_array($array) or count($array) == 0) {
            return false;
        }
        $return = "";
        for ($i = 0; $i < count($array); $i++) {
            $return .= (($i > 0) ? ", " : "") . $this->sqlQuote($array[$i], $commontype, $includenull);
        }
        return "(" . $return . ")";
    }

    public function sqlRandomFunc()
    {
        return "RAND()";
    }

    public function transBegin()
    {
        $this->logger->info("-- TRANSACTION BEGIN");
        $this->translevel++;
        if ($this->translevel != 1) {
            $this->logger->info("-- BEGIN (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute("BEGIN");
        }
    }

    public function transCommit()
    {
        $this->logger->info("-- TRANSACTION COMMIT");
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- COMMIT (not executed because there are {$this->translevel} transactions running)");
        } else {
            $this->execute("COMMIT");
            return true;
        }
        return false;
    }

    public function transRollback()
    {
        $this->logger->info("-- TRANSACTION ROLLBACK ");
        $this->execute("ROLLBACK");
        $this->translevel--;
        if ($this->translevel != 0) {
            $this->logger->info("-- ROLLBACK (this rollback is out of sync) [" . $this->translevel . "]");
            return false;
        }
        return true;
    }
}
