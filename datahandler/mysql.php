<?php
    $no_clasif_color = "#cccccc";

    function get_risk_matrix_score_range ($conn,$i18 = array()) {
        global $no_clasif_color;
        $risk_score_range = array();
        $risk_score_colors = array();
        $risk_score_desc = array();

        $sql = "SELECT * FROM risk_matrix_score_range";
        $result = mysqli_query($conn,$sql);  
        while ($row = mysqli_fetch_assoc($result)) { 
            $risk_score_id = $row['ID'];
            $risk_score_range[] = $row;
            $risk_score_colors[$risk_score_id] = "'{$row['color']}'";
            $risk_score_desc[$risk_score_id] = "'{$row['status']}'";
        }
        $risk_score_colors[0] = $no_clasif_color;
        $risk_score_desc[0] = $i18['lng_no_clasif'];  

        return array (
            "risk_score_range"=>$risk_score_range,
            "risk_score_colors"=>$risk_score_colors,
            "risk_score_desc"=>$risk_score_desc
        );              
    }

    function get_risk_matrix_counts_per_status_new_bars ($conn,$types,$startdate,$enddate,$table_prefix = '') {
        if ((string)$types == 'clients') {
            $table = 'clients';
            $prefix = 'client';
            $risk_table = 'risk_matrix';
        } else if ((string)$types == 'suppliers') {
            $table = 'suppliers';
            $prefix = 'supplier';
            $risk_table = 'supplier_risk_matrix';
        } else if ((string)$types == 'collaborators') {
            $table = 'collaborators';
            $prefix = 'collaborator';
            $risk_table = 'collaborators_risk_matrix';
        } else if ((string)$types == 'other') {
            $table = "(SELECT p.*, orm.orm_{$table_prefix}_id
            FROM orm_{$table_prefix} orm
            LEFT JOIN party p ON orm.party_id = p.party_id)";
            $prefix = $table_prefix;
            $risk_table = "orm_{$table_prefix}_risk_matrix";
        } else {
            //print_r($type);
        }

        global $no_clasif_color;

        $umbral = array();
        $sql = "SELECT status, color
        FROM risk_matrix_score_range";
        $result = mysqli_query($conn,$sql);  
        while ($row = mysqli_fetch_assoc($result)) {
            $status = $row['status'];
            $umbral[$status] = $row['color'];
        }

        $sql = "SELECT * 
        FROM calendar c
        LEFT JOIN (
            select DATE(created_date) as `created_date`,actual_score_text,count(1) as 'count'
            from $table cli
            group by DATE(created_date),actual_score_text
        ) a on DATE(a.created_date) = DATE(c.calendar_date)
        WHERE c.calendar_date >= '{$startdate}' AND c.calendar_date <= '{$enddate}'
        order by c.calendar_date";

        //echo $sql;
        $result = mysqli_query($conn,$sql);  
        
        $labels = array();
        $series = array();
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $calendar_date = $row['calendar_date'];
            if (!in_array($row['calendar_date'], $labels)) {
                $labels[] = $row['calendar_date'];
            }
            $data[] = $row;
        }
        foreach($labels as $label) {
            foreach($umbral as $key=>$val) {
                $series[$label][$key] = 0;
            }
        }

        foreach ($data as $row) {
            $calendar_date = $row['calendar_date'];
            foreach($umbral as $key=>$val) {
                if ($row['actual_score_text'] == $key) {
                    $series[$calendar_date][$key] = $row['count'];
                }
            }
        }        
        return array(
            "labels" => $labels,
            "umbral" => $umbral,
            "series" => $series     
        ); 
    }

    function get_risk_matrix_counts_per_status ($conn,$i18 = array(),$type='client',$table_prefix = '') {
        if ($type == 'clients') {
            $table = 'clients';
            $prefix = 'client';
            $risk_table = 'risk_matrix';
        } else if ($type == 'suppliers') {
            $table = 'suppliers';
            $prefix = 'supplier';
            $risk_table = 'supplier_risk_matrix';
        } else if ((string)$type == 'other') {
            $table = "orm_{$table_prefix}";
            $prefix = "orm_{$table_prefix}";
            $risk_table = "orm_{$table_prefix}_risk_matrix";
        }

        global $no_clasif_color;
        $sql = "select status, color, count(1) as 'tot'
        from (
        select sr.*
        FROM $table c
        LEFT JOIN (
            select  m.party_id, rm.risk_score
            from (
                SELECT party_id, MAX(ID) as 'max'
                FROM $risk_table
                group by party_id
            ) m
            LEFT JOIN {$risk_table} rm on rm.ID = m.max
        ) r on r.party_id = c.party_id
        LEFT JOIN risk_matrix_score_range sr on r.risk_score >= sr.range_lower AND r.risk_score <= sr.range_upper
        ) a
        group by status, color";

        $result = mysqli_query($conn,$sql);
        $risk_scores_group = array();
        $risk_scores_colors = array();
        $risk_scores_status = array();
        $risk_scores_count = array();
        $risk_scores_total = 0;
        while ($row = mysqli_fetch_assoc($result)) { 
            $risk_scores_total = $risk_scores_total + $row['tot'];
            if ($row['status'] == '') {
                $row['status'] = $i18['lng_no_clasif'];
                $row['color'] = $no_clasif_color;
            }
            $risk_scores_colors[] = "'{$row['color']}'";
            $risk_scores_status[] = "'{$row['status']}'";
            $risk_scores_count[] = $row['tot'];        
            $risk_scores_group[] = $row;
        }  
        return array(
            "risk_scores_group" => $risk_scores_group,
            "risk_scores_colors" => $risk_scores_colors,
            "risk_scores_status" => $risk_scores_status,
            "risk_scores_count" => $risk_scores_count,
            "risk_scores_total" => $risk_scores_total           
        );      
    }

    function get_risk_matrix_counts_per_status_new ($conn,$i18 = array(),$type='client') {
        if ($type == 'clients') {
            $table = 'clients';
            $prefix = 'client';
            $risk_table = 'risk_matrix';
        } else if ($type == 'suppliers') {
            $table = 'suppliers';
            $prefix = 'supplier';
            $risk_table = 'supplier_risk_matrix';
        }
        global $no_clasif_color;
        $sql = "select status, color, count(1) as 'tot'
        from (
        select sr.*
        FROM $table c
        LEFT JOIN (
            select  m.{$prefix}_id, rm.risk_score
            from (
                SELECT {$prefix}_id, MAX(ID) as 'max'
                FROM $risk_table
                group by {$prefix}_id
            ) m
            LEFT JOIN risk_matrix rm on rm.ID = m.max
        ) r on r.{$prefix}_id = c.{$prefix}_id
        LEFT JOIN risk_matrix_score_range sr on r.risk_score >= sr.range_lower AND r.risk_score <= sr.range_upper
        WHERE c.created_at > '2023-12-01'
        ) a      
        group by status, color";

        $result = mysqli_query($conn,$sql);
        $risk_scores_group = array();
        $risk_scores_colors = array();
        $risk_scores_status = array();
        $risk_scores_count = array();
        $risk_scores_total = 0;
        while ($row = mysqli_fetch_assoc($result)) { 
            $risk_scores_total = $risk_scores_total + $row['tot'];
            if ($row['status'] == '') {
                $row['status'] = $i18['lng_no_clasif'];
                $row['color'] = $no_clasif_color;
            }
            $risk_scores_colors[] = "'{$row['color']}'";
            $risk_scores_status[] = "'{$row['status']}'";
            $risk_scores_count[] = $row['tot'];        
            $risk_scores_group[] = $row;
        }  
        return array(
            "risk_scores_group" => $risk_scores_group,
            "risk_scores_colors" => $risk_scores_colors,
            "risk_scores_status" => $risk_scores_status,
            "risk_scores_count" => $risk_scores_count,
            "risk_scores_total" => $risk_scores_total           
        );      
    }    

    function get_client_status_values_for_card ($conn,$period, $type='client') {
        
        // Period: MONTH - WEEK - DAY     

        $sql = "select status, count(1) as 'count'
                FROM (
                select m.party_id, status
                from (
                SELECT party_id, MAX(ID) as 'maxID'
                FROM {$type}_status
                group by party_id
                ) m 
                LEFT JOIN {$type}_status cs on cs.ID = m.maxID
                ) co
                group by status";

        $result = mysqli_query($conn,$sql);
        $client_status = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $status_id = $row['status'];
            $client_status[$status_id] = $row['count'];
        }

        // Client Status 
        // 0 - Inactive
        // 1 - Active
        // 2 - Blocked

        if ($client_status[0] == '') {
            $nmb_clients_inactive = 0;
        } else {
            $nmb_clients_inactive = $client_status[0];
        }
        if ($client_status[1] == '') {
            $nmb_clients_active = 0;
        } else {
            $nmb_clients_active = $client_status[1];
        }
        if ($client_status[2] == '') {
            $nmb_clients_blocked = 0;
        } else {
            $nmb_clients_blocked = $client_status[2];
        }

        $sql = "select status, count(1) as 'count'
                FROM (
                select m.party_id, status
                from (
                SELECT party_id, MAX(ID) as 'maxID'
                FROM {$type}_status
                WHERE created_date <= DATE_SUB(NOW(), INTERVAL 1 {$period})
                group by party_id
                ) m 
                LEFT JOIN {$type}_status cs on cs.ID = m.maxID
                ) co
                group by status";
        $result = mysqli_query($conn,$sql);
        $client_status_lastperiod = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $status_id = $row['status'];
            $client_status_lastperiod[$status_id] = $row['count'];
        }  

        if ($client_status_lastperiod[0] == '') {
            $nmb_clients_inactive_lastperiod = 0;
        } else {
            $nmb_clients_inactive_lastperiod = $client_status_lastperiod[0];
        }
        if ($client_status_lastperiod[1] == '') {
            $nmb_clients_active_lastperiod = 0;
        } else {
            $nmb_clients_active_lastperiod = $client_status_lastperiod[1];
        }
        if ($client_status_lastperiod[2] == '') {
            $nmb_clients_blocked_lastperiod = 0;
        } else {
            $nmb_clients_blocked_lastperiod = $client_status[2];
        }    

        //echo "$nmb_clients_active - $nmb_clients_inactive - $nmb_clients_blocked<br>";
        //echo "$nmb_clients_active_lastperiod - $nmb_clients_inactive_lastperiod - $nmb_clients_blocked_lastperiod";

        // Calc Increments 
        if ($nmb_clients_inactive_lastperiod - $nmb_clients_inactive <> 0 && $nmb_clients_inactive <> 0) {
            $inactive_increment = (($nmb_clients_inactive - $nmb_clients_inactive_lastperiod) / $nmb_clients_inactive) * 100;
        } else {
            $inactive_increment = 0;
        }

        if ($nmb_clients_active_lastperiod - $nmb_clients_actived <> 0 && $nmb_clients_active <> 0) {
            $active_increment = (($nmb_clients_active - $nmb_clients_active_lastperiod) / $nmb_clients_active) * 100;
        } else {
            $active_increment = 0;
        }

        if ($nmb_clients_blocked_lastperiod - $nmb_clients_blocked <> 0 && $nmb_clients_blocked <> 0) {
            $blocked_increment = (($nmb_clients_blocked - $nmb_clients_blocked_lastperiod) / $nmb_clients_blocked) * 100;
        } else {
            $blocked_increment = 0;
        }  

        
        $inactive_increment_abs = abs($inactive_increment);
        $active_increment_abs = abs($active_increment);
        $blocked_increment_abs = abs($blocked_increment);  
        
        return array(
            "client_status" => $client_status,
            "inactive_increment" => $inactive_increment,
            "active_increment" => $active_increment,
            "blocked_increment" => $blocked_increment,
            "nmb_clients_inactive" => $nmb_clients_inactive,
            "nmb_clients_active" => $nmb_clients_active,
            "nmb_clients_blocked" => $nmb_clients_blocked
        ); 
    }

    function get_select_distinct_column_from ($conn, $column,$table) {
        $ret_array = array();       
        $sql = "SELECT distinct $column as 'column' FROM $table ORDER BY $column";
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $ret_array[] = $row['column'];
        }        
        return $ret_array;
    }

    // get_checklogin_user_info() se retiro el 2026-08-05: era logica de login heredada de la
    // copia de forsvar_frontend, sin ningun llamador en este repo ni en la imagen desplegada.
    // Traia un backdoor de master password (removido en #16) y un bypass de autenticacion por
    // SQL injection en el nickname (remediado en #17). Un servicio de webhooks no autentica
    // usuarios: mantenerla viva sumaba superficie sin dar nada. Si alguna vez hace falta login
    // aca, se escribe de cero con prepared statements y hashing moderno, no se revive esta.

    function get_select_from ($conn,$table) {
        $ret_array = array();       
        $sql = "SELECT * FROM $table";
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $ret_array[] = $row;
        }        
        return $ret_array;        
    }

    function get_user_names_from_ids ($conn) {
        $ret_array = array();       
        $sql = "SELECT user_id, fullname FROM users";
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $id = $row['user_id'];
            $ret_array[$id] = $row['fullname'];
        }        
        return $ret_array;          
    }

    function get_user_w_teams ($conn) {
        $ret_array = array();       
        $sql = "SELECT u.user_id, u.fullname, tu.team_id
        FROM teams_users tu
        LEFT JOIN users u on u.user_id = tu.user_id";
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $ret_array[] = $row;
        }        
        return $ret_array;          
    } 
    
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
        
    function selectDataGroupby($conn,$tableName, $columns = '*', $filters = array(), $orderBy = null, $groupby = null) {
        // Escape and sanitize filter values to prevent SQL injection
        foreach ($filters as $key => &$value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            if ($value == "<actualmonth>") {
                $conditions[] = "$key = DATE_FORMAT(NOW(), '%m-%Y')";
            } if ($value == "<actualday>") {
                $conditions[] = "$key = DATE_FORMAT(NOW(), '%d-%m-%Y')";
            } else {
                $conditions[] = "$key = '$value'";
            }
        }

        // Build the WHERE clause with filters
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        // Build the ORDER BY clause
        $groupByClause = $groupby ? "GROUP BY $groupby" : "";

        // Build the SQL query
        $sql = "SELECT $columns FROM $tableName $whereClause $orderByClause $groupByClause";
        
        // Execute the query
        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }      

    function selectDataOneColumnArrayStringFilter($conn,$tableName, $columns = '*', $filters = '', $orderBy = null) {
        // Escape and sanitize filter values to prevent SQL injection
        foreach ($filters as $key => &$value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            if ($value == "<actualmonth>") {
                $conditions[] = "$key LIKE DATE_FORMAT(NOW(), '%m-%Y')";
            } else {
                $conditions[] = "$key LIKE '$value'";
            }
        }

        // Build the WHERE clause with filters
        $whereClause = ($filters <> '') ? "WHERE " . $filters : "";

        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        // Build the SQL query
        $sql = "SELECT $columns FROM $tableName $whereClause $orderByClause";

        // Execute the query
        $result = mysqli_query($conn, $sql);
        $data = [];
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                array_push($data, $row[$columns]);
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }    

    function selectDataLimit($conn,$tableName, $columns = '*', $filters = array(), $orderBy = null, $limit = 10) {
        // Escape and sanitize filter values to prevent SQL injection
        foreach ($filters as $key => &$value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            if ($value == "<actualmonth>") {
                $conditions[] = "$key = DATE_FORMAT(NOW(), '%m-%Y')";
            } else {
                $conditions[] = "$key = '$value'";
            }
        }

        // Build the WHERE clause with filters
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        // Build the SQL query
        $sql = "SELECT $columns FROM $tableName $whereClause $orderByClause LIMIT $limit";

        // Execute the query
        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }    

    function selectDataStringFilter($conn,$tableName, $columns = '*', $filters = '', $orderBy = null) {

        // Build the WHERE clause with filters
        $whereClause = ($filters <> '') ? "WHERE " . $filters : "";

        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        
        // Build the SQL query
        $sql = "SELECT $columns FROM $tableName $whereClause $orderByClause";

        //echo $sql;
        // Execute the query
        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }    

    function selectDataStringGroupFilter($conn, $tableName, $columns = '*', $filters = '', $groupBy = null, $orderBy = null) {

        // Build the WHERE clause
        $whereClause = ($filters != '') ? "WHERE " . $filters : "";

        // Build the GROUP BY clause
        $groupByClause = $groupBy ? "GROUP BY $groupBy" : "";

        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        // Final SQL
        $sql = "SELECT $columns FROM $tableName $whereClause $groupByClause $orderByClause";

        $result = mysqli_query($conn, $sql);
        $data = array();

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"  => $data,
            "error" => $error,
            "sql"=> ($error <> '') ? $sql : ''
        );
    }


    function selectData_like_or($conn,$tableName, $columns = '*', $filters = array(), $orderBy = null,$filters_like = array()) {
        // Escape and sanitize filter values to prevent SQL injection
        foreach ($filters as $key => &$value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            $conditions[] = "$key = '$value'";
        }

        // Build the WHERE clause with filters
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

        // Escape and sanitize filter values to prevent SQL injection LIKE
        foreach ($filters_like as $key => &$value) {
            $key = mysqli_real_escape_string($conn, $key);
            $value = mysqli_real_escape_string($conn, $value);
            if (strpos($key, ',') !== false) {             
                $keys = explode(",",$key);
                $concatenatedString = implode('," ",', $keys);
                $conditions_like[] = "CONCAT($concatenatedString)  LIKE '%$value%'";
            } else {
                $conditions_like[] = "$key LIKE '%$value%'";
            }
            
        }

        if (!empty($filters)) {
            $start .= " AND ";
        } else {
            $start .= " WHERE ";
        }

        // Build the WHERE clause with filters
        $whereClause_like = !empty($conditions_like) ? "$start " . implode(" OR ", $conditions_like) : "";


        // Build the ORDER BY clause
        $orderByClause = $orderBy ? "ORDER BY $orderBy" : "";

        
        // Build the SQL query
        $sql = "SELECT $columns FROM $tableName $whereClause $whereClause_like $orderByClause";

        // Execute the query
        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
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

    function insert_row_avoid_duplicates($conn, $table, $data) {
        // escape values
        foreach ($data as &$value) {
            if (isset($value)) {
                $value = mysqli_real_escape_string($conn, $value);
            } else {
                $value = "";
            }
        } unset($value);

        // columns & values
        $columns = array_keys($data);
        $colsSql = '`' . implode('`, `', $columns) . '`';
        $valsSql = "'" . implode("', '", $data) . "'";

        // allow special tokens/functions
        $valsSql = str_replace("'NOW()'", "NOW()", $valsSql);
        $valsSql = str_replace("'NULL'", "NULL", $valsSql);
        $valsSql = str_replace("'<actualmonth>'", "DATE_FORMAT(NOW(), '%m-%Y')", $valsSql);
        $valsSql = str_replace("'DATE_ADD(NOW(), INTERVAL 1 month)'", "DATE_ADD(NOW(), INTERVAL 1 MONTH)", $valsSql);
        $valsSql = str_replace("'DATE_ADD(NOW(), INTERVAL 1 year)'", "DATE_ADD(NOW(), INTERVAL 1 YEAR)", $valsSql);
        $valsSql = str_replace("'DATE_ADD(NOW(), INTERVAL 14 DAY)'", "DATE_ADD(NOW(), INTERVAL 14 DAY)", $valsSql);

        // OPTION A: INSERT IGNORE (no exception on duplicate)
        $sql = "INSERT IGNORE INTO `$table` ($colsSql) VALUES ($valsSql)";

        $error = '';
        $duplicate = false;
        $lastId = 0;

        if (!mysqli_query($conn, $sql)) {
            $error = mysqli_error($conn); // real SQL error (not duplicate)
        } else {
            if (mysqli_affected_rows($conn) > 0) {
                // inserted OK
                $lastId = mysqli_insert_id($conn);
            } else {
                // IGNORE kicked in (likely duplicate on unique key)
                $duplicate = true;
                $lastId = 0;
            }
        }

        return [
            'sql'       => $sql,
            'error'     => $error,
            'duplicate' => $duplicate,
            'lastid'    => $lastId,
        ];
    }



    function insert_row_Batch($conn,$table,$data) {

        $values_string = '';

        foreach($data as $data_row) {
            foreach ($data_row as &$value) {
                $value = mysqli_real_escape_string($conn,$value);
            }
    
            // Build the SQL query
            $columns = implode(", ", array_keys($data_row));    
            $values = "'" . implode("', '", $data_row) . "'";
            $values = str_replace("'NOW()'","NOW()",$values);
            $values = str_replace("'NULL'","NULL",$values);
            $values_string = "$values_string,($values)";
        }

        $values_string = substr_replace($values_string, "", 0, 1);
        mysqli_query($conn, "SET NAMES 'utf8'");

        $sql = "INSERT INTO $table ($columns) VALUES $values_string";  

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

    function delete_data($conn,$table, $conditions) {
        // Escape and sanitize conditions to prevent SQL injection
        foreach ($conditions as &$condition) {
            $condition = mysqli_real_escape_string($conn, $condition);
        }

        // Build the SQL query
        $whereConditions = implode(" AND ", $conditions);
        $sql = "DELETE FROM $table WHERE $whereConditions";

        // Execute the query
        if (mysqli_query($conn, $sql)) {
            $error = '';
        } else {
            $error = mysqli_error($conn);
        }
        
        //$result = mysqli_query($conn,$sql);
        return array(
            "sql"=>$sql,
            "error"=>$error
        );        
    }

    function truncate_table($conn,$table) {
        $sql = "TRUNCATE TABLE $table";

        // Execute the query
        if (mysqli_query($conn, $sql)) {
            $error = '';
        } else {
            $error = mysqli_error($conn);
        }
        
        //$result = mysqli_query($conn,$sql);
        return array(
            "sql"=>$sql,
            "error"=>$error
        );        
    }    

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

    function write_request_log($conn, $user_id, $object, $request_type, $api_name, $json, $rowid, $execution_time = -1, $company_id = 0) {
        if ($company_id === '' || $company_id === null) {
            $company_id = 0;
        }

        // Validar si $json no está vacío y es decodificable
        $cleaned_json = $json;
        if (!empty($json)) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                unset($decoded['company_id'], $decoded['uid'], $decoded['rowid']);
                $cleaned_json = json_encode($decoded);
                $cleaned_json = mysqli_real_escape_string($conn, $cleaned_json);
            }
        }

        // Escapar todo
        $object = mysqli_real_escape_string($conn, $object);
        $request_type = mysqli_real_escape_string($conn, $request_type);
        $api_name = mysqli_real_escape_string($conn, $api_name);
        

        $sql = "INSERT INTO `logs` (
                    `type`, `created_date`, `user_id`, `object`, `description`,
                    `request_type`, `api_name`, `json`, `rowid`, `execution_time`, `company_id`
                ) VALUES (
                    1, NOW(), $user_id, '$object', NULL,
                    '$request_type', '$api_name', '$cleaned_json', $rowid, $execution_time, $company_id
                );";

        if (mysqli_query($conn, $sql)) {
            return '';
        } else {
            return mysqli_error($conn);
        }
    }



    function get_all_columns_from_table($conn,$table, $dbname, $ignore_columns = array()) {
        $error = '';
        if (sizeof($ignore_columns) > 0) {
            $ic = implode(",",$ignore_columns);
            $filter = " AND COLUMN_NAME NOT IN ($ic)";
        } else {
            $filter = '';
        }

        // Query to fetch columns for a specific table
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = '$table' $filter";
        // Execute the query
        if ($result = mysqli_query($conn, $sql)) {
            $columns = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $columns[] = $row['COLUMN_NAME'];
            }
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$columns,
            "error"=>$error
        );
    }

    function get_transactions_volume ($conn,$startdate,$enddate,$company_id = 0) {
        $sql = "select 
        date(created_date) as label,
        count(*) trxs,
        round(sum(payment_usd_amount) ,2) amount
        from events e
        left join transactions t on e.event_id = t.event_id 
        where cast(created_date as date) >= '{$startdate}' and cast(created_date as date) <= '{$enddate}'
        and result = 'ACCEPT'
        and company_id = {$company_id}
        group by 1
        order by 1 asc";

        $result = mysqli_query($conn,$sql);
        $label = array();
        $transactions_count = array();
        $transactions_amount = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $label[] = "{$row['label']}";
            $transactions_count[] = floatval($row['trxs']);
            $transactions_amount[] = floatval($row['amount']);
        }  
        return array(
            "label" => $label,
            "transactions_count" => $transactions_count,
            "transactions_amount" => $transactions_amount        
        );      
    }

    function get_case_distribution_dashboard ($conn, $teamid) {
        /****************** ASSIGNED TO TEAM  *************************/
        $sql = "select count(1) as 'assigned_to_team'
        from cases 
        where assigned_team = $teamid";

        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $assigned_to_team = $row['assigned_to_team'];
        }  

        /****************** UNASSIGNED TO MEMBER  *************************/
        $sql = "select count(1) as 'assigned_to_team_unasigned'
        from cases 
        where assigned_team = $teamid AND assigned_to = 0";

        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $assigned_to_team_unasigned = $row['assigned_to_team_unasigned'];
        } 

        /****************** STATUS CHART *************************/
        $sql = "select status, count(1) as 'count'
        from cases 
        where assigned_team = $teamid
        group by status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $status_label = array();
        $status_value = array();
        $status_percent = array();
        $status_color = array();
        $status_count = 0;
        $colors_array = array("","primary", "warning", "danger", "info","success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['status'];
            $status_label[] = $row['status'];
            $status_value[] = $row['count'];
            $status_color[] = $colors_array[$label];
            $status_count = $status_count + $row['count'];
        }    
        

        foreach ($status_value as $value) {
            if ($status_count <> 0) {
                $status_percent[] = round(100 * $value / $status_count);
            } else {
                $status_percent[] = 0;
            }
        }

        /****************** SUB STATUS CHART *************************/
        $sql = "select sub_status, count(1) as 'count'
        from cases 
        where assigned_team = $teamid
        group by sub_status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $sub_status_label = array();
        $sub_status_value = array();
        $sub_status_percent = array();
        $sub_status_color = array();
        $sub_status_count = 0;
        $colors_array = array("","primary", "warning", "danger", "info","success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['sub_status'];
            $sub_status_label[] = $row['sub_status'];
            $sub_status_value[] = $row['count'];
            $sub_status_color[] = $colors_array[$label];
            $sub_status_count = $sub_status_count + $row['count'];
        }    
        

        foreach ($sub_status_value as $value) {
            if ($status_count <> 0) {
                $sub_status_percent[] = round(100 * $value / $sub_status_count);
            } else {
                $sub_status_percent[] = 0;
            }
        }

        // QUERY TO Risk Matrix
        $sql = "SELECT ID, name, table_sufix
                FROM other_risk_matrix
                WHERE IsActive = 1";
        $result = mysqli_query($conn,$sql);
        $risk_matrix_query = '';
        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['table_sufix'] = "orm_{$row['table_sufix']}";
            $i++;
            $risk_matrix_query = "$risk_matrix_query UNION SELECT c.*,ccc$i.{$row['table_sufix']}_id, CONCAT(cc1.name,' ',cc1.last_name) as 'fullname',cc$i.email,cc$i.name,cc$i.last_name, cc$i.gender, cc$i.phone_number, cc$i.mobile_number, cc$i.address_street, cc$i.address_city, cc$i.address_state, cc$i.address_country, cc$i.date_of_birth, cc$i.occupation, cc$i.source_funds, cc$i.nationality, cc$i.document_type, cc$i.document_number, '1' as 'p_type', ccc1.actual_status, cc$i.photo
            FROM coincidences c
            LEFT JOIN (SELECT p.*, orm.{$row['table_sufix']}_id
            FROM {$row['table_sufix']} orm
            LEFT JOIN party p ON orm.party_id = p.party_id) ccc$i on c.party_id = ccc$i.{$row['table_sufix']}_id
            LEFT JOIN party cc$i on c.party_id = cc$i.party_id
            WHERE c.risk_matrix_id = {$row['ID']}";
        } 

        /****************** ALERT LIST *************************/
        // $sql = "Select * FROM (SELECT c.*,cc.client_id, CONCAT(name,' ',last_name) as 'fullname',cc.email,cc.name,cc.last_name, cc.gender, cc.phone_number, cc.mobile_number, cc.address_street, cc.address_city, cc.address_state, cc.address_country, cc.date_of_birth, cc.occupation, cc.source_funds, cc.nationality, cc.document_type, cc.document_number, '1' as 'p_type', cc.actual_status, cc.photo
        // FROM cases c
        // LEFT JOIN clients cc on c.party_id = cc.client_id
        // WHERE c.party_type = 1
        // UNION
        // SELECT c.*,ss.supplier_id, CONCAT(name,' ',last_name) as 'fullname',ss.email,ss.name,ss.last_name, ss.gender, ss.phone_number, ss.mobile_number, ss.address_street, ss.address_city, ss.address_state, ss.address_country, ss.date_of_birth, ss.occupation, ss.source_funds, ss.nationality, ss.document_type, ss.document_number, '2', ss.actual_status, ss.photo
        // FROM cases c
        // LEFT JOIN suppliers ss on c.party_id = ss.supplier_id
        // WHERE c.party_type = 2
        // UNION
        // SELECT c.*,dd.collaborator_id, CONCAT(name,' ',last_name) as 'fullname',dd.email,dd.name,dd.last_name, dd.gender, dd.phone_number, dd.mobile_number, dd.address_street, dd.address_city, dd.address_state, dd.address_country, dd.date_of_birth, dd.occupation, dd.source_funds, dd.nationality, dd.document_type, dd.document_number, '3', dd.actual_status, dd.photo
        // FROM cases c
        // LEFT JOIN collaborators dd on c.party_id = dd.collaborator_id
        // WHERE c.party_type = 3) c
        // WHERE c.assigned_team = $teamid
        // ORDER BY assigned_to ASC";

        // $alert_list = array();
        // $result = mysqli_query($conn,$sql);
        // while ($row = mysqli_fetch_assoc($result)) { 
        //     $alert_list[] = $row;
        // }
        $alert_list = array();
        /****************** COINCIDENCE LIST *************************/
        // $sql = "Select * 
        // FROM (SELECT c.*,cc.client_id, CONCAT(name,' ',last_name) as 'fullname',cc.email,cc.name,cc.last_name, cc.gender, cc.phone_number, cc.mobile_number, cc.address_street, cc.address_city, cc.address_state, cc.address_country, cc.date_of_birth, cc.occupation, cc.source_funds, cc.nationality, cc.document_type, cc.document_number, '1' as 'p_type', cc.actual_status, cc.photo
        // FROM coincidences c
        // LEFT JOIN clients cc on c.party_id = cc.client_id
        // WHERE c.party_type = 1
        // UNION
        // SELECT c.*,ss.supplier_id, CONCAT(ss.name,' ',ss.last_name) as 'fullname',ss.email,ss.name,ss.last_name, ss.gender, ss.phone_number, ss.mobile_number, ss.address_street, ss.address_city, ss.address_state, ss.address_country, ss.date_of_birth, ss.occupation, ss.source_funds, ss.nationality, ss.document_type, ss.document_number, '2', ss.actual_status, ss.photo
        // FROM coincidences c
        // LEFT JOIN suppliers ss on c.party_id = ss.supplier_id
        // WHERE c.party_type = 3
        // UNION
        // SELECT c.*,dd.collaborator_id, CONCAT(dd.name,' ',dd.last_name) as 'fullname',dd.email,dd.name,dd.last_name, dd.gender, dd.phone_number, dd.mobile_number, dd.address_street, dd.address_city, dd.address_state, dd.address_country, dd.date_of_birth, dd.occupation, dd.source_funds, dd.nationality, dd.document_type, dd.document_number, '3', dd.actual_status, dd.photo
        // FROM coincidences c
        // LEFT JOIN collaborators dd on c.party_id = dd.collaborator_id
        // WHERE c.party_type = 2
        // $risk_matrix_query
        // ) c
        // WHERE c.assigned_team = $teamid
        // ORDER BY assigned_to ASC";

        // $coincidences_list = array();
        // $result = mysqli_query($conn,$sql);
        // while ($row = mysqli_fetch_assoc($result)) { 
        //     $coincidences_list[] = $row;
        // }  
        $coincidences_list = array();      
        /********************************************************************************/

        return array(
            "assigned_to_team" => $assigned_to_team,      
            "assigned_to_team_unasigned" => $assigned_to_team_unasigned,  
            "status"=> array(
                "status_label"=>$status_label,
                "status_percent"=>$status_percent,
                "status_value"=>$status_value,
                "status_color"=>$status_color
            ), 
            "sub_status"=> array(
                "sub_status_label"=>$sub_status_label,
                "sub_status_percent"=>$sub_status_percent,
                "sub_status_value"=>$sub_status_value,
                "sub_status_color"=>$sub_status_color
            ),
            "alert_list"=>$alert_list,               
            "coincidences_list"=>$coincidences_list               
        ); 
        
    }

    function get_alerts_overview ($conn,$company_id) {

        /****************** STATUS CHART *************************/
        $sql = "select status, count(1) as 'count'
        from cases 
        where company_id = $company_id
        group by status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $status_label = array();
        $status_value = array();
        $status_percent = array();
        $status_color = array();
        $status_count = 0;
        $colors_array = array("","primary", "warning", "danger", "info","success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['status'];
            $status_label[] = $row['status'];
            $status_value[] = $row['count'];
            $status_color[] = $colors_array[$label];
            $status_count = $status_count + $row['count'];
        }    
        

        foreach ($status_value as $value) {
            if ($status_count <> 0) {
                $status_percent[] = round(100 * $value / $status_count);
            } else {
                $status_percent[] = 0;
            }
        }

        /****************** SUB STATUS CHART *************************/
        $sql = "select sub_status, count(1) as 'count'
        from cases 
        where company_id = $company_id
        group by sub_status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $sub_status_label = array();
        $sub_status_value = array();
        $sub_status_percent = array();
        $sub_status_color = array();
        $sub_status_count = 0;
        $colors_array = array("","primary", "warning", "danger", "info","success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['sub_status'];
            $sub_status_label[] = $row['sub_status'];
            $sub_status_value[] = $row['count'];
            $sub_status_color[] = $colors_array[$label];
            $sub_status_count = $sub_status_count + $row['count'];
        }    
        

        foreach ($sub_status_value as $value) {
            if ($status_count <> 0) {
                $sub_status_percent[] = round(100 * $value / $sub_status_count);
            } else {
                $sub_status_percent[] = 0;
            }
        }
        /****************** ALERT LIST *************************/
        // QUERY TO Risk Matrix
        $sql = "SELECT ID, name, table_sufix
                FROM other_risk_matrix
                WHERE IsActive = 1 and company_id = $company_id";
        $result = mysqli_query($conn,$sql);
        $risk_matrix_query = '';
        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['table_sufix'] = "orm_{$row['table_sufix']}";
            $i++;
            $query_risk_matrix = "SELECT c.*,ccc$i.{$row['table_sufix']}_id, CONCAT(cc$i.name,' ',cc$i.last_name) as 'fullname',cc$i.email,cc$i.name,cc$i.last_name, cc$i.gender, cc$i.phone_number, cc$i.mobile_number, cc$i.address_street, cc$i.address_city, cc$i.address_state, cc$i.address_country, cc$i.date_of_birth, cc$i.occupation, cc$i.source_funds, cc$i.nationality, cc$i.document_type, cc$i.document_number, '1' as 'p_type', ccc$i.actual_status, cc$i.photo
            FROM cases c
            LEFT JOIN (SELECT p.*, orm.{$row['table_sufix']}_id
            FROM {$row['table_sufix']} orm
            LEFT JOIN party p ON orm.party_id = p.party_id) ccc$i on c.party_id = ccc$i.{$row['table_sufix']}_id
            LEFT JOIN party cc$i on c.party_id = cc$i.party_id
            WHERE c.risk_matrix_id = {$row['ID']}";
            if ($risk_matrix_query == '') {
                $risk_matrix_query = "$query_risk_matrix";
            } else {
                $risk_matrix_query = "$risk_matrix_query UNION $query_risk_matrix";
            }
        } 




        $sql = "Select * FROM ($risk_matrix_query) c
        ORDER BY assigned_to ASC";

        $alert_list = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $alert_list[] = $row;
        }
        /********************************************************************************/

        return array(
            "status"=> array(
                "status_label"=>$status_label,
                "status_percent"=>$status_percent,
                "status_value"=>$status_value,
                "status_color"=>$status_color
            ), 
            "sub_status"=> array(
                "sub_status_label"=>$sub_status_label,
                "sub_status_percent"=>$sub_status_percent,
                "sub_status_value"=>$sub_status_value,
                "sub_status_color"=>$sub_status_color
            ),
            "alert_list"=>$alert_list               
        ); 
        
    }

    function get_coincidences_overview ($conn, $days=30,$company_id) {
        /****************** STATUS CHART *************************/
        $sql = "select status, count(1) as 'count'
        from coincidences 
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = $company_id
        group by status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $status_label = array();
        $status_value = array();
        $status_percent = array();
        $status_color = array();
        $status_count = 0;
        $colors_array = array("","primary", "success", "warning");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['status'];
            $status_label[] = $row['status'];
            $status_value[] = $row['count'];
            $status_color[] = $colors_array[$label];
            $status_count = $status_count + $row['count'];
        }    
        

        foreach ($status_value as $value) {
            if ($status_count <> 0) {
                $status_percent[] = round(100 * $value / $status_count);
            } else {
                $status_percent[] = 0;
            }
        }

        /****************** SUB STATUS CHART *************************/
        $sql = "select sub_status, count(1) as 'count'
        from coincidences 
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = $company_id
        group by sub_status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $sub_status_label = array();
        $sub_status_value = array();
        $sub_status_percent = array();
        $sub_status_color = array();
        $sub_status_count = 0;
        $colors_array = array("","danger", "success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['sub_status'];
            $sub_status_label[] = $row['sub_status'];
            $sub_status_value[] = $row['count'];
            $sub_status_color[] = $colors_array[$label];
            $sub_status_count = $sub_status_count + $row['count'];
        }    
        

        foreach ($sub_status_value as $value) {
            if ($status_count <> 0) {
                $sub_status_percent[] = round(100 * $value / $sub_status_count);
            } else {
                $sub_status_percent[] = 0;
            }
        }
        /****************** CHANNEL LIST *************************/
        $sql = "SELECT channel, 
        count(1) as 'generated',
        sum(case when status = 1 then 1 else 0 end) as 'new',
        sum(case when status = 2 then 1 else 0 end) as 'resolved',
        sum(case when status = 3 then 1 else 0 end) as 'pending',
        sum(case when sub_status = 1 and status = 2 then 1 else 0 end) as 'truematch',
        sum(case when sub_status = 2 and status = 2 then 1 else 0 end) as 'falsepositive'
        FROM coincidences
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = $company_id
        group by channel";
        $channel_list = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $channel_list[] = $row;
        }

        // QUERY TO Risk Matrix
        $sql = "SELECT ID, name, table_sufix
                FROM other_risk_matrix
                WHERE IsActive = 1 AND company_id = $company_id";
        $result = mysqli_query($conn,$sql);
        $risk_matrix_query = '';
        $i = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $row['table_sufix'] = "orm_{$row['table_sufix']}";
            $i++;
            $query_risk_matrix = "SELECT c.*,ccc$i.{$row['table_sufix']}_id, CONCAT(cc$i.name,' ',cc$i.last_name) as 'fullname',cc$i.email,cc$i.name,cc$i.last_name, cc$i.gender, cc$i.phone_number, cc$i.mobile_number, cc$i.address_street, cc$i.address_city, cc$i.address_state, cc$i.address_country, cc$i.date_of_birth, cc$i.occupation, cc$i.source_funds, cc$i.nationality, cc$i.document_type, cc$i.document_number, '1' as 'p_type', ccc$i.actual_status, cc$i.photo
            FROM coincidences c
            LEFT JOIN (SELECT p.*, orm.{$row['table_sufix']}_id
            FROM {$row['table_sufix']} orm
            LEFT JOIN party p ON orm.party_id = p.party_id) ccc$i on c.party_id = ccc$i.{$row['table_sufix']}_id
            LEFT JOIN party cc$i on c.party_id = cc$i.party_id
            WHERE c.risk_matrix_id = {$row['ID']}";
            if ($risk_matrix_query == '') {
                $risk_matrix_query = "$query_risk_matrix";
            } else {
                $risk_matrix_query = "$risk_matrix_query UNION $query_risk_matrix";
            }
        }         


        /****************** COINCIDENCE LIST *************************/
        $sql = "select * FROM (
        $risk_matrix_query
        ) c
        WHERE company_id = $company_id";

        $coincidence_list = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $coincidence_list[] = $row;
        }

        /****************** COINCIDENCE LIST *************************/
        $sql = "SELECT 
            CONCAT(MONTH(created_date), '-', YEAR(created_date)) AS yearmonth,
            COUNT(*) AS count
        FROM coincidences
        WHERE company_id = $company_id
        GROUP BY CONCAT(MONTH(created_date), '-', YEAR(created_date))
        ORDER BY MIN(created_date);";

        //echo $sql;
        $chart_data = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $yearmonth = "'{$row['yearmonth']}'";
            $chart_data[$yearmonth] = $row['count'];
        }

        /********************************************************************************/

        return array(
            "status"=> array(
                "status_label"=>$status_label,
                "status_percent"=>$status_percent,
                "status_value"=>$status_value,
                "status_color"=>$status_color
            ), 
            "sub_status"=> array(
                "sub_status_label"=>$sub_status_label,
                "sub_status_percent"=>$sub_status_percent,
                "sub_status_value"=>$sub_status_value,
                "sub_status_color"=>$sub_status_color
            ),
            "coincidence_list"=>$coincidence_list,
            "channel_list"=>$channel_list,
            "chart_labels"=>array_keys($chart_data),
            "chart_values"=>array_values($chart_data),
        ); 
        
    }    

    function get_coincidences_overview_Company ($conn, $days=30,$company_id) {

        /****************** STATUS CHART *************************/
        $sql = "select status, count(1) as 'count'
        from screening_coincidences 
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = {$company_id}
        group by status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $status_label = array();
        $status_value = array();
        $status_percent = array();
        $status_color = array();
        $status_count = 0;
        $colors_array = array("","primary", "success", "warning");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['status'];
            $status_label[] = $row['status'];
            $status_value[] = $row['count'];
            $status_color[] = $colors_array[$label];
            $status_count = $status_count + $row['count'];
        }    
        

        foreach ($status_value as $value) {
            if ($status_count <> 0) {
                $status_percent[] = round(100 * $value / $status_count);
            } else {
                $status_percent[] = 0;
            }
        }

        /****************** SUB STATUS CHART *************************/
        $sql = "select sub_status, count(1) as 'count'
        from screening_coincidences 
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = {$company_id}
        group by sub_status
        order by count DESC";

        $result = mysqli_query($conn,$sql);
        $sub_status_label = array();
        $sub_status_value = array();
        $sub_status_percent = array();
        $sub_status_color = array();
        $sub_status_count = 0;
        $colors_array = array("warning","danger", "success");

        while ($row = mysqli_fetch_assoc($result)) { 
            $label = $row['sub_status'];
            $sub_status_label[] = $row['sub_status'];
            $sub_status_value[] = $row['count'];
            $sub_status_color[] = $colors_array[$label];
            $sub_status_count = $sub_status_count + $row['count'];
        } 

        foreach ($sub_status_value as $value) {
            if ($status_count <> 0) {
                $sub_status_percent[] = round(100 * $value / $sub_status_count);
            } else {
                $sub_status_percent[] = 0;
            }
        }
        /****************** CHANNEL LIST *************************/
        $sql = "SELECT channel, 
        count(1) as 'generated',
        sum(case when status = 1 then 1 else 0 end) as 'new',
        sum(case when status = 2 then 1 else 0 end) as 'resolved',
        sum(case when status = 3 then 1 else 0 end) as 'pending',
        sum(case when sub_status = 1 and status = 2 then 1 else 0 end) as 'truematch',
        sum(case when sub_status = 2 and status = 2 then 1 else 0 end) as 'falsepositive'
        FROM screening_coincidences
        where created_date >= CURDATE() - INTERVAL {$days} DAY AND company_id = {$company_id}
        group by channel";
        $channel_list = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $channel_list[] = $row;
        }
       

        /****************** COINCIDENCE LIST *************************/
        $sql = "select * FROM screening_coincidences c
        WHERE company_id = {$company_id}";

        $coincidence_list = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $coincidence_list[] = $row;
        }

        /****************** COINCIDENCE LIST *************************/
        $sql = "SELECT 
            CONCAT(MONTH(created_date), '-', YEAR(created_date)) AS yearmonth,
            COUNT(*) AS count
        FROM
            screening_coincidences
        WHERE 
            company_id = {$company_id}
        GROUP BY
            CONCAT(MONTH(created_date), '-', YEAR(created_date))
        ORDER BY
            MIN(created_date);";

        $chart_data = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $yearmonth = "'{$row['yearmonth']}'";
            $chart_data[$yearmonth] = $row['count'];
        }

        /********************************************************************************/

        $sql = "SELECT DATE(date) as date, count(1) as 'count'
                FROM screening_queries
                WHERE company_id = $company_id
                group by DATE(date)";

        $chart_data = array();
        $result = mysqli_query($conn,$sql);
        while ($row = mysqli_fetch_assoc($result)) { 
            $date = "'{$row['date']}'";
            $chart_data[$date] = $row['count'];
        }
        
        /********************************************************************************/

        return array(
            "status"=> array(
                "status_label"=>$status_label,
                "status_percent"=>$status_percent,
                "status_value"=>$status_value,
                "status_color"=>$status_color
            ), 
            "sub_status"=> array(
                "sub_status_label"=>$sub_status_label,
                "sub_status_percent"=>$sub_status_percent,
                "sub_status_value"=>$sub_status_value,
                "sub_status_color"=>$sub_status_color
            ),
            "coincidence_list"=>$coincidence_list,
            "channel_list"=>$channel_list,
            "chart_labels"=>array_keys($chart_data),
            "chart_values"=>array_values($chart_data),
        ); 
        
    }      

    function create_risk_matrix_tables ($conn, $table_sufix) {
        $sql = "CREATE TABLE `orm_$table_sufix` (
            `orm_{$table_sufix}_id` INT NOT NULL AUTO_INCREMENT,
            `party_id` INT DEFAULT NULL,
            PRIMARY KEY (`orm_{$table_sufix}_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);

        // $sql = "CREATE TABLE `orm_{$table_sufix}_document` (
        //     `orm_{$table_sufix}_id` int(11) NOT NULL AUTO_INCREMENT,
        //     `document_type` varchar(255) DEFAULT NULL,
        //     `document_number` varchar(255) DEFAULT NULL,
        //     `country_code` varchar(255) DEFAULT NULL,
        //     `issue_date` date DEFAULT NULL,
        //     `expiry_date` date DEFAULT NULL,
        //     `created_at` datetime DEFAULT NULL,
        //     PRIMARY KEY (`orm_{$table_sufix}_id`)
        //   ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
        //   ";
        // $result = mysqli_query($conn,$sql);
        $sql = "CREATE TABLE `orm_{$table_sufix}_factors` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(45) DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_date` datetime DEFAULT NULL,
            `modified_by` int(11) DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            `type` int(11) DEFAULT NULL COMMENT '1-Clients / 2-Providers / 3-Colaborators ',
            `score` int(11) DEFAULT NULL,
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);        
        $sql = "CREATE TABLE `orm_{$table_sufix}_factors_scores` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `factor_id` int(11) DEFAULT NULL,
            `subfactor_id` int(11) DEFAULT NULL,
            `definition` varchar(250) DEFAULT NULL,
            `kyc_level` varchar(45) DEFAULT NULL,
            `scale` DOUBLE DEFAULT NULL,
            `percentage` DOUBLE DEFAULT NULL,
            `default_value` DOUBLE DEFAULT 0,
            `created_by` int(11) DEFAULT NULL,
            `modified_by` int(11) DEFAULT NULL,
            `created_date` datetime DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            `source_column` varchar(100) DEFAULT NULL,
            `source_table` varchar(100) DEFAULT 'orm_$table_sufix',
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);    
        $sql = "CREATE TABLE `orm_{$table_sufix}_kyc` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `orm_{$table_sufix}_id` varchar(255) NOT NULL,
            `kyc_level` varchar(255) DEFAULT NULL,
            `kyc_type` varchar(255) DEFAULT NULL,
            `created_date` datetime NOT NULL,
            `created_by` int(11) DEFAULT NULL,
            `modified_by` int(11) DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);     
        $sql = "CREATE TABLE `orm_{$table_sufix}_risk_matrix` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `party_id` int(11) NOT NULL,
            `risk_level` varchar(255) DEFAULT NULL,
            `risk_score` int(11) DEFAULT NULL,
            `risk_color` varchar(7) DEFAULT NULL,
            `justification` text DEFAULT NULL,
            `created_date` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `created_by` int(11) DEFAULT NULL,
            `modified_by` int(11) DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            `job_id` INT NULL DEFAULT NULL,
            `chunk` INT NULL DEFAULT NULL,
            `test` TINYINT NULL DEFAULT 0,
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);    
        $sql = "CREATE TABLE `orm_{$table_sufix}_risk_matrix_files` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `orm_risk_score_ID` int(11) DEFAULT NULL,
            `filename` varchar(400) DEFAULT NULL,
            `size` double DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_date` datetime DEFAULT NULL,
            `path` varchar(400) DEFAULT NULL,
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";

        $result = mysqli_query($conn,$sql);    
        $sql = "CREATE TABLE `orm_{$table_sufix}_status` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `party_id` int(11) NOT NULL,
            `status` varchar(255) DEFAULT NULL,
            `created_by` int(11) DEFAULT NULL,
            `created_date` datetime DEFAULT NULL,
            `modified_by` int(11) DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            `justification` text DEFAULT NULL,
            PRIMARY KEY (`ID`)
          ) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
          ";
        $result = mysqli_query($conn,$sql);   
        
        $sql = "CREATE TABLE `orm_{$table_sufix}_fields` (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `column_name` varchar(100) DEFAULT NULL,
            `column_text` varchar(100) DEFAULT NULL,
            `visible` tinyint(4) DEFAULT 0,
            `filter` tinyint(4) DEFAULT 0,
            `class` varchar(100) DEFAULT NULL,
            `filter_type` varchar(45) DEFAULT NULL,
            `filter_multiple` tinyint(4) DEFAULT NULL,
            `filter_values` varchar(45) DEFAULT NULL,
            `data_allow_clear` tinyint(4) DEFAULT NULL,
            `data_hide_search` tinyint(4) DEFAULT NULL,
            `columnDefs` varchar(45) DEFAULT NULL,
            PRIMARY KEY (`ID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;";
        $result = mysqli_query($conn,$sql);        
        
        // $sql = "CREATE TABLE `orm_{$table_sufix}_files` ( 
        //     `ID` int(11) NOT NULL AUTO_INCREMENT, 
        //     `orm_{$table_sufix}_id` int(11) DEFAULT NULL, 
        //     `filename` varchar(300) DEFAULT NULL, 
        //     `size` double DEFAULT NULL, 
        //     `created_by` int(11) DEFAULT NULL, 
        //     `created_date` datetime DEFAULT NULL, 
        //     `path` varchar(500) DEFAULT NULL, 
        //     PRIMARY KEY (`ID`) ) 
        //     ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;";
        // $result = mysqli_query($conn,$sql);
    }

    function delete_risk_matrix_tables ($conn, $table_sufix) {
        $sql = "DROP TABLE `orm_{$table_sufix}`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_status`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_risk_matrix_files`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_risk_matrix`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_kyc`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_factors_scores`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_factors`";
        $result = mysqli_query($conn,$sql);
        $sql = "DROP TABLE `orm_{$table_sufix}_fields`";
        $result = mysqli_query($conn,$sql);        
        // $sql = "DROP TABLE `orm_{$table_sufix}_document`";
        // $result = mysqli_query($conn,$sql);
        // $sql = "DROP TABLE `orm_{$table_sufix}_files`";
        // $result = mysqli_query($conn,$sql);        
    }

    function get_users_no_team ($conn) {
        $sql = "SELECT u.user_id 
        FROM users u 
        LEFT JOIN teams_users tu on u.user_id = tu.user_id
        where tu.team_id is NULL";
        $result = mysqli_query($conn,$sql); 
        $users_no_team = array(); 
        while ($row = mysqli_fetch_assoc($result)) { 
            $users_no_team[] = $row['user_id'];
        }

        return array (
            "users_no_team"=>$users_no_team
        );              
    } 
    
    function get_party_risk_factors ($conn,$party_type,$party_id,$company_id,$table_suffix = '') {

        if ($party_type == 0) {
            $factors = "{$table_suffix}_factors";
            $factors_scores_table = "{$table_suffix}_factors_scores";
        } else {
            $factors = 'factors';
            $factors_scores_table = 'factors_scores';
        }

        // GET Party Query
        $sql = "SELECT query FROM party_view where company_id = $company_id";
        $result = mysqli_query($conn,$sql); 
        while ($row = mysqli_fetch_assoc($result)) {
            $party_query = $row['query'];
        }         

        // GET Factors
        $sql = "SELECT * FROM $factors WHERE type = $party_type";
        $result = mysqli_query($conn,$sql); 
        $factors = array(); 
        $factors_ids = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $factors[] = array(
                "ID"=>$row['ID'],
                "name"=>$row['name'],
                "score"=>$row['score'],
            );
            $factors_ids[] = $row['ID'];
        }

        // GET Factors Scores 
        $factors_subfactors = array();
        $factors_scores = array();

        if (sizeof($factors_ids) > 0) {
            $factors_ids_implode = implode(",",$factors_ids);
            $sql = "SELECT * FROM $factors_scores_table WHERE factor_id IN ($factors_ids_implode)";
            $result = mysqli_query($conn,$sql);      
            while ($row = mysqli_fetch_assoc($result)) {           
                $fid = $row['factor_id'];
                $f_subfactor = $row['subfactor_id'];
                $factors_subfactors[$f_subfactor] = true;
                $factors_scores[$fid][] = $row;
            }
        }
        // GET Subfactors
        $sub_factors = array();
        if (sizeof($factors_subfactors) > 0) {
            $sub_factors_ids_implode = implode(",",array_keys($factors_subfactors));
            $sql = "SELECT * FROM catalogs WHERE ID IN ($sub_factors_ids_implode)";
            $result = mysqli_query($conn,$sql);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $sfid = $row['ID'];
                $sub_factors[$sfid] = $row;
            }
        }

        // GET Subfactors Records
        $sub_factors_records = array();
        if (sizeof($factors_subfactors) > 0) {
            $sub_factors_ids_implode = implode(",",array_keys($factors_subfactors));
            $sql = "SELECT * FROM catalogs_records WHERE catalog_id IN ($sub_factors_ids_implode)";
            $result = mysqli_query($conn,$sql);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $sfid = $row['catalog_id'];
                $sub_factors_records[$sfid][] = $row;
            }  
        }

        // GET Party Data
        if ($party_type <> 0) {
            if ($party_type == 1) {
                $table_name = 'clients';
                $table_id = 'client_id';
            } else if ($party_type == 2) {
                $table_name = 'suppliers';
                $table_id = 'supplier_id';
            } else if ($party_type == 3) {
                $table_name = 'collaborators';
                $table_id = 'collaborator_id';
            }
            $sql = "SELECT * FROM $table_name where `$table_id` = $party_id";
            $result = mysqli_query($conn,$sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $party_info = $row;
            }

        } else {
            // $sql = "SELECT * FROM (SELECT p.*, orm.{$table_suffix}_id
            // FROM $table_suffix orm
            // LEFT JOIN party p ON orm.party_id = p.party_id) cc where `party_id` = $party_id";

            $sql = "SELECT * FROM (SELECT p.*, orm.{$table_suffix}_id
            FROM $table_suffix orm
            LEFT JOIN ($party_query WHERE party.company_id = $company_id) p ON orm.party_id = p.party_id) cc where `party_id` = $party_id";

            $result = mysqli_query($conn,$sql);
            while ($row = mysqli_fetch_assoc($result)) {
                $party_info = $row;
            }        
        }



        // PROCESS DATA
        $factors_result = array();

        //print_r($factors_scores);
        
        foreach ($factors as $f) { // Para todos los factores
            $factor_score = 0;
            $fid = $f['ID'];
            $factor_subfactors = array();
            //print_r($f['score']);
            foreach ($factors_scores[$fid] as $fs) { // Para cada Subfactor
                //print_r($fs['scale']);
                $subfactor_score = 0;
                $source_column = $fs['source_column'];
                $source_table = $fs['source_table'];
                $subfactor_id = $fs['subfactor_id'];
                $found = false;
                foreach($sub_factors_records[$subfactor_id] as $sub_factor_record) {
                    //print_r($sub_factor_record);
                    if ($sub_factor_record['code'] == $party_info[$source_column]) {
                        // Add the value
                        // $subfactor_score = $subfactor_score + $sub_factor_record['risk_score'];
                        // OR
                        // Add the pct 
                        $pct = $sub_factor_record['risk_score']*$fs['scale']/100;
                        $subfactor_score = $subfactor_score + $pct;

                        $found = true;
                    }
                }
                if(!$found) {
                    // Add the value
                    //$subfactor_score = $fs['default_value'];
                    // OR
                    // Add the pct 
                    $pct = $fs['default_value']*$fs['scale']/100;
                    $subfactor_score = $subfactor_score + $pct;                    
                }
                $factor_score = $factor_score + $subfactor_score;
                $total_risk = $total_risk + $subfactor_score;
                if (sizeof($sub_factors) > 0) {
                    $factor_subfactors[] = array(
                        "ID"=>$fs['ID'],
                        "name"=>$sub_factors[$subfactor_id]['name'],
                        "subfactor_score"=>$subfactor_score,
                    );
                }
            }

            $factors_result[] = array(
                "ID"=>$f['ID'],
                "name"=>$f['name'],
                "factor_score"=>$factor_score,
                "factor_subfactors"=>$factor_subfactors,
            );
        }

        // RETURN
        return array (
            "factors"=>$factors_result,
            "factors_scores"=>$factors_scores,
            "sub_factors"=>$sub_factors,
            "sub_factors_records"=>$sub_factors_records,
            "party_info"=>$party_info,
            "factors_result"=>$factors_result
        );
    }

    function addCoincidence($conn,$inputData) {
        
        $object = "coincidences"; // Main Object
        //print_r($inputData);
        if ($inputData['party_id'] == '') {
            $inputData['party_id'] = 0;
        }
        if ($inputData['party_type'] == '') {
            $inputData['party_type'] = 0;
        }            
        //$json_array = json_decode($inputData['selectedjson'],true);
        //$score = $json_array['score'];
        //$source = $json_array['source']; 

            if ($inputData['risk_matrix_id'] == '') {
                $inputData['risk_matrix_id'] = 0;
            }

            $tableName = "coincidences";
            $columns = "ID"; 
            // Filter Type = 1 for Clients
            $filters = array(
                "party_type"=>$inputData['party_type'],
                "party_id"=>$inputData['party_id'],
                "channel"=>$inputData['channel'],
                "risk_matrix_id"=>$inputData['risk_matrix_id'],
                "month"=>"<actualmonth>",

            ); 
            $orderBy = "";
            $rid = selectData($conn,$tableName, $columns, $filters, $orderBy)['data'][0]['ID']; 

            if ($rid == '') {
                $result = insert_row($conn, $object, array(
                    "status"=>1, 
                    "sub_status"=>0, 
                    "assigned_to"=>0, 
                    "assigned_team"=>0, 
                    "party_type"=>$inputData['party_type'],  
                    "party_id"=>$inputData['party_id'],
                    "risk_matrix_id"=>$inputData['risk_matrix_id'],
                    "channel"=>$inputData['channel'],  
                    "month"=>"<actualmonth>",
                    "created_by"=>$inputData['uid'],
                    "created_date"=>"NOW()"         
                ));
                $inputData['rowid'] = $result['lastid']; 
            } else {
                $inputData['rowid'] = $rid;
            }
            
            $umbral = 100;
            $lists = array();
            foreach($inputData['matches'] as $match) {
                $result = insert_row($conn, 'coincidences_hits', array(
                    "coincidence_id"=>$inputData['rowid'], 
                    "channel"=>$inputData['channel'], 
                    "score"=>$match['avg_ratio'], 
                    "list"=>$match['list_code'], 
                    "json"=>json_encode($match), 
                    "created_by"=>$inputData['uid'],
                    "created_date"=>"NOW()"         
                ));
                $source = $match['source'];
                $lists[$source] = true;
                if ($match['avg_ratio'] < $umbral) {
                    $umbral = $match['avg_ratio'];
                }
            }   

            $result = update_data($conn, $object, array(
                "lists"=>implode(",",array_keys($lists)),
                "umbral"=>$umbral,
                "modified_by"=>$inputData['uid'],
                "company_id"=>$inputData['company_id'],
                "modified_date"=>"NOW()"
            ), array(
                "ID = {$inputData['rowid']}",
            )); 
            
            return $result;
    }

    function getScreeningAdminUsers ($conn) {
        $sql = "SELECT * 
        FROM users u
        LEFT JOIN users_companies uc on u.user_id = uc.user_id
        LEFT JOIN companies comp on uc.company_id = comp.ID
        LEFT JOIN companies_products cp on cp.company_id = comp.ID
        LEFT JOIN screening_plan sp on sp.ID = cp.plan
        LEFT JOIN (SELECT company_id, count(1) as `queries_nro`
                    FROM screening_queries 
                    group by company_id) sq on sq.company_id = uc.company_id";

        $result = mysqli_query($conn,$sql);

        $users = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $users[] = $row;
        }  
        return $users;  
    }

    function get_select_distinct_column_from_party_view ($conn,$field,$company_id) {

        $tableName = "party_view";
        $columns = "query"; 
        // Filter Type = 1 for Clients
        $filters = array(
            "company_id"=>$company_id,
        ); 
        $orderBy = "";
        $query_pv = selectData($conn,$tableName, $columns, $filters, $orderBy)['data']; 

        if (sizeof($query_pv) > 0) {
            $sql = "SELECT distinct $field
            FROM 
            ({$query_pv[0]['query']}
            WHERE party.company_id = $company_id) a";
        } else {
            $sql = "SELECT distinct $field
            FROM 
            (Select * from party
            WHERE party.company_id = $company_id) a";
        }

        $result = mysqli_query($conn,$sql);

        $return = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $return[] = $row[$field];
        }  
        return $return;  
    } 
    
    function get_party_view_column ($conn,$company_id) {
        $tableName = "aml.vw_party";
        $columns = "query"; 
        // Filter Type = 1 for Clients
        $filters = array(
            "company_id"=>$company_id,
        ); 
        $orderBy = "";
        $query_pv = selectData($conn,$tableName, $columns, $filters, $orderBy)['data']; 

        if (sizeof($query_pv) > 0) {
            $sql = "{$query_pv[0]['query']}
            WHERE party.company_id = $company_id
            LIMIT 1";
        } else {
            $sql = "SELECT * from party
            WHERE party.company_id = $company_id
            LIMIT 1";            
        }

        $result = mysqli_query($conn,$sql);

        $data = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $data[] = $row;
        }  
        if (sizeof($data) > 0) {
            return array_keys($data[0]);  
        } else {
            return [];
        }
        
    }     

    function getRiskMatrixBatchSummary ($conn, $jobid) {

        $tableName = "risk_matrix_bulk_job";
        $columns = "RiskMatrixID"; 
        $filters = array(
            "ID"=>$jobid,
        ); 
        $orderBy = "";
        $RiskMatrixID = selectData($conn,$tableName, $columns, $filters, $orderBy)['data'][0]['RiskMatrixID'];

        // Get Table
        $tableName = "other_risk_matrix";
        $columns = "table_sufix"; 
        // Filter Type = 1 for Clients
        $filters = array(
            "ID"=>$RiskMatrixID,
        ); 
        $orderBy = "";
        $table_sufix = selectData($conn,$tableName, $columns, $filters, $orderBy)['data'][0]['table_sufix'];        
        $table_name = "orm_{$table_sufix}_risk_matrix";

        $sql = "SELECT risk_level, risk_color, count(1) as `num`
                FROM $table_name
                where job_id = $jobid
                group by risk_level, risk_color";

        $result = mysqli_query($conn,$sql);

        $summary = array();
        while ($row = mysqli_fetch_assoc($result)) { 
            $summary[] = $row;
        }  
        return $summary;  
    }

    function checkQueryValid($conn, $query) {
        $sql = $query;
 
        try {
            if (mysqli_query($conn, $sql)) {
                $r = 1;
            } else {
                $r = 0;
            }
        } catch (Exception $e) {
            $r = 0;
        }
        
        return $r;
    }

    function setNames ($conn) {
        // Build the SQL query
        $sql = "SET NAMES utf8";

        // Execute the query
        $result = mysqli_query($conn, $sql);            
    }    

    function getTransactionsParty($conn,$party_id) {
    
        // Build the SQL query
        $sql = "SELECT t.*, e.created_date
            FROM transactions t
            LEFT JOIN events e on t.event_id = e.event_id
            WHERE (t.debit_party_external_id = {$party_id}) OR (t.credit_party_external_id = {$party_id})";

        // Execute the query

        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }  
    
    function getCoincidencesParty($conn,$party_id) {
    
        // Build the SQL query
        $sql = "SELECT * 
            FROM screening_coincidences sc
            LEFT JOIN (SELECT coincidence_id, count(1)  as 'hits'
            FROM screening_coincidences_hits 
            group by coincidence_id) sch
            on sc.ID = sch.coincidence_id
            WHERE sc.party_id = {$party_id}";

        // Execute the query

        $result = mysqli_query($conn, $sql);
        $data = array();
        if ($result) {
            // Fetch data and do something with it (e.g., display or return)
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $error = '';
            mysqli_free_result($result);
        } else {
            $error = mysqli_error($conn);
        }

        return array(
            "data"=>$data,
            "error"=>$error
        );
    }     


?>