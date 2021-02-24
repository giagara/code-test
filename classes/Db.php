<?php



class Db
{
    /** @var PDO */
    protected $link;

    /* @var PDOStatement */
    protected $result;

    /**
     * Returns a new PDO object (database link)
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param int $timeout
     * @return PDO
     */
    protected static function _getPDO($host, $user, $password, $dbname, $timeout = 5)
    {
        $dsn = 'mysql:';
        if ($dbname) {
            $dsn .= 'dbname='.$dbname.';';
        }
        if (preg_match('/^(.*):([0-9]+)$/', $host, $matches)) {
            $dsn .= 'host='.$matches[1].';port='.$matches[2];
        } elseif (preg_match('#^.*:(/.*)$#', $host, $matches)) {
            $dsn .= 'unix_socket='.$matches[1];
        } else {
            $dsn .= 'host='.$host;
        }

        return new PDO($dsn, $user, $password, array(PDO::ATTR_TIMEOUT => $timeout, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
    }

    /**
     * Tries to connect to the database
     *
     * @return PDO
     */
    public function connect()
    {
        try {
            $this->link = $this->_getPDO($this->server, $this->user, $this->password, $this->database, 5);
        } catch (PDOException $e) {
            throw new Exception('Link to database cannot be established:'.$e->getMessage(), 1);
        }

        // UTF-8 support
        if ($this->link->exec('SET NAMES \'utf8\'') === false) {
            throw new Exception('Fatal error: no utf-8 support. Please check your server configuration.', 1);
        }

        $this->link->exec('SET SESSION sql_mode = \'\'');

        return $this->link;
    }

    /**
     * Destroys the database connection link
     *
     * @return void
     */
    public function disconnect()
    {
        unset($this->link);
    }

    /**
     * Perform a query on the database engine
     *
     * @param string $sql
     * @return PDOStatement
     */
    protected function _query($sql)
    {
        return $this->link->query($sql);
    }

    /**
     * Execute a query and get result resource
     *
     * @return bool|mysqli_result|PDOStatement|resource
     */
    public function query($sql)
    {
        if ($sql instanceof DbQuery) {
            $sql = $sql->build();
        }

        $this->result = $this->_query($sql);

        if (!$this->result && $this->getNumberError() == 2006) {
            if ($this->connect()) {
                $this->result = $this->_query($sql);
            }
        }

        if (_ENV_DEV_) {
            $this->displayError($sql);
        }

        return $this->result;
    }

    /**
     * Displays last SQL error
     *
     * @param string|bool $sql
     * @throws Exception
     */
    public function displayError($sql = false)
    {
        $errno = $this->getNumberError();
        if (_ENV_DEV_ && $errno) {
            if ($sql) {
                throw new Exception($this->getMsgError(), 1);
            }
        }
    }

    /**
     * Executes return the result of $sql as array
     *
     * @param string $sql Query to execute
     * @param bool $use_cache
     * @return array|false|null|mysqli_result|PDOStatement|resource
     */
    public function executeS($sql)
    {
        $this->result = false;
        $this->last_query = $sql;

        $this->result = $this->query($sql);

        if (!$this->result) {
            $result = false;
        } else {
            $result = $this->getAll($this->result);
            
        }

        return $result;
    }

    /**
     * Returns the next row from the result set.
     *
     * @param bool $result
     * @return array|false|null
     */
    public function nextRow($result = false)
    {
        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns all rows from the result set.
     *
     * @param bool $result
     * @return array|false|null
     */
    protected function getAll($result = false)
    {
        if (!$result) {
            $result = $this->result;
        }

        if (!is_object($result)) {
            return false;
        }

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns an associative array containing the first row of the query
     * This function automatically adds "LIMIT 1" to the query
     *
     * @param string $sql the select query (without "LIMIT 1")
     * @return array|bool|object|null
     */
    public function getRow($sql)
    {

        $sql = rtrim($sql, " \t\n\r\0\x0B;").' LIMIT 1';

        $this->result = $this->query($sql);
        if (!$this->result) {
            $result = false;
        } else {
            $result = $this->nextRow($this->result);
        }

        if (is_null($result)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Returns a value from the first row, first column of a SELECT query
     *
     * @param string $sql
     * @return string|false|null
     */
    public function getValue($sql)
    {
        if (!$result = $this->getRow($sql)) {
            return false;
        }

        return array_shift($result);
    }

    /**
     * Returns row count from the result set.
     *
     * @param PDOStatement $result
     * @return int
     */
    protected function _numRows($result)
    {
        return $result->rowCount();
    }

    /**
     * Returns ID of the last inserted row.
     *
     * @return string|int
     */
    public function Insert_ID()
    {
        return $this->link->lastInsertId();
    }

    /**
     * Return the number of rows affected by the last SQL query.
     *
     * @return int
     */
    public function Affected_Rows()
    {
        return $this->result->rowCount();
    }

    /**
     * Returns error message.
     *
     * @param bool $query
     * @return string
     */
    public function getMsgError($query = false)
    {
        $error = $this->link->errorInfo();
        return ($error[0] == '00000') ? '' : $error[2];
    }

    /**
     * Returns error code.
     *
     * @return int
     */
    public function getNumberError()
    {
        $error = $this->link->errorInfo();
        return isset($error[1]) ? $error[1] : 0;
    }

    /**
     * Escapes illegal characters in a string.
     *
     * @param string $str
     * @return string
     */
    public function escape($str)
    {
        $search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
        $replace = array("\\\\", "\\0", "\\n", "\\r", "\Z", "\'", '\"');
        return str_replace($search, $replace, $str);
    }


}
