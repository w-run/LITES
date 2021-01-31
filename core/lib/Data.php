<?php

namespace core\lib;

class Data
{

    public $table = "";


    public function __construct($table)
    {
        $this->table = $table;
    }


    public function get($where = null, $rows = null, $limit = null, $order = null, $group = null)
    {
        $sql = new MySQL();
        $table = $this->table;
        $res = array();
        $row = $this->make_row($rows);
        $where = $this->make_where($where);
        $limit = $this->make_limit($limit);
        $order = $this->make_order($order);
        $group = $this->make_group($group);
        $str = "SELECT $row FROM $table$where$order$limit$group";
        $queryRes = $sql->query($str);
        do {
            $tmp = $this->toArray($queryRes);
            if ($tmp != null)
                array_push($res, $tmp);
        } while ($tmp != null);
        $sql->close();
        return $res;
    }


    public function get_union($union_table, $union_where, $where = null, $rows = null, $limit = null, $order = null)
    {
        $sql = new MySQL();
        $table = $this->table;
        $res = array();
        $row = $this->make_row($rows);
        $where = $this->make_where($where);
        $limit = $this->make_limit($limit);
        $order = $this->make_order($order);
        if ($where != null)
            $union_where = " AND " . $union_where;
        $str = "SELECT $row FROM $table,$union_table $where$union_where$order$limit";
        $queryRes = $sql->query($str);
        do {
            $tmp = $this->toArray($queryRes);
            if ($tmp != null)
                array_push($res, $tmp);
        } while ($tmp != null);
        $sql->close();
        return $res;
    }


    public function add($rows, $values)
    {
        $sql = new MySQL();
        $table = $this->table;
        $value = "";
        $row = "(";
        for ($i = 0; $i < count($rows); $i++) {
            $row .= $rows[$i] . ((($i + 1) < count($rows)) ? ", " : ")");
        }
        for ($i = 0; $i < count($values); $i++) {
            $value .= ((gettype($values[$i]) == 'integer') ? $values[$i] : "'" . $values[$i] . "'") . ((($i + 1) < count($values)) ? ", " : "");
        }
        $str = "INSERT INTO $table $row VALUES ($value)";
        $sql->query($str);
        $res = $sql->query_res();
        $sql->close();
        return $res;
    }


    public function edit($where, $values)
    {
        $sql = new MySQL();
        $table = $this->table;
        $value = "";
        $values_key = array_keys($values);
        for ($i = 0; $i < count($values_key); $i++) {
            $key = $values_key[$i];
            $val = ((gettype($values[$values_key[$i]]) == 'integer') ? $values[$values_key[$i]] : "'" . $values[$values_key[$i]] . "'");
            $value .= ($key . "=" . $val) . ((($i + 1) < count($values_key)) ? ", " : "");
        }
        $str = "UPDATE $table SET $value WHERE $where";
        $sql->query($str);
        $res = $sql->query_res();
        $sql->close();
        return $res;
    }


    public function edit_calc($where, $row, $calcStr = "+1", $update = false)
    {
        $sql = new MySQL();
        $table = $this->table;
        $datetime = date("Y-m-d H:i:s");
        $upStr = "";
        if ($update)
            $upStr = ", update_time = '$datetime'";
        $str = "UPDATE $table SET $row = $row $calcStr $upStr WHERE $where";
        $sql->query($str);
        $res = $sql->query_res();
        $sql->close();
        return $res;
    }


    public function del($where)
    {
        $sql = new MySQL();
        $table = $this->table;
        $str = "DELETE FROM $table WHERE $where";
        $sql->query($str);
        $res = $sql->query_res();
        $sql->close();
        return $res;
    }


    public function count($where = null, $rows = null)
    {
        $sql = new MySQL();
        $table = $this->table;
        $row = $this->make_row($rows);
        $where = $this->make_where($where);
        $str = "SELECT count($row) FROM $table$where";
        $res = $sql->query($str);
        $count = $this->toArray($res, 1);
        $sql->close();
        return $count[0];
    }

    public function getByTime($field = 'time', $days = 1, $where = null, $start = 'NOW()')
    {
        if ($where == null)
            $where = "";
        else
            $where .= " AND ";
        $res = $this->get($where . "TO_DAYS($start)-TO_DAYS($field)<=$days", ["count(1) as num"])[0]['num'];
        return intval($res);
    }


    public function toArray($result, $type = 0)
    {
        if (!$result)
            return null;
        if ($result->num_rows < 1)
            return null;
        return ($type == 2) ? $result->fetch_array() : (($type == 0) ? $result->fetch_assoc() : $result->fetch_row());
    }


    private function make_row($rows)
    {
        $row = "*";
        if ($rows != null) {
            $row = "";
            for ($i = 0; $i < count($rows); $i++) {
                $row .= $rows[$i] . ((($i + 1) < count($rows)) ? "," : "");
            }
        }
        return $row;
    }


    private function make_where($where)
    {
        if ($where != null) {
            $where = " WHERE " . $where;
        }
        return $where;
    }


    private function make_limit($limit)
    {
        if ($limit != null) {
            $limit = " LIMIT " . $limit;
        }
        return $limit;
    }


    private function make_order($order)
    {
        if ($order != null) {
            $order = " ORDER BY " . $order;
        }
        return $order;
    }


    private function make_group($group)
    {
        if ($group != null) {
            $group = " GROUP BY " . $group;
        }
        return $group;
    }


}