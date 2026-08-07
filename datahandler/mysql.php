<?php
    // Handler de MySQL de forsvar_webhooks.
    //
    // Este archivo nacio como copia entera del datahandler de forsvar_frontend: 42 funciones,
    // de las cuales el worker usa TRES (selectData, update_data, insert_row — ver worker.php y
    // utils/functions.php). Las otras 39 nunca se llamaron desde este repo, y 22 de ellas
    // interpolaban parametros directo en el SQL (party_id, company_id, table, filters, days,
    // startdate/enddate...). No eran explotables porque no habia forma de invocarlas, pero
    // cualquiera que cableara una la habria cableado ya vulnerable — y el codigo muerto no
    // avisa de que lo esta.
    //
    // Se conservan solo las tres en uso. Si hace falta otra, se trae del frontend Y se le
    // arregla el binding en el mismo movimiento; no se restaura este archivo entero.
    //
    // Nota sobre la interpolacion que queda: selectData/update_data/insert_row interpolan el
    // NOMBRE de tabla, que en los 7 call sites es un literal del codigo ("webhooks_events",
    // "party_onboarding_log"). Los VALORES van escapados con mysqli_real_escape_string.

    
    function selectData($conn, $tableName, $columns = '*', $filters = array(), $orderBy = null, $limit = null, $offset = null) {
        $conditions = [];

        foreach ($filters as $rawKey => $value) {
            $useDate = false;
            if (strpos($rawKey, "DATE:") === 0) {
                $useDate = true;
                $rawKey = substr($rawKey, 5);
            }

            if (preg_match('/^(.+?)\s*(>=|<=|<>|>|<|=)?$/', $rawKey, $matches)) {
                $key = mysqli_real_escape_string($conn, $matches[1]);
                $operator = isset($matches[2]) ? $matches[2] : '=';

                $field = $useDate ? "DATE($key)" : $key;

                if ($value === "<actualmonth>") {
                    $conditions[] = "$key = DATE_FORMAT(NOW(), '%m-%Y')";
                } elseif (is_array($value)) {
                    $escapedValues = array_map(function($v) use ($conn) {
                        return "'" . mysqli_real_escape_string($conn, $v) . "'";
                    }, $value);
                    $conditions[] = "$field IN (" . implode(", ", $escapedValues) . ")";
                } else {
                    $escapedValue = mysqli_real_escape_string($conn, $value);
                    $conditions[] = "$field $operator '$escapedValue'";
                }
            }
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";
        $limitOffsetClause = "";

        if (!is_null($limit)) {
            $limitOffsetClause .= " LIMIT " . intval($limit);
            if (!is_null($offset)) {
                $limitOffsetClause .= " OFFSET " . intval($offset);
            }
        }

        $sql = "SELECT $columns FROM $tableName $whereClause $orderByClause $limitOffsetClause";

        $result = mysqli_query($conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return [
            "data" => $data,
            "error" => $error
        ];
    }


    // IN 
    // $filters = [
    //     "status" => ["active", "pending"],              // → IN ('active', 'pending')
    //     "user_id >=" => 10,
    //     "DATE:created_at >" => "2024-01-01"
    // ];


    function update_data($conn, $tableName, $data, $conditions) {
        try {
            $set = [];
            $error = '';
            $sql = '';

            if (empty($tableName) || empty($data) || empty($conditions)) {
                throw new Exception("Missing table name, data, or conditions");
            }

            foreach ($data as $key => $value) {
                $safeKey = mysqli_real_escape_string($conn, $key);
                $safeValue = mysqli_real_escape_string($conn, $value);
                $set[] = "$safeKey = '$safeValue'";
            }

            $setClause = implode(", ", $set);
            $setClause = str_replace("'NOW()'", "NOW()", $setClause);
            $setClause = str_replace("'NULL'", "NULL", $setClause);
            $setClause = str_replace("'DATE_ADD(NOW(), INTERVAL 1 month)'", "DATE_ADD(NOW(), INTERVAL 1 MONTH)", $setClause);
            $setClause = str_replace("'DATE_ADD(NOW(), INTERVAL 1 year)'", "DATE_ADD(NOW(), INTERVAL 1 YEAR)", $setClause);

            $whereArray = [];
            foreach ($conditions as $condition) {
                $whereArray[] = mysqli_real_escape_string($conn, $condition);
            }

            $whereConditions = implode(" AND ", $whereArray);
            $sql = "UPDATE `$tableName` SET $setClause WHERE $whereConditions";

            mysqli_query($conn, "SET NAMES 'utf8'");
            $result = mysqli_query($conn, $sql);

            if (!$result) {
                throw new Exception(mysqli_error($conn));
            }

            return [
                "success" => true,
                "sql" => $sql,
                "error" => ""
            ];
        } catch (Throwable $e) {
            return [
                "success" => false,
                "sql" => $sql ?? '',
                "error" => $e->getMessage()
            ];
        }
    }


    function insert_row($conn,$table,$data) {

        foreach ($data as &$value) {
            if (isset($value)) {
                $value = mysqli_real_escape_string($conn,$value);
            } else {
                $value = "";
            }
        }

        // Build the SQL query
        $columns = implode(", ", array_keys($data));    
        $values = "'" . implode("', '", $data) . "'";
        $values = str_replace("'NOW()'","NOW()",$values);
        $values = str_replace("'NULL'","NULL",$values);
        $values = str_replace("'<actualmonth>'","DATE_FORMAT(NOW(), '%m-%Y')",$values);
        $values = str_replace("'DATE_ADD(NOW(), INTERVAL 1 month)'","DATE_ADD(NOW(), INTERVAL 1 MONTH)",$values);
        $values = str_replace("'DATE_ADD(NOW(), INTERVAL 1 year)'","DATE_ADD(NOW(), INTERVAL 1 YEAR)",$values);
        $values = str_replace("'DATE_ADD(NOW(), INTERVAL 14 DAY)'","DATE_ADD(NOW(), INTERVAL 14 DAY)",$values);
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";  

        //echo $sql;
        // Execute the query
        if (mysqli_query($conn, $sql)) {
            $error = '';
        } else {
            $error = mysqli_error($conn);
        }


        //$result = mysqli_query($conn,$sql);
        return array(
            "sql"=>$sql,
            "error"=>$error,
            "lastid"=>mysqli_insert_id($conn)
        );

    
    }

?>
