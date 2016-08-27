<?php
namespace Models;

use Engine\AbstractModel;
use Phalcon\DI;
use Phalcon\Mvc\Model AS PhalconModel;
use Phalcon\Mvc\Model\Query\Builder AS QueryBuilder;
use Phalcon\Mvc\Model\Query AS ModelQuery;

class Times extends AbstractModel
{
    public function getTimesInfo($where, $fields = "*")
    {
        $where['logintime'] = array('egt', TIMESTAMP - 60 * 15);
        return $this->getBuilder('Times')->columns($fields)
            ->where(K($where), V($where))->getQuery()->getSingleResult();
    }

    public function saveTimesInfo(array $comment, $mode = 'INSERT', $where = '')
    {
        if (empty($comment)) {
            return false;
        }
        $keys = array_keys($comment);
        $values = array_values($comment);
        $transaction = $this->getDI()->getTransactions()->get();
        $this->setTransaction($transaction);
        if ($mode == 'INSERT') {
            $condi['username'] = $comment['username'];
            $condi['logintime'] = array('elt', TIMESTAMP - 60 * 15);
            $this->sqlBuilder('Times')->where(K($condi), V($condi))->delete()->getQuery()->execute();
            $status = $this->sqlBuilder('Times')->insert($keys, $values)->getQuery()->execute();

        } else {
            $status = $this->sqlBuilder('Times')->update($keys, $values)->where(K($where), V($where))->getQuery()->execute();
        }
        $transaction->commit();
        return $status->success();
    }

    public function delTimesInfo($where)
    {
        return $this->sqlBuilder('Times')->where(K($where), V($where))->delete()->getQuery()->execute();
    }
}
