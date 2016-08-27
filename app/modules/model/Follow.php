<?php
namespace Models;

use Engine\AbstractModel;

class Follow extends AbstractModel
{
    public function getFollowList($date, array $param)
    {
        $result = $this->getBuilder('Follow')->columns('COUNT(*) AS total')
            ->where(K($date), V($date))
            ->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;
        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 10
        ]);

        $builder = $this->getBuilder('Follow', 'f')->columns('f.id,f.catid as cid,f.userid,f.adddate,c.catname as name,c.image')
            ->join('Models\Category', 'f.catid = c.catid', 'c')
            ->where(K($date), V($date))->orderBy('adddate DESC')->limit($where['limit'])->offset($where['offset']);

        $favorite = $builder->getQuery()->execute()->toArray();
        $favorites=array();
        foreach ($favorite as $key => $val) {
            $audios = $this->getBuilder('Models\Audio', "n")->columns('n.id,n.title')
                ->where("n.status = 99")
                ->andWhere('n.catid = :catid:', array('catid' => $val['cid']))->orderBy('n.inputtime DESC')
                ->limit(1)->getQuery()->getSingleResult();
            $favorites[$key]=$val;
            $favorites[$key]['title']=$audios->title;

        }
        return array($where['pagecount'], $favorites);
    }
    public function countFollowList($date)
    {
        $result = $this->getBuilder('Follow')->columns('COUNT(*) AS total')
            ->where(K($date), V($date))
            ->getQuery()->getSingleResult();

        return isset($result->total) ? intval($result->total) : 0;
    }
    public function addFollowInfo(array $data)
    {
        $condition['userid'] = $data['userid'];
        $condition['catid'] = $data['catid'];
        $result = $this->getBuilder('Follow')->columns("count(*) as total")
            ->where(K($condition), V($condition))->getQuery()->getSingleResult();
        $res = 0;
        if ($result->total == 0) {
            try {
                $transaction = $this->getDI()->getTransactions()->get();
                $this->setTransaction($transaction);
                $keys = array_keys($data);
                $values = array_values($data);
                $status = $this->sqlBuilder('Follow')->insert($keys, $values)->getQuery()->execute();

                $transaction->commit();
                if ($res = $status->success()) {
                    if (isset($status->getModel()->id))
                        return $status->getModel()->id;
                }
            } catch (TxFailed $e) {
                echo "Failed, reason: ", $e->getMessage();
            }
        } else $res = 1;
        return $res;
    }

    public function delFollowInfo(array $date)
    {
        return $this->sqlBuilder('Follow')->delete()->where(K($date), V($date))->getQuery()->execute();
    }
}
