<?php
namespace Models;

use Engine\AbstractModel;

class Member extends AbstractModel
{
    public function getMemberInfo($where, $fields = "*")
    {
        return $this->getBuilder('Member')->columns($fields)
            ->where(K($where), V($where))->getQuery()->getSingleResult();
    }

    public function getMobileLog($data)
    {
        $condition['mobile'] = $data['mobile'];
        $condition['status'] = $data['status'];
        $condition['posttime'] = array('egt', TIMESTAMP - 60 * 30);
        return $this->getBuilder('Models\SmsReport')->columns("count(*) as total,id_code,msg")
            ->where(K($condition), V($condition))->getQuery()->getSingleResult();
    }

    public function sendMobileLog($data)
    {
        $result = $this->getMobileLog($data);
        $res = false;
        if ($result->total == 0) {
            try {
                $transaction = $this->getDI()->getTransactions()->get();
                $this->setTransaction($transaction);
                $keys = array_keys($data);
                $values = array_values($data);

                $status = $this->sqlBuilder('Models\SmsReport')->insert($keys, $values)->getQuery()->execute();
                $condi['status'] = $data['status'];
                $condi['posttime'] = array('elt', TIMESTAMP - 60 * 30);
                $this->sqlBuilder('Models\SmsReport')->where(K($condi), V($condi))->delete()->getQuery()->execute();
                $transaction->commit();
                if ($res = $status->success()) {
                    if (isset($status->getModel()->id))
                        return $status->getModel()->id;
                }
            } catch (TxFailed $e) {
                echo "Failed, reason: ", $e->getMessage();
            }
        }
        return $res;
    }

    public function delMobileLog($data)
    {
        return $this->sqlBuilder('Models\SmsReport')->where(K($data), V($data))->delete()->getQuery()->execute();
    }

    public function getMobileReport($data)
    {
        $condition['mobile'] = $data['mobile'];
        $condition['status'] = $data['status'];
        $condition['posttime'] = array('egt', TIMESTAMP - 60 * 30);
        $result = $this->getBuilder('Models\SmsReport')->columns("id_code")
            ->where(K($condition), V($condition))->getQuery()->getSingleResult();
        return !empty($result) ? $result : false;
    }

    public function saveMemberInfo($data, $mode = 'INSERT', $where = '')
    {
        $res = 0;
        try {
            $transaction = $this->getDI()->getTransactions()->get();

            $this->setTransaction($transaction);

            $keys = array_keys($data);
            $values = array_values($data);
            if ($mode == 'INSERT') {
                $status = $this->sqlBuilder('Member')->insert($keys, $values)->getQuery()->execute();
            } else {
                $status = $this->sqlBuilder('Member')->update($keys, $values)->where(K($where), V($where))->getQuery()->execute();
            }
            $transaction->commit();

            if ($res = $status->success()) {
                if (isset($status->getModel()->userid))
                    return $status->getModel()->userid;
            }

        } catch (TxFailed $e) {
            $transaction->rollback("Cannot save robot part");
            echo "Failed, reason: ", $e->getMessage();

        }
        return $res;
    }
}
