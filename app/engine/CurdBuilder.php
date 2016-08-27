<?php
namespace Engine;

use Phalcon\DI;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Mvc\Model\Query\Builder as QueryBuilder;
use Phalcon\Mvc\Model\Query as ModelQuery;


class CurdBuilder extends QueryBuilder
{
    public $_Phql = NULL;
    public $_type = NULL;

    # INSERT INTO Cars (price, type) VALUES (:price:, :type:)
    /*$result = $this->modelsManager->createBuilder()
    ->insert('Cars', array('price' => 15000.00, 'type' => 'Sedan'))
    ->getQuery()
    ->execute();

    # UDPATE Cars SET price = :price, type = :type: WHERE id = :id:
    $result = $this->modelsManager->createBuilder()
    ->update('Cars', array('price' => 15000.00, 'type' => 'Sedan'))
    ->where('id = :id:', array('id' => 100))
    ->getQuery()
    ->execute();

    # DELETE FROM Cars WHERE id = :id:
    $result = $this->modelsManager->createBuilder()
    ->delete('Cars')
    ->where('id = :id:', array('id' => 100))
    ->getQuery()
    ->execute();

    ## Or alternate concept

    # INSERT INTO Cars (price, type) VALUES (:price:, :type:)
    $result = $this->modelsManager->createBuilder()
    ->insert('Cars', array('price', 'type'), array(15000.00, 'Sedan'))
    ->getQuery()
    ->execute();

    # UDPATE Cars SET price = :price, type = :type: WHERE id = :id:
    $result = $this->modelsManager->createBuilder()
    ->update(array('price', 'type'), array(15000.00, 'Sedan'))
    ->where('id = :id:', array('id' => 100))
    ->getQuery()
    ->execute();*/

    ## Or alternate concept

    # $builder = new \Engine\CurdBuilder();
    # $builder->from('member')->insert(['user_id' => 1, 'username' => 'shies'], false)->execute();
    # $builder->from('member')->insertAll(
    # [
    #   ['user_id' => 1, 'username' => 'shies'], 
    #   ['user_id' => 2, 'username' => 'danko']
    # ], false)->execute();

    # $builder->from('member')->update(['user_id' => 1, 'username' => 'shies'], 'user_id = 1')->execute();
    # $builder->from('member')->delete('user_id = 1')->execute();

    public function insert(array $keys = array(), array $values = array())
    {
        if (empty($keys) || empty($values)
        ) {
            throw new \Exception('The frist params or second params must is array');
        }

        if (count($keys) !== count($values)) {
            throw new \Exception('The keys neq values params number');
        }

        $args = func_get_args();
        if (func_num_args() < 2) {
            throw new \Exception('Sorry, please input your params');
        }

        $ignore = false;
        $fields = array_shift($args);
        $values = array_slice($args, 0);

        $format_keys = NULL;
        foreach ($fields AS $val)
            $format_keys .= '' . $val . ',';

        $valueList = [];
        foreach ($values AS $val) {
            if (empty($val) ||
                !is_array($val) ||
                count($val) !== count($keys)
            ) {
                continue;
            }
            $str = '(\'' . implode('\',\'', array_values($val)) . '\')';
            $valueList[] = $str;
            unset($str);
        }

        if ($ignore) {
            $sql = "INSERT IGNORE INTO [%s] (%s) VALUES " . join(',', $valueList);
        } else {
            $sql = "INSERT INTO [%s] (%s) VALUES " . join(',', $valueList);
        }

        $this->_Phql = sprintf($sql, $this->_models, rtrim($format_keys, ','));
        $this->_type = 306;
        unset($ignore, $fields, $values, $format_keys, $valueList);

        return $this;
    }

    public function update(array $keys = array(), array $values = array())
    {
        if (empty($keys) || empty($values)) {
            throw new \Exception('The frist params or second params must is array');
        }

        if (count($keys) !== count($values)) {
            throw new \Exception('The keys neq values params number');
        }

        $setVal = NULL;
        foreach ($keys AS $key => $val) {
            if (preg_match('/^('.$val.')/', $values[$key])) {// 递加
                $setVal .= "$val =  $values[$key],";
            } else
                $setVal .= "$val =  '$values[$key]',";

        }

        $sql = "UPDATE [%s] SET %s ";
        $this->_Phql = sprintf($sql, $this->_models, rtrim($setVal, ','));
        $this->_type = 300;

        return $this;
    }


    public function delete()
    {
        // set query type method for executeDelete
        $this->_type = 303;
        $this->_Phql = "DELETE FROM [%s] ";
        $this->_Phql = sprintf($this->_Phql, $this->_models);

        return $this;
    }


    public function getQuery()
    {
        if (!$this->_Phql) {
            throw new \Exception('please format sql after call this method');
        }

        $where = $this->getWhere();
        if (!empty($where)) {
            $this->_Phql .= (' WHERE ' . $where);
        }

        $limit = $this->getLimit();
        if (!empty($limit)) {
            $this->_Phql .= (' LIMIT ' . $limit);
        }

        // or new ModelQuery($this->_Phql) call all
        $query = new \Phalcon\Mvc\Model\Query($this->_Phql, \Phalcon\Di::getDefault());
        if (!empty($this->_bindParams)) {
            $query->setBindParams($this->_bindParams);
        }
        if (!empty($this->_bindTypes)) {
            $query->setBindTypes($this->_bindTypes);
        }
        $query->setType($this->_type);
        $query->setDI(\Phalcon\Di::getDefault());

        return $query;
    }


    /*
    public function insert($arr, $ignore = false)
    {
        $field = $value = NULL;

        $keys = array_keys($arr);
        $values = array_values($arr);

        foreach ($keys AS $key)
            $field .= '' . $key . ',';

        foreach ($values AS $val)
            $value .= '\'' . $val . '\',';

        if ($ignore)
        {
            $sql = "INSERT IGNORE INTO [%s] (%s) VALUES (%s)";
        }
        else
        {
            $sql = "INSERT INTO [%s] (%s) VALUES (%s)";
        }

        $this->_Phql = sprintf($sql, $this->_models, rtrim($field, ','), rtrim($value, ','));

        return $this;
    }


    public function insertAll($arr, $ignore = false)
    {
        if (!is_array($arr[0]))
        {
            throw new \Exception('The frist params must is array');
        }

        $field = NULL;
        $keys = array_keys($arr[0]);
        foreach ($keys AS $key)
        {
            $field .= '`' . $key . '`,';
        }

        $valueList = [];
        foreach ($arr AS $val)
        {
            if ($val)
            {
                $str = '(\'' . implode('\',\'', array_values($val)) . '\')';
                $valueList[] = $str;
                unset($str);
            }
        }

        if ($ignore)
        {
            $sql = "INSERT IGNORE INTO [%s] (%s) VALUES " . join(',', $valueList);
        }
        else
        {
            $sql = "INSERT INTO [%s] (%s) VALUES " . join(',', $valueList);
        }

        $this->_Phql = sprintf($sql, $this->_models, rtrim($field, ','));
        
        return $this;
    }


    public function execute()
    {
        if (empty($this->_Phql))
        {
            throw new \Exception('please format sql after call this method');
        }

        $_PM = _PhalconModel::getInstance();
        $result = $_PM->getModelsManager()->executeQuery($this->_Phql);
        if ($result->success() == false)
        {
            foreach ($result->getMessages() as $message)
                echo $message->getMessage();
        }
        return true;
    }


    public function update($arr, $where = '')
    {
        if (!is_array($arr))
        {
            throw new \Exception('The frist params must is array');
        }

        $keys = array_keys($arr);
        $values = array_values($arr);

        $setVal = NULL;
        foreach ($keys AS $key => $val)
            $setVal = "`$val` =  '$values[$key]',";

        $sql = "UPDATE `%s` SET %s ";
        if ($where)
        {
            $sql .= "WHERE %s";
        }

        $this->_Phql = sprintf($sql, $this->_models, rtrim($setVal, ','), $where);

        return $this;
    }


    public function delete($where = '')
    {
        $sql = "DELETE FROM `%s` ";
        if ($where)
        {
            $sql .= "WHERE %s";
        }

        $this->_Phql = sprintf($sql, $this->_models, $where);

        return $this;
    }
    */

}

/*
class _PhalconModel extends PhalconModel
{
    private static $_instance;

    public static function getInstance()
    {
        if (empty(self::$_instance))
        {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    private function __clone() {}
}
*/