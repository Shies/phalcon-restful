<?php
namespace Models;

use Engine\AbstractModel;
use Phalcon\DI;
use Phalcon\Mvc\Model AS PhalconModel;
use Phalcon\Mvc\Model\Query AS ModelQuery;

class News extends AbstractModel
{

    public $client_id;

    public $private_key;

    public $status;


    public function getArticleInfo($id)
    {
        $article_id = intval($id);

        $builder = $this->getBuilder('News')->columns('id, catid, typeid, title, style, thumb, keywords')->where('id=' . $article_id)->limit(1);
        $result = (Object) $builder->getQuery()->getSingleResult();
		
        if (empty($result)) {
            return false;
        }
		
        $result->title = stripslashes($result->title);
        $result->thumb = is_file($result->thumb) ? $result->thumb : '';

		
        return $result;
    }


    public function getContent($id)
    {
        $date = rcache($id, "content");
        if (empty($date)) {
            $builder = $this->getBuilder('News', "n")->columns('n.id,n.catid as cid,n.title,n.catid,n.description,n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type,n.keywords,c.modelid,nd.content,nd.relation')
                ->join('Models\Category', 'n.catid = c.catid', 'c')
                ->join('Models\NewsData', 'n.id = nd.id', 'nd')
                ->where("n.status = 99 AND n.id=$id");
            $result = $builder->getQuery()->getSingleResult();
            if (!empty($result)) {
                $sql = "";
                if ($result['relation']) {
                    $relations = explode('|', trim($result['relation'], '|'));
                    $relations = array_diff($relations, array(null));
                    $relations = implode(',', $relations);
                    $sql = " AND id IN ($relations)";
                } elseif ($result['keywords']) {
                    $keywords = str_replace(array('%', "'"), '', $result['keywords']);
                    $keywords_arr = explode(' ', $keywords);
                    $key_array = array();
                    $number = 0;
                    $i = 1;
                    $sql .= " AND catid='$result[catid]'";
                    foreach ($keywords_arr as $_k) {
                        $sql2 = $sql . " AND keywords LIKE '%$_k%' AND id != " . abs(intval($result['id']));
                    }
                }
                $relation = $this->getBuilder('News', "n")->columns('n.id,n.title,n.username as author,n.inputtime as time,n.thumb as pic')
                    ->where("n.status = 99 $sql $sql2")->limit(4)
                    ->getQuery()->execute()->toArray();
                list($ishot, $hits) = $this->getHits($result['modelid'], $result['id']);
                $date = array(
                    "id" => $result['id'],
                    "cid" => $result['cid'],
                    "mid" => $result['modelid'],
                    "title" => $result['title'],
                    "desc" => $result['description'],
                    "author" => $result['author'],
                    "ishot" => $ishot,
                    "view" => $hits,
                    "favorite" => $this->getFavorite($result['cid'], $result['id']),
                    "comment" => $this->getComment($result['cid'], $result['modelid'], $result['id']),
                    "time" => $result['time'],
                    "pic" => $result['pic'],
                    "type" => $this->isType($result["type"]),
                    "content" => strip_only_tags($result['content'], '<img>,<strong>'),
                    "relation" => json_encode($relation, JSON_UNESCAPED_UNICODE)
                );
            }
            wcache($id, $date, "content");
        }
        $date["relation"] = json_decode($date["relation"]);
        return $date;
    }


    public function getIndex($limit = 10)
    {
        $builder = $this->getBuilder('News', "n")->columns('n.id,n.catid as cid,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type,c.modelid')
            ->leftJoin('Models\Category', 'n.catid = c.catid', 'c')
            ->leftJoin("Models\Praise", 'n.id= substring_index(h.praiseid,"-",-1)', 'h')
            ->where("n.status = 99 AND n.posids = 1")->orderBy('h.praises DESC')->limit($limit);
        $hot = $builder->getQuery()->execute()->toArray();

        $hots = array();
        foreach ($hot as $k => $v) {
            list($ishot, $hits) = $this->getHits($v['modelid'], $v['id']);
            $hots[$k] = $v;
            $hots[$k]["ishot"] = $ishot;
            $hots[$k]["view"] = $hits;
            $hots[$k]["favorite"] =$this->getFavorite($v['cid'], $v['id']);
            $hots[$k]["comment"] = $this->getComment($v['cid'], $v['modelid'], $v['id']);
            $hots[$k]["type"] = $this->isType($v["type"]);
        }

        $builder = $this->getBuilder('Models\Category', 'c')->columns('c.catid as cid,c.siteid as sid,c.catname as name,c.modelid,c.arrchildid,m.tablename')
            ->join('Models\Model', 'c.modelid = m.modelid', 'm')
            ->where("c.parentid = 0 and c.modelid>0 ")->orderBy('catid');
        $category = $builder->getQuery()->execute()->toArray();
        $arr = array();
        foreach ($category as $key => $cate) {
            $arr[$key] = $cate;

            if (!empty($cate['tablename'])) $tablename = ucfirst($cate['tablename']);

            $robots = array();
            if ($tablename != "Audio") {

                $builder = $this->getBuilder('Models\\' . $tablename)->columns('id,title,description as [desc],username as author,inputtime as time,thumb as pic,typeid as type')
                    ->where('status = 99 and catid in (' . $cate["arrchildid"] . ')')->orderBy('inputtime DESC')->limit($limit);
                $robot = $builder->getQuery()->execute();

                foreach ($robot as $k => $v) {
                    list($ishot, $hits) = $this->getHits($cate['modelid'], $v['id']);
                    $robots[$k]["id"] = $v['id'];
                    $robots[$k]["title"] = strip_tags($v['title']);
                    $robots[$k]["desc"] = strip_tags($v['desc']);
                    $robots[$k]["author"] = strip_tags($v['author']);
                    $robots[$k]["ishot"] = $ishot;
                    $robots[$k]["view"] = $hits;
                    $robots[$k]["favorite"] = $this->getFavorite($cate['cid'], $v['id']);
                    $robots[$k]["time"] = $v['time'];
                    $robots[$k]["pic"] = $v['pic'];
                    $robots[$k]["comment"] = $this->getComment($cate['cid'], $cate['modelid'], $v['id']);
                    $robots[$k]["type"] = $this->isType($v["type"]);
                }
            } else {

                $category = $this->getBuilder('Models\Category')->columns('catid as cid,siteid as sid,catname as name,image')
                    ->where('parentid = :parentid:', array('parentid' => $cate['cid']))->orderBy('listorder DESC')->limit($limit)
                    ->getQuery()->execute()->toArray();
                $i=0;
                foreach ($category as $k => $v) {
                    $audio = $this->getBuilder('Models\Audio', 'n')->columns('n.id,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.typeid as type')
                        ->where('status = 99 and catid = :catid:', array('catid' => $v['cid']))
                        ->orderBy('inputtime DESC')
                        ->getQuery()->getSingleResult();
                    if (empty($audio)) continue;
                    $robots[$i]["cid"] = $v['cid'];
                    $robots[$i]["name"] = $v['name'];

                    $robots[$i]["title"] = $audio['title'];
                    $robots[$i]["time"] = $audio['time'];
                    $robots[$i]["image"] = $v['image'];
                    $robots[$i]["desc"] = $audio['desc'];
                    $robots[$i]["author"] = $audio['author'];
                    $robots[$i]["type"] = $this->isType($audio["type"]);
                    $i++;
                }
            }

            unset($arr[$key]['tablename']);
            unset($arr[$key]['modelid']);
            unset($arr[$key]['arrchildid']);
            $arr[$key]["list"] = $robots;
        }

        return array($hots, $arr);
    }

    public function getCategory($id, array $param)
    {
        $builder = $this->getBuilder('Models\Category', 'c')->columns('c.arrchildid,c.modelid,m.tablename')
            ->join('Models\Model', 'c.modelid = m.modelid', 'm')
            ->where('c.catid = :catid:', array('catid' => $id));
        $arr = $builder->getQuery()->getSingleResult();

        if (!empty($arr['tablename']) && $arr['tablename'] == "news") {
            return $this->getNewsList($id, $arr, $param);
        }
        if (!empty($arr['tablename']) && $arr['tablename'] == "picture") {
            return $this->getPictureList($id, $arr, $param);
        } else {
            return $this->getAudioList($id, $arr, $param);
        }

    }

    public function getNewsList($id, $arr, array $param)
    {
        $result = $this->getBuilder('Models\News')->columns('COUNT(*) AS total')
            ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')
            ->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;
        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'n' => 1,
            'limit' => $param['page'] == 1 ? 10 : 9
        ]);
        if ($result->total == 0 || $where['pagecount'] < $param['page']) return array(0, []);

        if (!$param['hot']) {
            $builder = $this->getBuilder('Models\News')->columns('id,title,catid as cid,description as [desc],username as author,inputtime as time,thumb as pic,typeid as type')
                ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')->orderBy('inputtime DESC')->limit($where['limit'])->offset($where['offset']);
        } else {
            $builder = $this->getBuilder('Models\News', 'n')->columns('n.id,n.title,n.catid as cid,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type')
                ->leftJoin("Models\Praise", 'n.id= substring_index(h.praiseid,"-",-1)', 'h')
                ->where('n.status = 99 and n.catid in (' . $arr['arrchildid'] . ')')->orderBy('h.praises DESC')->limit($where['limit'])->offset($where['offset']);
        }
        $robot = $builder->getQuery()->execute()->toArray();
        $robots = array();
        foreach ($robot as $v => $h) {
            list($ishot, $hits) = $this->getHits($arr['modelid'], $h['id']);
            $robots[$v] = $h;
            $robots[$v]["desc"] = strip_tags($h['desc']);
            $robots[$v]["ishot"] = $ishot;
            $robots[$v]["view"] = $hits;
            $robots[$v]["favorite"] = $this->getFavorite($h['cid'], $h['id']);
            $robots[$v]["comment"] = $this->getComment($h['cid'], $arr['modelid'], $h['id']);
            $robots[$v]["type"] = $this->isType($h["type"]);
        }
        return array($where['pagecount'], $robots);
    }

    public function getPictureList($id, $arr, array $param)
    {
        $result = $this->getBuilder('Models\Picture')->columns('COUNT(*) AS total')
            ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')
            ->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;

        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 10
        ]);
        if ($result->total == 0 || $where['pagecount'] < $param['page']) return array(0, []);
        if (!$param['hot']) {
            $builder = $this->getBuilder('Models\Picture')->columns('id,catid as cid,title,description as [desc],username as author,inputtime as time,thumb as pic,typeid as type')
                ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')->orderBy('inputtime DESC')->limit($where['limit'])->offset($where['offset']);
        } else {
            $builder = $this->getBuilder('Models\Picture', 'n')->columns('n.id,n.catid as cid,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type')
                ->leftJoin("Models\Praise", 'n.id= substring_index(h.praiseid,"-",-1)', 'h')
                ->where('n.status = 99 and n.catid in (' . $arr['arrchildid'] . ')')->orderBy('h.praises DESC')->limit($where['limit'])->offset($where['offset']);
        }
        $robot = $builder->getQuery()->execute()->toArray();
        $robots = array();
        foreach ($robot as $v => $h) {
            list($ishot, $hits) = $this->getHits($arr['modelid'], $h['id']);
            $robots[$v] = $h;
            $robots[$v]["desc"] = strip_tags($h['desc']);
            $robots[$v]["ishot"] = $ishot;
            $robots[$v]["view"] = $hits;
            $robots[$v]["like"] =  $this->getLike($arr['modelid'], $h['id']);
            $robots[$v]["favorite"] = $this->getFavorite($h['cid'], $h['id']);
            $robots[$v]["comment"] = $this->getComment($h['cid'], $arr['modelid'], $h['id']);
            $robots[$v]["type"] = $this->isType($h["type"]);

        }
        if ($param['page'] == 1)
            $robots[10]["video"] = $this->getBuilder('Models\Video')->columns('id,catid as cid,title,description as [desc],username as author,inputtime as time,thumb as pic')
                ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')->orderBy('inputtime DESC')->limit(1)->getQuery()->getSingleResult();
        return array($where['pagecount'], $robots);
    }

    public function getAudioList($id, $arr, array $param)
    {
        $builder = $this->getBuilder('Models\Category')->columns('COUNT(*) AS total')
            ->where('parentid = :parentid:', array('parentid' => $id));
        $result = $builder->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;

        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 10
        ]);
        if ($result->total == 0 || $where['pagecount'] < $param['page']) return array(0, []);
        $category = $this->getBuilder('Models\Category')->columns('catid as cid,siteid as sid,catname as name,image,modelid,arrchildid')
            ->where('parentid = :parentid:', array('parentid' => $id))->orderBy(!$param['hot'] ? 'listorder DESC' : 'cid DESC')->limit($where['limit'])->offset($where['offset'])
            ->getQuery()->execute()->toArray();
        $arr = array();
        $i=0;
        foreach ($category as $key => $cate) {
            $robot = $this->getBuilder('Models\Audio', 'n')->columns('n.id,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.typeid as type,nd.audio,COUNT(*) AS total')
                ->where('status = 99 and catid = :catid:', array('catid' => $cate['cid']))
                ->join('Models\AudioData', 'n.id = nd.id', 'nd')->orderBy('inputtime DESC')->limit(1)
                ->getQuery()->execute();
            if( $robot[0]['total']==0) continue;

            $robots = array();
            $arr[$i] = $cate;
            $arr[$i]["ishot"] = 1;
            $arr[$i]["view"] = 1;
            $arr[$i]["like"] = 1;
            $arr[$i]["comment"] = 1;
            $arr[$i]["total"] = $robot[0]['total'];
            foreach ($robot as $k => $v) {
                $audio = string2array($v['audio']);
                $robots[$k]["id"] = $v['id'];
                $robots[$k]["title"] = strip_tags($v['title']);
                $robots[$k]["desc"] = strip_tags($v['desc']);
                $robots[$k]["author"] = strip_tags($v['author']);
                $robots[$k]["list"] = !empty($audio) ? $audio : [['src' => '', 'content' => '']];
                $robots[$k]["type"] = $this->isType($v["type"]);
            }
            unset($arr[$i]['modelid']);
            unset($arr[$i]['arrchildid']);
            $arr[$i]["list"] = $robots;
            $i++;

        }
        return array($where['pagecount'], $arr);
    }

    public function getPicture($id)
    {
        $date = rcache($id, "picture");
        if (empty($date)) {
            $result = $builder = $this->getBuilder('Models\Picture', 'n')->columns('n.id,n.catid as cid,n.title,n.description,n.username,n.inputtime as time,n.thumb as pic,n.keywords,n.typeid as type,c.modelid,nd.pictureurls,nd.relation')
                ->join('Models\Category', 'n.catid = c.catid', 'c')
                ->join('Models\PictureData', 'n.id = nd.id', 'nd')
                ->where('n.status = 99 AND n.id = :id:', array('id' => $id))
                ->getQuery()->getSingleResult();
            if (!empty($result)) {
                list($ishot, $hits) = $this->getHits($result['modelid'], $result['id']);
                $date = array(
                    "id" => $result['id'],
                    "cid" => $result['cid'],
                    "mid" => $result['modelid'],
                    "title" => $result['title'],
                    "desc" => $result['description'],
                    "author" => $result['username'],
                    "ishot" => $ishot,
                    "view" => $hits,
                    "favorite" => $this->getFavorite($result['cid'], $result['id']),
                    "comment" => $this->getComment($result['cid'], $result['modelid'], $result['id']),
                    "time" => $result['time'],
                    "pic" => $result['pic'],
                    "type" => $this->isType($result["type"]),
                    "list" => $result['pictureurls'],
                );
            }
            wcache($id, $date, "picture");
        }
        $date['list'] = string2array($date['list']);
        return $date;
    }

    public function getVideoList($id, array $param)
    {
        $builder = $this->getBuilder('Models\Category', 'c')->columns('c.arrchildid,c.modelid,m.tablename')
            ->join('Models\Model', 'c.modelid = m.modelid', 'm')
            ->where('c.catid = :catid:', array('catid' => $id));
        $arr = $builder->getQuery()->getSingleResult();

        $result = $this->getBuilder('Models\Video')->columns('COUNT(*) AS total')
            ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')
            ->getQuery()->getSingleResult();

        $result->total = isset($result->total) ? $result->total : 0;
        $where = $this->getPage([
            'page' => $param['page'],
            'total' => $result->total,
            'limit' => 1
        ]);
        if ($result->total == 0 || $where['pagecount'] < $param['page']) return array(0, []);

        if (!$param['hot']) {
            $builder = $this->getBuilder('Models\Video')->columns('id,title,catid as cid,description as [desc],username as author,inputtime as time,thumb as pic,typeid as type')
                ->where('status = 99 and catid in (' . $arr['arrchildid'] . ')')->orderBy('inputtime DESC')->limit($where['limit'])->offset($where['offset']);
        } else {
            $builder = $this->getBuilder('Models\Video', 'n')->columns('n.id,n.title,n.catid as cid,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.typeid as type')
                ->leftJoin("Models\Praise", 'n.id= substring_index(h.praiseid,"-",-1)', 'h')
                ->where('n.status = 99 and n.catid in (' . $arr['arrchildid'] . ')')->orderBy('h.praises DESC')->limit($where['limit'])->offset($where['offset']);
        }
        $robot = $builder->getQuery()->execute()->toArray();
        $robots = array();
        foreach ($robot as $v => $h) {
            list($ishot, $hits) = $this->getHits($arr['modelid'], $h['id']);
            $robots[$v] = $h;
            $robots[$v]["desc"] = strip_tags($h['desc']);
            $robots[$v]["ishot"] = $ishot;
            $robots[$v]["view"] = $hits;
            $robots[$v]["like"] =  $this->getLike($arr['modelid'], $h['id']);
            $robots[$v]["favorite"] = $this->getFavorite($h['cid'], $h['id']);
            $robots[$v]["comment"] = $this->getComment($h['cid'], $arr['modelid'], $h['id']);
            $robots[$v]["type"] = $this->isType($h["type"]);
        }
        return array($where['pagecount'], $robots);
    }

    public function getVideo($id)
    {
        $date = rcache($id, "video");
        if (empty($date)) {
            $result = $builder = $this->getBuilder('Models\Video', 'n')->columns('n.id,n.catid as cid,n.title,n.description as [desc],n.username as author,n.inputtime as time,n.thumb as pic,n.keywords,n.typeid as type,c.modelid,nd.video,nd.relation,nd.content')
                ->join('Models\Category', 'n.catid = c.catid', 'c')
                ->join('Models\VideoData', 'n.id = nd.id', 'nd')
                ->where('n.status = 99 AND n.id = :id:', array('id' => $id))
                ->getQuery()->getSingleResult();
            if (!empty($result)) {
                $sql = "";
                if ($result['relation']) {
                    $relations = explode('|', trim($result['relation'], '|'));
                    $relations = array_diff($relations, array(null));
                    $relations = implode(',', $relations);
                    $sql = " AND id IN ($relations)";
                } elseif ($result['keywords']) {
                    $keywords = str_replace(array('%', "'"), '', $result['keywords']);
                    $keywords_arr = explode(' ', $keywords);
                    $key_array = array();
                    $number = 0;
                    $i = 1;
                    $sql .= " AND catid='$result[cid]'";
                    foreach ($keywords_arr as $_k) {
                        $sql2 = $sql . " AND keywords LIKE '%$_k%' AND id != " . abs(intval($result['id']));
                    }
                }
                $relation = $this->getBuilder('Models\Video', "n")->columns('n.id,n.title,n.username as author,n.inputtime as time,n.thumb as pic')
                    ->where("n.status = 99 $sql $sql2")->limit(4)
                    ->getQuery()->execute()->toArray();
                list($ishot, $hits) = $this->getHits($result['modelid'], $result['id']);
                $date = array(
                    "id" => $result['id'],
                    "cid" => $result['cid'],
                    "mid" => $result['modelid'],
                    "title" => $result['title'],
                    "desc" => $result['desc'],
                    "author" => $result['author'],
                    "ishot" => $ishot,
                    "view" => $hits,
                    "favorite" => $this->getFavorite($result['cid'], $result['id']),
                    "comment" => $this->getComment($result['cid'], $result['modelid'], $result['id']),
                    "time" => $result['time'],
                    "pic" => $result['pic'],
                    "type" => $this->isType($result["type"]),
                    "content" => strip_only_tags($result['content'], '<img>,<strong>'),
                    "list" => $result['video'],
                    "relation" => json_encode($relation, JSON_UNESCAPED_UNICODE)
                );
            }
            wcache($id, $date, "video");
        }
        $date['list'] = string2array($date['list']);
        $date['relation'] = json_decode($date['relation']);
        return $date;
    }

    public function getComment($cid, $modelid, $id)
    {
        $builder = $this->getBuilder('Models\Comment')->columns('total')
            ->where("commentid='content_$cid-$id-$modelid'");
        $robot = $builder->getQuery()->getSingleResult();
        return empty($robot->total) ? 0 : $robot->total;
    }

    public function getHits($modelid, $id)
    {
        $builder = $this->getBuilder('Models\Hits')->columns('views')
            ->where("hitsid='c-$modelid-$id'");
        $robot = $builder->getQuery()->getSingleResult();
        if ($robot) {
            $hits = $robot['views'];
            $ishot = $hits > 10 ? 1 : 0;
            return array($ishot, $hits);
        } else return array(0, 0);
    }

    public function getLike($modelid, $id)
    {
        $builder = $this->getBuilder('Models\Praise')->columns('praises')
            ->where("praiseid='c-$modelid-$id'");
        $robot = $builder->getQuery()->getSingleResult();
        return empty($robot->praises) ? 0 : $robot->praises;
    }
    public function getFavorite($cid, $id)
    {
        $builder = $this->getBuilder('Models\Favorite')->columns('count(*) as total')
            ->where("catid=$cid and aid=$id");
        $robot = $builder->getQuery()->getSingleResult();
        return empty($robot->total) ? 0 : $robot->total;
    }
    public function getModel()
    {
        $date = rkcache("modelAll");
        if (empty($date)) {
            $date = $this->getBuilder('Models\Model')->columns('modelid,siteid,name,tablename')
                ->getQuery()->execute()->toArray();
            wkcache("modelAll", $date);
        }
        return $date;
    }

}

