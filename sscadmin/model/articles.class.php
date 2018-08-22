<?php

if (!defined('IN_LIGHT')) {
    die('KCAH');
}

class articles
{
    static public function getItem($id, $status = 1)
    {
        $sql = 'SELECT * FROM articles WHERE article_id = ' . intval($id);
        if ($status !== NULL) {
            $sql .= " AND status = $status";
        }

        return $GLOBALS['db']->getRow($sql);
    }

    static public function getItems($category_id = -1, $status = -1, $start = -1, $amount = DEFAULT_PER_PAGE)
    {
        $sql = 'SELECT a.*,c.name as category_name FROM articles a LEFT JOIN article_categories c ON a.category_id = c.category_id WHERE 1';
        if ($category_id != -1) {
            if (preg_match('`^\d+$`', $category_id)) {
                $sql .= " AND c.category_id = " . intval($category_id);
            }
            else {
                $sql .= " AND c.name = '$category_id'";
            }
        }
        if ($status != -1) {
            $sql .= " AND status = " . intval($status);
        }
        //$sql .= ' ORDER BY article_id DESC';
        if ($start > -1) {
            $sql .= " LIMIT $start, $amount";
        }
        $result = $GLOBALS['db']->getAll($sql);

        return $result;
    }

    static public function getItemsNumber($category_id = 0, $status = -1)
    {
        $sql = 'SELECT count(*) AS count FROM articles WHERE 1';
        if ($category_id !== 0) {
            $sql .= " AND category_id = " . intval($category_id);
        }
        if ($status !== -1) {
            $sql .= " AND status = " . intval($status);
        }

        $result = $GLOBALS['db']->getRow($sql);

        return $result['count'];
    }

    static public function getItemsById($ids)
    {
        if (!is_array($ids)) {
            throw new exception2('invalid args');
        }

        $sql = 'SELECT * FROM articles WHERE article_id IN(' . implode(',', $ids) . ')';

        return $GLOBALS['db']->getAll($sql, array(),'article_id');
    }

    static public function addItem($data)
    {
        if (!is_array($data)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->insert('articles', $data);
    }

    static public function updateItem($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->updateSM('articles',$data,array('article_id'=>$id));
    }

    static public function deleteItem($id, $realDelete = false)
    {

        if (!is_numeric($id) || $id <= 0) {
            throw new exception2('invalid args');
        }

        if ($realDelete) {
            $sql = "DELETE FROM articles WHERE article_id = " . intval($id);
            $type = 'd';
        }
        else {
            $sql = "UPDATE articles SET status = 0 WHERE article_id = " . intval($id);
            $type = 'u';
        }

        return $GLOBALS['db']->query($sql, array(), $type);
    }

    /**
     * 文章分类模型
     */
    static public function getCategory($id)
    {
        $sql = 'SELECT * FROM article_categories WHERE category_id = ' . intval($id);

        return $GLOBALS['db']->getRow($sql);
    }

    //得到分类列表
    static public function getCategories()
    {
        $sql = 'SELECT * FROM article_categories WHERE 1';
        $sql .= ' ORDER BY category_id ASC';
        $result = $GLOBALS['db']->getAll($sql, array(),'category_id');

        return $result;
    }

    //增加文章分类
    static public function addCategory($data)
    {
        if (!is_array($data)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->insert('article_categories', $data);
    }

    static public function updateCategory($id, $data)
    {
        if (!is_numeric($id)) {
            throw new exception2('invalid args');
        }

        return $GLOBALS['db']->updateSM('article_categories',$data,array('category_id'=>$id));
    }

    static public function deleteCategory($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            throw new exception2('invalid args');
        }

        if (articles::getItems($id)) {
            throw new exception2('该分类下有文章，不能删除');
        }

        $sql = "DELETE FROM article_categories WHERE category_id = " . intval($id);

        return $GLOBALS['db']->query($sql, array(), 'd');
    }
}
?>