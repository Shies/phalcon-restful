<?php
namespace Models;
use Engine\AbstractModel;
class Audio extends AbstractModel
{

    public function getAudio($id, $param)
    {
        $cate = $this->getBuilder('Models\Category')
            ->columns('catid as cid ,siteid as sid,catname as name,image,description as [desc]')
            ->where('catid = :catid:', array('catid' => $id))->getQuery()->getSingleResult()->toArray();
        if (empty($cate)) return array(0, []);
        $result = $this->getBuilder("Models\Audio")->columns("COUNT(*) AS total")
            ->where('status = 99 and catid = :catid:', array('catid' => $id))
            ->getQuery()->getSingleResult();
        $result->total = isset($result->total) ? $result->total : 0;

        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 10
        ]);
        if ($result->total == 0 || $where['pagecount'] < $param['page']) return array(0, []);

        $builder = $this->getBuilder('Models\Audio', "n")->columns('n.id,n.catid as cid ,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type,c.modelid,nd.audio')
            ->join('Models\Category', 'n.catid = c.catid', 'c')
            ->join('Models\AudioData', 'n.id = nd.id', 'nd')
            ->where("n.status = 99 and c.ismenu = 1")
            ->andWhere('n.catid = :catid:', array('catid' => $id))->orderBy('n.updatetime DESC')
            ->limit($where['limit'], $where['offset']);
        $result = $builder->getQuery()->execute();
        $date = array();
        if (!empty($result)) {
            foreach ($result as $key => $v) {
                $audio = string2array($v['audio']);
                $date[$key]["id"] = $v['id'];
                $date[$key]["cid"] = $v['cid'];
                $date[$key]["mid"] = $v['modelid'];
                $date[$key]["title"] = strip_tags($v['title']);
                $date[$key]["desc"] = strip_tags($v['desc']);
                $date[$key]["author"] = strip_tags($v['author']);
                $date[$key]["favorite"] = 0;
                $date[$key]["time"] = $v['time'];
                $date[$key]["type"] = $this->isType($v["type"]);
                $date[$key]["list"] = !empty($audio) ? $audio : [['src' => '', 'content' => '']];
            }
        }
        return array($where['pagecount'], $cate, $date);
    }
}
