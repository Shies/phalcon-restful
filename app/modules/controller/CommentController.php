<?php
namespace Controllers;

use Models\News;
use Models\Badword;
use Models\Comment_data_1;
use Models\Praise;

use Engine\Filter;
use Phalcon\DI;
use Phalcon\Mvc\Model\Criteria;
use Phalcon\Paginator\Adapter\Model as Paginator;
use Phalcon\Mvc\View\Simple as SimpleView;


class CommentController extends AbstractController
{
    # 查看当前接口联调是否OK
    public function pingokAction()
    {
        echo 'ping ok';
    }


    # 获取当前评论对象(eg：article、feed、wechat)
    public function articleAction($id)
    {
        header('Access-Control-Allow-Origin:*');
        if (empty($id) == 1) {
            callback(false, '无效参数');
        }


        $badword = new Badword();
        $badlist = $badword->getBadWord();


        $news = new News();
        $article = $news->getArticleInfo($id);


        callback(true, 'ok', 200,
            [
                'badword' => $badlist,
                'article' => $article
            ]
        );
    }


    # 获取当前评论点赞人数
    public function praiseAction($id)
    {
        /* *
		 * id         => 自增ID 
		 * id_value   => 对象值
		 * user_id    => 用户ID
		 * dateline   => 时间戳
		 * ip_address => IP地址
		 */
        if (empty($id)) {
            callback(false, '无效参数');
        }

        $cmt = new Comment_data_1();
        $arr = $cmt->getPraiseById(['praise' => 'praise + 1'], ['id' => $id]);
        die(json_encode($arr));
    }


    # 插入当前评论详情
    public function commentAction()
    {
        /* *
		 * id        => 自增ID
		 * commentid => 对象ID 
		 * siteid    => 站点ID
		 * userid    => 用户ID
		 * username  => 用户名
		 * creat_at  => 时间戳
		 * ip		 => IP地址
		 * status    => 展示状态
		 * content   => 评论内容
		 * direction => 指导数
		 * support   => 支持数/Like
		 * reply	 => 是否回复(1/0)
		 */
        if (!$this->request->isPost()) {
            callback(false, '无效请求');
        }
        $id = $this->request->getPost('id');
        $userid = $this->request->getPost('userid');
        $username = $this->request->getPost('username');
        $commentid = $this->request->getPost('commentid');
        $id = isset($id) ? $id : 0;
        if (empty($commentid)) {
            callback(false, '无效参数');
        }

        if ($commentid) {
            list($text, $cid, $ids, $mid) = explode('-', $commentid);
            $commentid = 'content_' . intval($cid) . '-' . intval($ids) . '-' . intval($mid);
        }

        $content = htmlspecialchars(trim($this->request->getPost('content')));

        $content = $this->checkContent($content);
        if (empty($content) ||
            strlen($content) > 200
        ) {
            callback(false, "评论内容不能为空");
        }


        $cmt = new Comment_data_1();
        $comment = array(
            'id' => $id,
            'commentid' => $commentid, // eg cmt_obj_id
            'siteid' => 1,
            'userid' => $userid,
            'username' => $username,
            'creat_at' => TIMESTAMP,
            'status' => 1,
            'content' => $content,
            'ip' => getIp(),
            'praise' => 0,
        );


        /*$queue = new \Engine\Queue\redis(array(
            'host' => '172.16.8.113',
            'port' => 6379,
            'persistent' => true,
        ));



        $count = intval(null);
        $queue->push('comment', serialize($comment));
        while ($queue->size('comment'))
        {
            // if concurrency gt 100, auto flush redis db, here is just a temporary test
            if ($queue->size('comment') > 100)
            {
                $queue->remove('comment');
                $result['err'] = 1;
                $result['msg'] = 'The queue length already gt maxinum';
                die(json_encode($result));
            }

            if (!$queue->view('comment', 0))
            {
                break;
            }

            $content = $queue->pop('comment', 30);
            if (empty($content) ||
                is_array($content))
            {
                continue;
            }

            $comment = unserialize($content);
            $cmt->saveComment($comment) AND $count++;
            usleep(100);
        }
*/

        $status = $cmt->saveComment($comment);
        if (empty($status)) {
            // $queue->remove('comment');
            callback(false, '保存失败');
        } else {
            callback(true, '保存成功', 200,
                [
                    'comment' => $status
                ]
            );
        }
    }


    private function checkCNContent($content)
    {
        $badword = NULL;
        if (class_exists('\\Models\\Badword')) {
            $badword = new Badword();
        }

        return $badword->replace_badword($content);
    }


    private function checkContent($content)
    {

        $filter = new Filter();

        // filter zh_cn for cms backend manage
        $content = $this->checkCNContent($content);

        $filter->setWord('fuck');
        $filter->setWord('shit');
        $filter->setWord('dick');
        $filter->setWord('rubbish');

        $badlist = $filter->search($content);
        if (empty($badlist)) {
            return $content;
        }

        $find = [];
        foreach ($badlist AS $key => $value) {
            if (is_array($value)) {
                list($word, $pos) = array_values($value);
                $find[] = substr($content, $pos, strlen($word));
            }
        }
        return str_replace(
            (array)$find,
            '*', $content
        );
    }


    # 获取当前回复列表(每个评论支持多个引用)
    public function replyAction($id)
    {
        if (!$this->request->isGet()) {
            callback(false, '无效请求');
        }

        if (empty($id)) {
            callback(false, '无效参数');
        }


        $commentid = intval($id);


        $cmt = new Comment_data_1();
        $reply = $cmt->getReplyList($commentid);
        if (empty($reply)) {
            callback(false, '回复不能为空');
        }


        die(json_encode($reply, JSON_UNESCAPED_UNICODE));
    }


    # 循环加载更多吧！目前只能想到这么多
    public function loopAction($commentid, $page)
    {
        if (!$this->request->isGet()) {
            callback(false, '无效请求');
        }

        if (empty($commentid)) {
            callback(false, '无效参数');
        }

        $page = intval($page);
        if ($commentid) {
            list($text, $cid, $id, $mid) = explode('-', $commentid);
            $commentid = 'content_' . intval($cid) . '-' . intval($id) . '-' . intval($mid);
        }

        $cmt = new Comment_data_1();
        $comment_list = $cmt->getCommentList([
            'where' => [
                'commentid' => $commentid
            ],
            'page' => $page,
        ], true
        );
        die(json_encode($comment_list, JSON_UNESCAPED_UNICODE));
    }

}