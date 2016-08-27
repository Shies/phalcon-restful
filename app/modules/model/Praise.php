<?php
namespace Models;

use Engine\AbstractModel;

class Praise extends AbstractModel
{

    public function getPraiseInfo(array $data, $fields = '*')
    {
        return $this->getBuilder('Praise')->columns($fields)
            ->where(K($data), V($data))->getQuery()->getSingleResult();
    }


    public function savePraiseInfo(array $where, $cid)
    {
        if (empty($where)) {
            return false;
        }
        $result = $this->getPraiseInfo($where);

        $praises = !empty($result) ? $result->praises + 1 : 1;

        $yesterdaypraises = (date('Ymd', $result->updatetime) == date('Ymd', strtotime('-1 day'))) ? $result->daypraises : $result->yesterdaypraises;
        $daypraises = (date('Ymd', $result->updatetime) == date('Ymd', TIMESTAMP)) ? ($result->daypraises + 1) : 1;
        $weekpraises = (date('YW', $result->updatetime) == date('YW', TIMESTAMP)) ? ($result->weekpraises + 1) : 1;
        $monthpraises = (date('Ym', $result->updatetime) == date('Ym', TIMESTAMP)) ? ($result->monthpraises + 1) : 1;
        $data = array(
            'praises' => $praises, 'yesterdaypraises' => $yesterdaypraises,
            'daypraises' => $daypraises, 'weekpraises' => $weekpraises,
            'monthpraises' => $monthpraises, 'updatetime' => TIMESTAMP
        );

        $transaction = $this->getDI()->getTransactions()->get();
        $this->setTransaction($transaction);
        if (empty($result)) {
            $data['praiseid'] = $where['praiseid'];
            $data['catid'] = $cid;
            $status = $this->sqlBuilder('Praise')->insert(array_keys($data), array_values($data))->getQuery()->execute();
        } else {
            $status = $this->sqlBuilder('Praise')->update(array_keys($data), array_values($data))->where(K($where), V($where))->getQuery()->execute();
        }
        $transaction->commit();
        return $status->success() ? $data : 0;
    }


    public function delPraiseInfo(array $where)
    {
        if (empty($where)) {
            return false;
        }
        $result = $this->getPraiseInfo($where);

        $data = array(
            'praises' => 'praises-1', 'yesterdaypraises' => 'yesterdaypraises-1',
            'daypraises' => 'daypraises-1', 'weekpraises' => 'weekpraises-1',
            'monthpraises' => 'monthpraises-1', 'updatetime' => TIMESTAMP
        );

        $transaction = $this->getDI()->getTransactions()->get();
        $this->setTransaction($transaction);

         $status = $this->sqlBuilder('Praise')->update(array_keys($data), array_values($data))->where(K($where), V($where))->getQuery()->execute();

        $transaction->commit();
        return $status->success();
    }
}
