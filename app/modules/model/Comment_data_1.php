<?php

namespace Models;

use Engine\AbstractModel;
use Phalcon\DI;
use Phalcon\Paginator\Adapter\Model as PaginatorModel;
use Phalcon\Paginator\Adapter\NativeArray as PaginatorArray;
use Phalcon\Paginator\Adapter\QueryBuilder as PaginatorQueryBuilder;

class Comment_data_1 extends AbstractModel
{
    public function getPraiseById(array $comment, $where)
    {
        $keys = array_keys($comment);
        $values = array_values($comment);
        $status = $this->sqlBuilder('Comment_data_1')->update($keys, $values)->where(K($where), V($where))->getQuery()->execute();
        $result = $this->getBuilder('Comment_data_1')->columns("praise")
            ->where(K($where), V($where))->getQuery()->getSingleResult();
        return isset($result->praise) ? (int)$result->praise : 0;
    }


    public function getCommentCount($where = '')
    {
        if (empty($where)) {
            $where = '1=1';
        }

        $builder = $this->getBuilder('Comment_data_1')->columns('COUNT(*) AS total')->where($where)->limit(1);
        $result = $builder->getQuery()->getSingleResult();

        if (isset($result->total))
            return $result->total;
        else
            return 0;
    }


    public function getCommentList(array $param, $async = false)
    {
        $condi = (array)$param['where'];
        if (!isset($condi['commentid'])) {
            $condi = 'commentid=\'1\'';
        } else {
            $condi = "commentid='" . $condi['commentid'] . "'";
        }

        $page = isset($param['page']) ? $param['page'] : 1;
        $limit = isset($limit) ? $limit : 10;


        $total = $this->getCommentCount($condi);

        $offset = isset($where['offset']) ? $where['offset'] : 0;
        $pager = isset($where['pager']) ? $where['pager'] : null;


        // must remove, else appear notice. ready?
        // $where['offset']
        $builder = $this->getBuilder('Comment_data_1')
            ->columns('id, commentid, siteid, userid, username, creat_at, status, content, ip, reply, praise')
            ->where($condi)
            ->orderBy('id')
            ->limit($limit, $offset);
        $result = $builder->getQuery()->execute()->toArray();


        return $result;
    }


    public function saveComment(array $comment, $mode = 'INSERT', $where = '')
    {
        if (empty($comment)) {
            return false;
        }
        if (!is_string($comment['commentid'])) {
            return false;
        }
        if (isset($comment['id']) && $comment['id']) {
            $r = $this->getCommentById($comment['id']);
            if ($r) {
                if ($r->reply) {
                    $comment['floor_number'] = intval($r->floor_number) + 1;
                    $comment['content'] = '<div class="content">' . str_replace('<span></span>', '<span class="floor-num fr">' . $comment['floor_number'] . '</span><span class="blue f12">' . $r->username . ' 于 ' . date('Y-m-d H:i:s', $r->creat_at) . '发布</span>', $r->content) . '</div><span></span>' . $comment['content'];
                } else {
                    $comment['content'] = '<div class="content"><span class="floor-num fr">1</span><span class="blue f12">' . $r->username . ' 于 ' . date('Y-m-d H:i:s', $r->creat_at) . '发布</span><pre>' . $r->content . '</pre></div><span></span>' . $comment['content'];
                    $comment['floor_number'] = 1;
                }
                $comment['reply'] = 1;
            }
        }
        unset($comment['id']);
        $keys = array_keys($comment);
        $values = array_values($comment);

        // Request a transaction
//        $transaction = $this->getDI()->getTransactions()->get();
//        $this->setTransaction($transaction);
//        $status = $this->sqlBuilder('Comment_data_1')->insert($keys, $values)->getQuery()->execute();
//
//        $this->sqlBuilder('Models\Comment')->update(['total'], ['total + 1'])
//            ->where('commentid = :commentid:', ['commentid' => $comment['commentid']])->getQuery()->execute();
//
//        $transaction->commit();
//        if (!isset($status->getModel()->id) || $status->getModel()->id==0 ) {
//            return 0;
//        }
//        $row = $this->getCommentById($status->getModel()->id);

        $row = $this->getCommentById(91);
        $content = str_replace("<span class=\"floor-num fr\">", "['",$row->content);
        $content = str_replace("<span class=\"blue f12\">", "['",$content);
        $content = str_replace("</span>", "']", $content);
        $content = str_replace("<span>", "['",$content);
        $content = str_replace("<div class=\"content\">", "", $content);
        $content = str_replace("</div>", "", $content);
        $content = str_replace("']['", "'=>'", $content);
        $content = str_replace("']", "++", $content);
        $content = str_replace("['", "'],['", $content);
        print_r($content);
        pr( string2array($content));
        exit;
        $html = <<<EOF

                <div class="quote-list">
						<div class="quote-box">
							<div class="floor floor-5 bor-n">
								<div class="floor floor-4">
									<div class="floor floor-3 bor-b">
										<div class="floor floor-2 bor-b">
											<div class="floor floor-1">
												<div class="quote-tit">
													<span class="floor-num fr">1</span>
													<span class="quote-name">#19913   匿名用户 </span>
												</div>
												<div class="quote-con">救援方法：将船拖到岸边，再切割船底，或船侧。进入船内！</div>
											</div>
											<div class="quote-tit">
												<span class="floor-num fr">1</span>
												<span class="quote-name">#19913   匿名用户 </span>
											</div>
											<div class="quote-con">救援方法：将船拖到岸边，再切割船底，或船侧。进入船内！</div>
										</div>
										<div class="quote-tit">
											<span class="floor-num fr">2</span>
											<span class="quote-name">#19913   匿名用户 </span>
										</div>
										<div class="quote-con">救援方法：将船拖到岸边，再切割船底，或船侧。进入船内！</div>
									</div>
									<div class="quote-tit">
										<span class="floor-num fr">3</span>
										<span class="quote-name">#19913   匿名用户 </span>
									</div>
									<div class="quote-con">将船拖到岸边，再切割船底，或船侧。进入船内！</div>
								</div>

								<div class="quote-tit">
									<span class="floor-num fr">4</span>
									<span class="quote-name">#19913   匿名用户 </span>
								</div>
								<div class="quote-con">事故都还没调查出来，一帮人就开始道德绑架网络暴力逼船长自杀了，没死在灾难中，死在了这些喷狗的嘴上</div>
							</div>
						</div>
						<div class="quote-prompt">4条引用已隐藏</div>
					</div>
EOF;
        return 1;
    }


    public function getCommentById($id)
    {
        $builder = $this->getBuilder('Comment_data_1')
            ->columns('id, commentid, siteid, userid, username, creat_at, status, content, ip, reply,floor_number, praise')
            ->where('id = :id:', ['id' => $id])
            ->limit(1);
        $result = $builder->getQuery()->getSingleResult();

        if (isset($result->creat_at)) {
            $result->creat_at_formated = date('Y-m-d', $result['creat_at']);
        }

        return $result;
    }


    public function getReplyList($comment_id, $limit = 10)
    {
        $cmt_id = (int)$comment_id;

        $builder = $this->getBuilder('Comment_data_1')
            ->columns('username, creat_at, ip, status, content')
            ->where('id = \'$cmt_id\' AND reply <> 0')
            ->orderBy('id')
            ->limit($limit);

        $result = $builder->getQuery()->execute()->toArray();


        return $result;
    }


}
