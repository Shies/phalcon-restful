<?php
namespace Models;

use Engine\AbstractModel;

class Hits extends AbstractModel
{

    public function getHitsInfo(array $data, $fields = '*')
    {
        return $this->getBuilder('Hits')->columns($fields)
            ->where(K($data), V($data))->getQuery()->getSingleResult();
    }

    public function saveHitsInfo(array $where, $cid)
    {
        if (empty($where)) {
            return false;
        }
        $result = $this->getHitsInfo($where);

        $views = !empty($result) ? $result->views + 1 : 1;

        $yesterdayviews = (date('Ymd', $result->updatetime) == date('Ymd', strtotime('-1 day'))) ? $result->dayviews : $result->yesterdayviews;
        $dayviews = (date('Ymd', $result->updatetime) == date('Ymd', TIMESTAMP)) ? ($result->dayviews + 1) : 1;
        $weekviews = (date('YW', $result->updatetime) == date('YW', TIMESTAMP)) ? ($result->weekviews + 1) : 1;
        $monthviews = (date('Ym', $result->updatetime) == date('Ym', TIMESTAMP)) ? ($result->monthviews + 1) : 1;
        $data = array(
            'views' => $views, 'yesterdayviews' => $yesterdayviews,
            'dayviews' => $dayviews, 'weekviews' => $weekviews,
            'monthviews' => $monthviews, 'updatetime' => TIMESTAMP
        );

        $transaction = $this->getDI()->getTransactions()->get();
        $this->setTransaction($transaction);
        if (empty($result)) {
            $data['hitsid'] = $where['hitsid'];
            $data['catid'] = $cid;
            $status = $this->sqlBuilder('Hits')->insert(array_keys($data), array_values($data))->getQuery()->execute();
        } else {
            $status = $this->sqlBuilder('Hits')->update(array_keys($data), array_values($data))->where(K($where), V($where))->getQuery()->execute();
        }
        $transaction->commit();
        return $status->success() ? $data : 0;
    }

}
