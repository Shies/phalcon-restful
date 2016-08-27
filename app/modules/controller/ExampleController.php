<?php

namespace Controllers;

use Models\Audio;
use Models\Favorite;
use Models\Follow;
use Models\News;
use Phalcon\DI;

class ExampleController extends AbstractController
{
    public function pingAction()
    {
        echo "ping...";
    }

    public function cacheAction()
    {
//        wkcache('my-cache-data11', 20);
        //wcache(5, array("456", "grr33ff4", "655544"),'goods',60);
        $cache = Di::getDefault()->get('cacheData');
        $cache->clear();
//        $cache->IncrBy("my-cache-data11",  10);
//        $cache->DecrBy("my-cache-data", 10);
//        rcache(5, 'goods',0);
//        $data = rkcache('my-cache-data');
//        pr($data);
//        $this->di->get("cacheData")->delete('my-cache-data');

    }

    public function contentAction($id)
    {
        $news = new News();
        $json = $news->getContent($id);
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function pictureAction($id)
    {
        $news = new News();
        $json = $news->getPicture($id);
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function videoAction($id)
    {
        $news = new News();
        $json = $news->getVideo($id);
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function indexAction()
    {
        $news = new News();
        list($hots, $arr) = $news->getIndex(10);
        $json =
            [
                "time" => time(),
                "hot" => $hots,
                "list" => $arr
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function categoryAction($id, $page)
    {
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        $news = new News();
        list($pagecount, $list) = $news->getCategory($id, $parameters);
        $json =
            [
                "time" => time(),
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function videoListAction($id, $page)
    {
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        $news = new News();
        list($pagecount, $list) = $news->getVideoList($id, $parameters);
        $json =
            [
                "time" => time(),
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function categoryHotAction($id, $hot, $page)
    {
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        $parameters['hot'] = isset($hot) ? 1 : 0;
        $news = new News();
        list($pagecount, $list) = $news->getCategory($id, $parameters);
        $json =
            [
                "time" => time(),
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function menuAction()
    {
        $json = ["version" => "1.0.1",
            "time" => time(),
            "list" => [
                [
                    "cid" => 0,
                    "color" => "#000",
                    "name" => "首页"
                ],
                [
                    "cid" => 6,
                    "color" => "#31aafd",
                    "name" => "无论"
                ],
                [
                    "cid" => 7,
                    "color" => "#48cfad",
                    "name" => "有声"
                ],
                [
                    "cid" => 8,
                    "color" => "#ed5565",
                    "name" => "会色"
                ],
                [
                    "cid" => 9,
                    "color" => "#967adc",
                    "name" => "真探"
                ],
                [
                    "cid" => 10,
                    "color" => "#c8a500",
                    "name" => "财料"
                ],
                ["cid" => 11,
                    "color" => "#00a9c8",
                    "name" => "丝路"
                ],
                [
                    "cid" => 12,
                    "color" => "#51626f",
                    "name" => "上新"
                ]
            ]];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function audioAction($id, $page)
    {
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        $news = new Audio();
        list($pagecount, $cate, $list) = $news->getAudio($id, $parameters);
        $json =
            [
                "time" => time(),
                "sid" => $cate['sid'],
                "cid" => $cate['cid'],
                "name" => $cate['name'],
                "image" => $cate['image'],
                "desc" => $cate['desc'],
                "ishot" => 1,
                "view" => 1,
                "like" => 1,
                "comment" => 1,
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);

    }

    public function statesAction($userid, $id, $cid, $mid)
    {
        $userid = isset($userid) ? $userid : 0;
        $id = isset($id) ? intval($id) : 0;
        $cid = isset($cid) ? intval($cid) : 0;
        $mid = isset($mid) ? intval($mid) : 0;
        $news = new News();
        list($ishot, $hits) = $news->getHits($mid, $id);
        $isFavorite = false;
        $isFollow = false;
        if ($userid != 0) {
            $model_favorite = new Favorite();
            $isf = $model_favorite->countFavoriteList(array('userid' => $userid, 'aid' => $id, 'catid' => $cid));
            $isFavorite = $isf ? true : false;
            $model_follow = new Follow();
            $iso = $model_follow->countFollowList(array('userid' => $userid, 'catid' => $cid));
            $isFollow = $iso ? true : false;
        }
        $json =
            [
                "view" => $hits,//查看数,
                "like" => $news->getLike($cid, $mid, $id),//点赞数
                "favorites" => $news->getFavorite($cid, $id),//收藏数
                "comment" => $news->getComment($cid, $mid, $id),//评论数
                "favorite" => $isFavorite,//是否收藏
                "follow" => $isFollow,//是否关注
            ];
        echo json_encode($json);

    }
}
