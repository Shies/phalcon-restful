<?php
namespace Engine;

use Engine\CurdBuilder;
use Phalcon\DI;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Mvc\Model\Query\Builder;

/**
 * Abstract Model.
 *
 * @method static findFirstById($id)
 * @method static findFirstByLanguage($name)
 *
 * @method DIBehaviour|\Phalcon\DI getDI()
 */
abstract class AbstractModel extends PhalconModel
{

    public static $_tbprefix = "";

    public function initialize()
    {
        self::$_tbprefix = config('config')['dbMaster']['prefix'];
        $this->setReadConnectionService('db');//读
        $this->setWriteConnectionService('dbMaster');//写
        $this->useDynamicUpdate(true);//关闭更新全字段
        $this->setup(array('notNullValidations' => false));//关闭ORM自动验证非空列的映射表
    }

    public function getSource()
    {
        return self::$_tbprefix . strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', end(explode("\\", get_class($this)))));
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        $reader = DI::getDefault()->get('annotations');
        $reflector = $reader->get(get_called_class());
        $annotations = $reflector->getClassAnnotations();

        return $annotations->get('Source')->getArgument(0);
    }

    /**
     * Get builder associated with table of this model.
     *
     * @param $table
     * @param string|null $tableAlias Table alias to use in query.
     * @return Builder
     */
    public function getBuilder($table, $tableAlias = null)
    {
        $table = $table != end(explode("\\", get_class($this))) ? $table : get_called_class();
        $builder = $this->getModelsManager()->createBuilder();
        if (!$tableAlias) {
            $builder->from($table);
        } else {
            $builder->addFrom($table, $tableAlias);
        }

        return $builder;
    }

    public function getProfile()
    {
        if (config('config')['profiler']) {
            $profiles = $this->getDI()->getProfiler()->getProfiles();
            foreach ($profiles as $profile) {
                if (preg_match('/^(DESCRIBE)/', $profile->getSQLStatement())
                || preg_match('/^(SELECT IF)/', $profile->getSQLStatement())) continue;
                echo "SQL: ", $profile->getSQLStatement(), "; ->", sprintf("%.4f", $profile->getTotalElapsedSeconds()), "ms <br>";
            }
        }
    }

    /**
     * load curdbuilder set write operation
     * @param $table
     * @param null $tableAlias
     * @return CurdBuilder
     */
    public function sqlBuilder($table, $tableAlias = null)
    {
        $table = $table != end(explode("\\", get_class($this))) ? $table : get_called_class();
        $builder = new CurdBuilder();
        if (!$tableAlias) {
            $builder->from($table);
        } else {
            $builder->addFrom($table, $tableAlias);
        }

        return $builder;
    }


    /**
     * get some pagination important params
     * @param array $where
     * @return array
     */
    public function getPage(array $where)
    {
        extract($where, EXTR_SKIP);
        if ($page <= 0) {
            $page = 1;
        }

        $pagecount = ceil($total / $limit);
        if ($page >= $pagecount) {
            $page = $pagecount;
        }
        $n = isset($n) ? $n : 0;
        if (!isset($offset)) {
            $offset = $page == 1 ? ($page - 1) * $limit : ($page - 1) * $limit + $n;
        }
        $offset = $offset > 0 ? $offset : 0;
        return ['page' => $page, 'limit' => $limit, 'offset' => $offset, 'pagecount' => $pagecount];
    }

    /**
     * @param $type
     * @return string
     */
    public function isType($type)
    {
        if ($type == 0) return "normal";
        elseif ($type == 2) return "pic";
        elseif ($type == 3) return "video";
        elseif ($type == 4) return "audio";
    }

}
