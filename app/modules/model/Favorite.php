<?php
namespace Models;

use Engine\AbstractModel;

class Favorite extends AbstractModel
{
    public function getFavoriteList($date, array $param)
    {
        $result = $this->getBuilder('Favorite')->columns('COUNT(*) AS total')
            ->where(K($date), V($date))
            ->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;
        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 10
        ]);

        $builder = $this->getBuilder('Favorite')->columns('id as fid ,title,catid as cid,userid,typeid as type,aid as id ,adddate')
            ->where(K($date), V($date))->orderBy('adddate DESC')->limit($where['limit'])->offset($where['offset']);

        $favorite = $builder->getQuery()->execute()->toArray();
        $favorites = array();
        foreach ($favorite as $key => $var) {
            $favorites[$key] = $var;
            $favorites[$key]['type'] = $this->isType($var["type"]);
        }
        return array($where['pagecount'], $favorites);
    }
    public function countFavoriteList($date)
    {
        $result = $this->getBuilder('Favorite')->columns('COUNT(*) AS total')
            ->where(K($date), V($date))
            ->getQuery()->getSingleResult();
        return isset($result->total) ? $result->total : 0;
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

    public function getType($type)
    {
        if ($type == "normal") return 0;
        elseif ($type == "pic") return 2;
        elseif ($type == "video") return 3;
        elseif ($type == "audio") return 4;
    }

    public function addFavoriteInfo(array $data)
    {
        $condition['userid'] = $data['userid'];
        $condition['catid'] = $data['catid'];
        $condition['aid'] = $data['aid'];
        $data['typeid'] = $this->getType($data['type']);
        $data['adddate'] = TIMESTAMP;
        unset($data['type']);

        $result = $this->getBuilder('Favorite')->columns("count(*) as total")
            ->where(K($condition), V($condition))->getQuery()->getSingleResult();
        $res = 0;
        if ($result->total == 0) {
            try {
                $transaction = $this->getDI()->getTransactions()->get();
                $this->setTransaction($transaction);
                $keys = array_keys($data);
                $values = array_values($data);
                $status = $this->sqlBuilder('Favorite')->insert($keys, $values)->getQuery()->execute();
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

    public function delFavoriteInfo(array $date)
    {
        return $this->sqlBuilder('Favorite')->where(K($date), V($date))->delete()->getQuery()->execute();
    }
}
