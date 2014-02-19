<?php
namespace core;
/*
 * ensure your table must have columns as following to make class work
 *
 * CREATE TABLE `item_etc_category` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `parent_id` int(11) NOT NULL DEFAULT '0',
        `name` varchar(255) NOT NULL,
        `sort` int(11) NOT NULL DEFAULT '0',
        `lft` int(11) NOT NULL DEFAULT '0',
        `rgt` int(11) NOT NULL DEFAULT '0',
        `section` int(11) NOT NULL DEFAULT '0',
        `depth` int(11) NOT NULL DEFAULT '0',
        `weight` int(11) NOT NULL DEFAULT '0',
        `cp_limit_type` tinyint(4) NOT NULL DEFAULT '0',
(*)        `cp_info` varchar(255) NOT NULL DEFAULT '',
        `view_tree_cache` text,
        PRIMARY KEY (`id`),
        KEY `index_parent_id` (`parent_id`),
        KEY `index_section_lft_rgt` (`section`,`lft`,`rgt`),
        KEY `index_section_rgt_lft` (`section`,`rgt`,`lft`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

this class support 2 mode, single root mode and multi-root mode

terminology:
    single root: all tree node be saved in single table, id of root is 0
    multi-root: save multiple trees in single table, id of root is non-zero

depth:
    root: 0

cp_limit_type:
    used to customize the depth of tree be copied, sometimes we just want to copy one part of the nodes of the tree
    value:
        0 - default, none control, this node and nodes under this node will be copied
        1 - until here, nodes until this depth will be copied, but not include this node
        2 - until this node, nodes until this depth will be copied, include this node

cp_info(optional):
    to save the copy information where the subtree is from, a typical info format is
    %class_name%:%id%

 */
class TreeModel extends Model {

    const INIT_SORT_ASC = 'a';
    const INIT_SORT_DESC = 'd';

    const NAVI_FROM_ROOT = 'r';
    const NAVI_FROM_SELF = 's';

    static public $lft_rgt;
    static public $save_lft_rgt_entity_list;

    protected $_root_name = 'root';
    protected $_root_id = 0;
    protected $_root_section = 0;

    /**
     * initialize tree
     * put right left/right value into table
     * @param bool $is_start
     */
    protected function initTreeLftRgtValue($start_node_id, $sort = self::INIT_SORT_ASC, $depth = null) {

        $id = 0;
        $section = $this->_root_section;
        $depth = $depth === null ? 0 : $depth;
        if (is_numeric($start_node_id)) {
            if ($start_node_id !== $this->_root_id) {
                $now_node = $this->queryByAnd(array('id' => $start_node_id), null, array('id', 'lft', 'rgt', 'section', 'depth'));
                if (empty($now_node)) {
                    return ;
                }
                self::$lft_rgt = $now_node['lft'];
                $section = $now_node['section'];
                $depth = $now_node['depth'];
            } else {
                self::$lft_rgt = 1;
            }
            $id = $start_node_id;
        } else {
            $section = $start_node_id['section'];
            $id = $start_node_id['id'];
            self::$lft_rgt ++;
            $depth ++;
        }
        self::$save_lft_rgt_entity_list[$id]['lft'] = self::$lft_rgt;
        self::$save_lft_rgt_entity_list[$id]['depth'] = $depth;
        $sorted_child_list = $this->getSortedChildList($id, $section, $sort);
        if (empty($sorted_child_list)) {
            self::$lft_rgt ++;
            self::$save_lft_rgt_entity_list[$id]['rgt'] = self::$lft_rgt;
            return ;
        }
        foreach ($sorted_child_list as $sorted_child) {
            $this->initTreeLftRgtValue($sorted_child, $sort, $depth);
        }
        self::$lft_rgt ++;
        self::$save_lft_rgt_entity_list[$id]['rgt'] = self::$lft_rgt;
    }
    /**
     * initTree
     * @param int $start_node_id
     * @param string $sort
     */
    public function initTree($start_node_id, $sort = self::INIT_SORT_ASC) {
        $this->initTreeLftRgtValue($start_node_id, $sort);
        if (empty(self::$save_lft_rgt_entity_list)) {
            return ;
        }
        foreach (self::$save_lft_rgt_entity_list as $node_id => $node_value) {
            $sql = "UPDATE {$this->table} SET lft = :lft, rgt = :rgt, depth = :depth WHERE id = :id";
            $this->exec($sql,
                array(':lft' => $node_value['lft'], ':rgt' => $node_value['rgt'], ':id' => $node_id, ':depth' => $node_value['depth']));
        }
    }

    /**
     * get sorted child list
     * @param int $node_id
     * @param string $section
     * @param string $sort
     * @param array $column_list
     */
    public function getSortedChildList($node_id, $section, $sort, $column_list = null) {
        switch ($sort) {
            case self::INIT_SORT_ASC:
                $sort = ' sort ASC ';    break;
            case self::INIT_SORT_DESC:
                $sort = ' sort DESC ';    break;
            default:
                $sort = null;    break;
        }
        if (is_array($node_id)) {
            $node_id = $node_id['id'];
        }
        $child_list = $this->queryAllByAnd(array('parent_id' => $node_id, 'section' => $section),
            null, $column_list, $sort);
        if (empty($child_list)) {
            return array();
        }
        return $child_list;
    }
    /**
     * get all child list
     * @param int $node_id
     * @param array $column_list
     * @return array | bool
     */
    public function getAllChildList($node_id, $column_list = null) {

        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt', 'section'));
        } else {
            return array();
        }
        if (empty($now_node)) {
            return false;
        }
        $sql = "SELECT " . $column_list === null ? '*' : implode(',', $column_list) . " FROM {$this->table} ";
        $sql .= "WHERE lft > :lft AND rgt < :rgt AND section = :section";
        $all_child_list = $this->queryAll($sql, array(':lft' => $now_node['lft'], ':rgt' => $now_node['rgt'], 'section' => $now_node['section']));
        if (empty($all_child_list)) {
            return array();
        }
        return $all_child_list;
    }
    /**
     * get root node of the tree
     * @param int | array $node_id
     * @return boolean|array
     */
    public function getRoot($node_id) {

        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt', 'section'));
        } else {
            return array();
        }
        if (empty($now_node)) {
            return false;
        }
        $sql = "
            SELECT MAX(rgt) + 1 AS rgt, section FROM {$this->table}
            WHERE
                parent_id = 0
            AND
                lft <= :lft
            AND
                rgt >= :rgt
            AND
                section = :section
            LIMIT 1
        ";
        $right_under_root = $this->queryRow($sql,
            array(':lft' => $now_node['lft'], ':rgt' => $now_node['rgt'], ':section' => $now_node['section']));
        if (empty($right_under_root)) {
            return false;
        }
        return array(
            'id' => $this->_root_id,
            'name' => $this->_root_name,
            'lft' => 0,
            'rgt' => $right_under_root['rgt'] + 1,
            'depth' => 0,
            'section' => $right_under_root['section']
        );
    }
    /**
     * get navi list from the tree
     * @param int $node_id
     * @param string $sort
     * @param bool $is_include_self
     * @return array | bool
     */
    public function getNaviList($node_id, $sort = self::NAVI_FROM_ROOT, $is_include_self = true) {

        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt', 'section'));
        } else {
            return array();
        }
        if (empty($now_node)) {
            return array();
        }
        $sql = "SELECT lft, rgt, section FROM {$this->table} WHERE section = :section ";
        if ($is_include_self) {
            $sql .= ' lft <= :lft AND rgt >= :rgt ';
        } else {
            $sql .= ' lft < :lft AND rgt > :rgt ';
        }
        if ($sort == self::NAVI_FROM_ROOT) {
            $sql .= ' ORDER BY lft ASC ';
        } else {
            $sql .= ' ORDER BY rgt ASC ';
        }
        $navi_list = $this->queryRow($sql,
        array(':lft' => $now_node['lft'], ':rgt' => $now_node['rgt'], ':section' => $now_node['section']));
        if (empty($navi_list)) {
            return array();
        }
        return $navi_list;
    }

    /**
     * get leaf list
     * @param string $section
     * @param int | null $node_id
     * @param array | null $column_list
     * @return multitype:|boolean
     */
    public function getLeafList($section, $node_id = null, $column_list = null) {

        $now_node = null;
        if (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt', 'section'));
        } elseif (is_array($node_id)) {
            $now_node = $node_id;
        }
        $sql = "SELECT " . $column_list === null ? '*' : implode(',', $column_list) . " FROM {$this->table} ";
        $sql .= "WHERE section = :section ";
        $param = array();
        $param[':section'] = $section;
        if (!empty($now_node)) {
            $sql .= " AND lft >= :lft AND rgt <= :rgt";
            $param[':lft'] = $now_node['lft'];
            $param[':rgt'] = $now_node['rgt'];
        }
        $sql .= "AND rgt - lft = 1";
        $leaf_list = $this->queryAll($sql, $param);
        if (empty($leaf_list)) {
            return array();
        }
        return $leaf_list;
    }

    /**
     * get parent
     * @param int $node_id
     * @return multitype:|boolean
     */
    public function getParent($node_id) {

        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('parent_id'));
        } else {
            return array();
        }
        if (empty($now_node)) {
            return array();
        }
        $parent = $this->queryByAnd(array('id' => $now_node['parent_id']));
        if (empty($parent)) {
            return array();
        }
        return $parent;
    }
    /**
     * get is leaf
     * @param array | int $node_id
     * @return bool
     */
    public function getIsLeaf($node_id) {

        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt'));
        } else {
            return false;
        }
        if (empty($now_node)) {
            return false;
        }
        return $now_node['rgt'] - $now_node['lft'] == 1 ? true : false;
    }
    /**
     * get count of descendants
     * @param int | array $node_id
     * @param bool $include_self
     * @return int
     */
    public function countDescendant($node_id, $include_self = false) {
        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt'));
        }
        if (empty($now_node)) {
            return 0;
        }
        return ($now_node['rgt'] - $now_node['lft'] - 1) / 2;
    }
    /**
     * get count of ancestors
     * @param int | array $node_id
     * @param bool $include_self
     * @return int
     */
    public function countAncestor($node_id, $include_self = false) {
        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('depth'));
        }
        if (empty($now_node)) {
            return 0;
        }
        return $now_node['depth'];
    }
    /**
     * check if node_id is the ancestor of des_node_id
     * @param int | array $node_id
     * @param bool $include_self
     * @return bool | null
     */
    public function getIsAncestor($node_id, $des_node_id) {
        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt'));
        }
        if (empty($now_node)) {
            return null;
        }
        if ($now_node['id'] == $this->_root_id) {
            return true;
        }
        if (is_array($des_node_id)) {
            $des_node = $des_node_id;
        } elseif (is_numeric($des_node_id)) {
            $des_node = $this->queryByAnd(array('id' => $des_node_id), null, array('lft', 'rgt'));
        }
        if (empty($des_node)) {
            return null;
        }
        if ($des_node['id'] == $this->_root_id) {
            return false;
        }
        return $now_node['lft'] < $des_node['lft'] && $now_node['rgt'] > $des_node['rgt'];
    }
    /**
     * check if node_id is the descendant of des_node_id
     * @param int | array $node_id
     * @param bool $include_self
     * @return bool | null
     */
    public function getIsDescendant($node_id, $des_node_id) {
        if (is_array($node_id)) {
            $now_node = $node_id;
        } elseif (is_numeric($node_id)) {
            $now_node = $this->queryByAnd(array('id' => $node_id), null, array('lft', 'rgt'));
        }
        if (empty($now_node)) {
            return null;
        }
        if ($now_node['id'] == $this->_root_id) {
            return false;
        }
        if (is_array($des_node_id)) {
            $des_node = $des_node_id;
        } elseif (is_numeric($des_node_id)) {
            $des_node = $this->queryByAnd(array('id' => $des_node_id), null, array('lft', 'rgt'));
        }
        if (empty($des_node)) {
            return null;
        }
        if ($des_node['id'] == $this->_root_id) {
            return true;
        }
        return $now_node['lft'] > $des_node['lft'] && $now_node['rgt'] < $des_node['rgt'];
    }

    /**
     * return : succeed: true;
     */
    public function move(array $node, array $to_node){
        // ensure both ids are numeric
        if(empty($node['id']) || empty($to_node['id'])){
            return 'id empty';
        }
        // cant move root node
        if ($node['id'] == $this->_root_id) {
            return 'can not move root node';
        }
        // cant move under same node
        if ($node['parent_id'] == $to_node['parent_id']) {
            return 'can not move under same node';
        }
        // cant move to your own descendant node
        if ($this->getIsDescendant($node, $to_node)) {
            return 'can not move to descendant node';
        }
        if($to_node['id'] === 0){
            $to_node['lft'] = 0;
        }
        $this->unshiftLftRgt($to_node);
        $to_node = $this->queryByAnd(array('id' => $to_node['id']));
        $node['parent_id'] = $to_node['id'];
        $this->update($node);
        $this->shiftLftRgt($node, $this->countDescendant($node) + 1);
        $this->initTree($to_node);
        return true;
    }

    /**
     * shift lft/rgt value
     * @param array $node
     * @param int $count
     */
    private function shiftLftRgt(array $node, $count){
        if(!is_numeric($node['parent_id'])){
            return ;
        }
        $shift_value = 2 * $count;
        // descendant of ancestor: lft, rgt + %shiftValue%
        $update_sql = "UPDATE `{$this->table}` SET lft = lft + :shift_value, rgt = rgt + :shift_value WHERE section = :section AND lft > :rgt AND rgt > :rgt";
        $this->exec($update_sql, array(':shift_value' => $shift_value, ':section' => $node['section'], ':rgt' => $node['rgt']));
        // ancestor: rgt + %shiftValue%
        $update_sql = "UPDATE `{$this->table}` SET rgt = rgt + :shift_value WHERE section = :section AND lft < :lft AND rgt > :rgt";
        $this->exec($update_sql, array(':shift_value' => $shift_value, ':section' => $node['section'], ':lft' => $node['lft'], ':rgt' => $node['rgt']));
    }
    /**
     * unshift lft/rgt value
     * @param array $node
     * @param bool $include_self
     */
    private function unshiftLftRgt(array $node, $include_self = true){

        $shift_value = $include_self ? $node['rgt'] - $node['lft'] + 1 : $node['rgt'] - $node['lft'] - 1;
        // descendant of ancestor: lft, rgt - %shiftValue%
        $update_sql = "UPDATE `{$this->table}` SET lft = lft - :shift_value, rgt = rgt - :shift_value WHERE section = :section AND lft > :rgt AND rgt > :rgt";
        $this->exec($update_sql, array(':shift_value' => $shift_value, ':section' => $node['section'], ':rgt' => $node['rgt']));
        // ancestor: rgt - %shiftValue%
        $update_sql = "UPDATE `{$this->table}` SET rgt = rgt - :shift_value WHERE section = :section AND lft < :lft AND rgt > :rgt";
        $this->exec($update_sql, array(':shift_value' => $shift_value, ':section' => $node['section'], ':lft' => $node['lft'], ':rgt' => $node['rgt']));
    }


















    /*
     * return :
     *         succeed: true;
     *         failure:
     *             James_Message::err message
     */
    public function move($toObj){
        // ensure both ids are numeric
        if(!is_numeric($this->id) || !is_numeric($toObj->id)){
            return James_Message::CATEGORY_MOVE_ERR_ID_NOT_NUMERIC;
        }
        // cant move root node
        if($this->isRoot){
            return James_Message::CATEGORY_MOVE_ERR_CANT_MOVE_ROOT;
        }
        // cant move under same node
        if ($this->parent->id == $toObj->id){
            return James_Message::CATEGORY_MOVE_ERR_CANT_MOVE_TO_SAME_NODE;
        }
        // single root mode: id of root is 0, compute lft rgt value
        if($this->id === 0){
            $rootRow = $this->rootAsRow;
            $this->rgt = $rootRow['rgt'];
            $this->lft = 0;
            $this->section = $rootRow['section'];
        }
        // when in multi root mode, moving between different trees is forbidden
        if ($this->isMulti && $this->section != $toObj->section){
            return James_Message::CATEGORY_MOVE_ERR_CANT_MOVE_BETWEEN_DIF_TREE;
        }
        // cant move to your own descendant node
        if($this->isAncestor($toObj)){
            return James_Message::CATEGORY_MOVE_ERR_CANT_MOVE_OWN_DESCENDANT;
        }
        $className = get_class($this);
        if($toObj->id === 0){
            $toObj->lft = 0;
        }
        $this->unshiftLftRgt();
        // reload database data
        $newTo = new $className($toObj->id);
        $this->parentId = $newTo->id;
        $this->save();
        $newTo->shiftLftRgt($this->descendantCount + 1);
        $newTo->initTree();
        return true;
    }
    /*
     */
    public function remove($includeSelf = true){
        if (!$this->id){
            return;
        }
        if ($includeSelf){
            $deleteSql = "DELETE FROM {$this->_tableName} WHERE section = {$this->section} AND lft >= {$this->lft} AND rgt <= {$this->rgt}";
            $this->_dbg->getCommand()->exec($deleteSql);
        }else{
            $deleteSql = "DELETE FROM {$this->_tableName} WHERE section = {$this->section} AND lft > {$this->lft} AND rgt < {$this->rgt}";
            $this->_dbg->getCommand()->exec($deleteSql);
        }
        $this->unshiftLftRgt($includeSelf);
    }
    /*
     * copy tree as desendant under this node, until specified depth, if exist
     *
     * cp_limit_type:
     * 0 - default, none control, this node and nodes under this node will be copied
     * 1 - until here, nodes until this depth will be copied, but not include this node
     * 2 - until this node, nodes until this depth will be copied, include this node
     */
    public function copy($cpSrcRootObj, $depth = 0){

        if(!is_numeric($this->id) || !is_numeric($cpSrcRootObj->id)){
            return;
        }
        // single root mode: id of root is 0, compute lft rgt value
        if($this->id === 0){
            $rootRow = $this->rootAsRow;
            $this->rgt = $rootRow['rgt'];
            $this->lft = 0;
            $this->section = $rootRow['section'];
        }
        // cant copy ancestor node under this node
        if ($cpSrcRootObj->isAncestor($this)){
            return ;
        }
        // className
        $className = get_class($this);
        $listClassName = $className . '_List';
        $listObj = new $listClassName();
        $oldDescendantList = $this->getDescendantList();

        $excludeIdArray = array();
        $srcTableName = $cpSrcRootObj->tableName;
        $sql = "
            SELECT
                id, lft, rgt, cp_limit_type
            FROM
                {$srcTableName}
            WHERE
                cp_limit_type != 0
        ";
        $rowArray = $this->_dbg->getCommand()->queryAll($sql);
        foreach ($rowArray as $row){
            $sql1 = "
                SELECT
                    id
                FROM
                    {$srcTableName}
                WHERE
            ";
            if ($row['cp_limit_type'] == '1'){
                $sql1 .= " lft >= {$row['lft']} AND rgt <= {$row['rgt']} ";
            }elseif ($row['cp_limit_type'] == '2'){
                $sql1 .= " lft > {$row['lft']} AND rgt < {$row['rgt']} ";
            }
            $rowArray1 = $this->_dbg->getCommand()->queryAll($sql1);
            foreach ($rowArray1 as $row1){
                $excludeIdArray[] = $row1['id'];
            }
        }
        $addCount = 0;
        $deleteCount = 0;
        $obj = new $className();
        $depthRootIdArray = array();
        $depthRootIdArray['1'] = $this->id;
        foreach ($cpSrcRootObj->getDescendantList() as $cpSrcObj){
            if($depth && $cpSrcObj->depth - $cpSrcRootObj->depth > $depth){
                continue;
            }
            if (in_array($cpSrcObj->id, $excludeIdArray)){
                continue;
            }
            if (!$this->_cpCheck()){
                continue;
            }
            $cpObj = clone $obj;
            $cpObj->parentId = $depthRootIdArray[$cpSrcObj->depth - $cpSrcRootObj->depth];
            $cpObj->name = $cpSrcObj->name;
            $cpObj->sort = $cpSrcObj->sort;
            $cpObj->section = $cpSrcObj->section;
            $cpObj->weight = $cpSrcObj->weight;
            $cpObj->cpLimitType = $cpSrcObj->cpLimitType;
            $cpObj->save();
            $cpObj->saveCpInfo();
            $depthRootIdArray[$cpSrcObj->depth - $cpSrcRootObj->depth + 1] = $cpObj->id;
            $addCount++;
        }
        $this->shiftLftRgt($addCount);
        $this->initTree();
    }
    /*
     * type:
     *         0: inside
     *         1: after
     *         2: before
     */
    const INSERT_SORT_AFTER = 1;
    const INSERT_SORT_BEFORE = 2;
    const INSERT_SORT_INSIDE = 0;
    public function insertWithSort($type){

        $className = get_class($this);
        $obj = new $className();
        if ($type == '1'){
            $obj->parentId = $this->id;
        }else{
            $obj->parentId = $this->parentId;
        }
        if (!$type == self::INSERT_SORT_AFTER){
            $obj->sort = $this->sort + 1;
        }elseif($type == self::INSERT_SORT_BEFORE){
            $obj->sort = $this->sort;
        }elseif($type == self::INSERT_SORT_INSIDE){
            $lastChild = array_pop($this->childList);
            $obj->sort = $lastChild->sort + 1;
        }
        $obj->name = 'new name';
        $obj->save();
        if ($type != self::INSERT_SORT_INSIDE){
            $updateSql = "
                UPDATE {$this->_tableName}
                    SET sort = sort + 1
                WHERE parent_id = {$this->parentId} ";
            if ($type == self::INSERT_SORT_AFTER){
                $updateSql .= " AND lft > {$this->lft} ";
            }elseif ($type == self::INSERT_SORT_BEFORE){
                $updateSql .= " AND lft >= {$this->lft} ";
            }
            $this->_dbg->getCommand()->exec($updateSql);
        }
    }
    /*
     */
    private function shiftLftRgt($count){
        if(!is_numeric($this->parentId)){
            return ;
        }
        $shiftValue = 2 * $count;
        // descendant of ancestor: lft, rgt + %shiftValue%
        $updateSql = "UPDATE {$this->_tableName} SET lft = lft + {$shiftValue}, rgt = rgt + {$shiftValue} WHERE section = {$this->section} AND lft > {$this->rgt} AND rgt > {$this->rgt} ";
        $this->_dbg->getCommand()->exec($updateSql);
        // ancestor: rgt + %shiftValue%
        $updateSql = "UPDATE {$this->_tableName} SET rgt = rgt + {$shiftValue} WHERE section = {$this->section} AND lft < {$this->lft} AND rgt > {$this->rgt} ";
        $this->_dbg->getCommand()->exec($updateSql);
    }
    /*
     * should be unshift the value,then remove the node
     */
    private function unshiftLftRgt($includeSelf = true){
        $shiftValue = $includeSelf ? $this->rgt - $this->lft + 1 : $this->rgt - $this->lft - 1;
        // descendant of ancestor: lft, rgt - %shiftValue%
        $updateSql = "UPDATE {$this->_tableName} SET lft = lft - {$shiftValue}, rgt = rgt - {$shiftValue} WHERE section = {$this->section} AND lft > {$this->rgt} AND rgt > {$this->rgt}";
        $this->_dbg->getCommand()->exec($updateSql);
        // ancestor: rgt - %shiftValue%
        $updateSql = "UPDATE {$this->_tableName} SET rgt = rgt - {$shiftValue} WHERE section = {$this->section} AND lft < {$this->lft} AND rgt > {$this->rgt}";
        $this->_dbg->getCommand()->exec($updateSql);
    }

    /*
     * overwrite this function if you want to customize the copy logic
     */
    protected function _cpCheck(){
        return true;
    }
    /*
     *  overwrite this function if you want to customize the copy logic
     */
    protected function saveCpInfo(){
        return;
    }
    /*
    */
    protected function ___getIsFirst(){
        if($this->parent){
            return $this->parent->childList[0]->id == $this->id ? true : false;
        }
        return false;
    }
    /*
    */
    protected function ___getIsLast(){

        if($this->parent){
            $lastKey = count($this->parent->childList) -1;
            $lastChild = $this->parent->childList[$lastKey];
            if($lastChild->id == $this->id){
                return true;
            }
        }
        return false;
    }

    /*
     */
    public function isAncestor($obj){
        if($this->id === 0){
            return true;
        }
        if($obj->id === 0){
            return false;
        }
        return $this->lft < $obj->lft && $this->rgt > $obj->rgt ? true : false;
    }
    /*
     */
    public function isDescendant($obj){
        if($this->id === 0){
            return false;
        }
        if($obj->id === 0){
            return true;
        }
        return $this->lft > $obj->lft && $this->rgt < $obj->rgt ? true : false;
    }
    /*
     */
    public function importFromYaml($yaml, &$savedIdArray = null){

        $yamlArray = array();
        if (!is_array($yaml)){
            $yamlStr = '';
            if (is_file($yaml)){
                if (!file_exists($yaml)){
                    return false;
                }
                $yamlStr = @file_get_contents($yaml);
            }else{
                $yamlStr = $yaml;
            }
            $yamlStr = str_replace("\t", '    ', $yamlStr);
            require_once(PROJECT_DIR . '/library/ext/spyc/spyc.php');
            $yamlArray = Spyc::YAMLLoad($yamlStr);
        }else{
            $yamlArray = $yaml;
        }
        if ($savedIdArray){
            $savedIdArray = $this->_saveYamlChildren($yamlArray, $this->id);
        }else{
            $this->_saveYamlChildren($yamlArray, $this->id);
        }
        str_replace(':', ':', $yamlStr, $savedCt);
        $yamlStr = null;
        $this->shiftLftRgt($savedCt);
        $isStart = false;
        if ($this->id == '0'){
            $isStart = true;
        }
        $this->initTree($isStart);
    }
    /*
     */
    protected function _saveYamlChildren($yamlArray, $rootId){

        $className = get_class($this);
        $obj = new $className();
        $idArray = array();
        foreach ($yamlArray as $key => $yaml) {
            if (is_array($yaml)) {
                $cloneObj = clone $obj;
                $cloneObj->parentId = $rootId;
                $cloneObj->name = $key;
                $cloneObj->save();
                $idArray[] = $cloneObj->id;
                $array = $this->_saveYamlChildren($yaml, $cloneObj->id);
                $idArray = array_merge($idArray, $array);
            }else{
                $cloneObj = clone $obj;
                $cloneObj->parentId = $rootId;
                $cloneObj->name = $yaml;
                $cloneObj->save();
                $idArray[] = $cloneObj->id;
            }
        }
        return array_unique($idArray);
    }
    /*
     */
    public function getTreeStyleString($depth = 0){
        $isIncludeSelf = $depth ? false : true;
        $rowArray = $this->_getDescendantRowArray($isIncludeSelf, 0, $depth);
        $rowArray = $this->_setDescendantRowName($rowArray);
        $treeStyleStr = '';
        foreach ($rowArray as $row){
            $treeStyleStr .= "{$row['ruleLine']} [{$row['id']}]{$row['name']}[lft{$row['lft']}][rgt{$row['rgt']}]<br>";
        }
        return $treeStyleStr;
    }
    /*
     * for miftree
     * see: http://mifjs.net/tree/
     *
     * {
            "property": {"name": "root"},
            "children": [
                {"property": {"name": "node1"}},
                {"property": {"name": "node2"}, "state": {"open": true},
                    "children":[
                        {"property": {"name": "node2.1"}},
                        {"property": {"name": "node2.2"}}
                    ]
                },
                {"property": {"name": "node4"}},
                {"property": {"name": "node3"}}
            ]
            "data" : {"id": "id_value"}
        }

        ->
        $array = array(
            'property' => array('name' => 'root'),
            'children' => array(
                array('property' => array('name' => 'node1')),
                array(
                    'property' => array('name' => 'node2',
                    'state' => array('open' => true)),
                    'children' => array(
                        array('property' => array('name' => 'node2.1')),
                        array('property' => array('name' => 'node2.2')),
                    )
                ),
                array('property' => array('name' => 'node3')),
                array('property' => array('name' => 'node4')),
            )
        );
     *
     *
     */
    public function getJsonString($depth = 0){
        if (file_exists(APP_CACHE . DS . $this->jsonCacheFile)){
//            return file_get_contents(APP_CACHE . DS . $this->jsonCacheFile);
        }
        $jsonArray = array();
        $isIncludeSelf = $depth ? false : true;
        $rowArray = $this->_getDescendantRowArray($isIncludeSelf, 0, $depth);
        $rowArray = $this->_setDescendantRowName($rowArray);
        $rowArray = $this->_setDescendantRowData($rowArray);
        $rootArray = array('0' => array(
            'property' => array('name' => 'root', 'id' => 0),
            'children' => array(),
            'data' => array()
        ));
        foreach($rowArray as $row){
            $target = $row['parent_id'];
            $id = $row['id'];
            if (!isset($rootArray[$target])){
                $rootArray[$target] = array('property' => array('name' => $row['name'], 'id' => $row['id']));
                if ($row['rgt'] - $row['lft'] != 1){
                    $rootArray[$target]['children'] = array();
                    $rootArray[$target]['loadable'] = true;
                }
                $rootArray[$target]['data'] = isset($row['data']) ? $row['data'] : array();
            }
            if (!isset($rootArray[$id])){
                $rootArray[$id] = array('property' => array('name' => $row['name'], 'id' => $row['id']));
                if ($row['rgt'] - $row['lft'] != 1){
                    $rootArray[$id]['children'] = array();
                    $rootArray[$id]['loadable'] = true;
                }
                $rootArray[$id]['data'] = isset($row['data']) ? $row['data'] : array();
            }
            $rootArray[$target]['children'][] = &$rootArray[$id];
        }
        $tree = $rootArray['0'];
        $rootArray = null;
        $json = json_encode(array($tree));
//        @file_put_contents(APP_CACHE . DS . $this->jsonCacheFile, $json);
        return $json;
    }
    /*
     */
    protected function ___getJsonCacheFile(){
        return 'json_' . get_class($this). "_{$this->id}";
    }
    /*
     * override this function if a custom name is needed
     */
    protected function _setDescendantRowName($rowArray){
        return $rowArray;
    }
    /*
     * override this function if a custom href is needed
     */
    protected function _setDescendantRowAhref($rowArray){
        return $rowArray;
    }
    /*
     * override this function if a custom data is needed
     */
    protected function _setDescendantRowData($rowArray){
        return $rowArray;
    }
    /*
     */
    protected function _insert($isForce = false){
        if ($this->isRoot){
            return '';
        }
        return parent::_insert($isForce);
    }
    /*
     * type:
     *         0 - tree style
     *         1 - mif tree
     */
    protected function _getDescendantRowArray($selfInclude = false, $type = 0, $depth = 0){
        $lftRgtCond = '';
        if($this->id !== 0){
            if ($selfInclude){
                $lftRgtCond = " AND lft >= {$this->lft} AND rgt <= {$this->rgt} ";
            }else{
                $lftRgtCond = " AND lft > {$this->lft} AND rgt < {$this->rgt} ";
            }
        }else{
            $this->section = 0;
            $this->depth = 0;
        }
        $sql = "
            SELECT
                id, parent_id, section, lft, rgt, depth, name
                ,depth - {$this->depth} AS tempDepth
            FROM
                {$this->_tableName}
            WHERE
                section = {$this->section}
                {$lftRgtCond}
        ";
        if ($depth){
            $tmpDepth = $this->depth + $depth;
            $sql .= " AND depth <= {$tmpDepth} ";
        }
        $sql .=' ORDER BY lft ASC ';
        try{
            $rowArray = $this->_dbg->getCommand()->queryAll($sql);
        }catch (Exception $e){
            echo $this->id . $sql;
            return;
        }
        if ($type == 1){
            return $rowArray;
        }
        return $this->_setTreeRuleLine($rowArray);
    }
    /*
     */
    protected function _setTreeRuleLine($rowArray){

        $lastChildIdParentIdArray = $this->_getLastChildIdParentIdArray();
        $tempArray = array();
        foreach($rowArray as $key => $row){
            $tempDepth = $row['tempDepth'];
            $ruleLine = '';
            if ($tempDepth){
                if (isset($lastChildIdParentIdArray[$row['id']])){
                    $tempArray[$tempDepth] = '&nbsp;&nbsp;&nbsp;';
                }else{
                    $tempArray[$tempDepth] = '&#9474;';
                }
                $tempTempArray = array_slice($tempArray, 0, $tempDepth - 1);
                $ruleLine = implode($tempTempArray);
                if (isset($lastChildIdParentIdArray[$row['id']])){
                    $ruleLine .= '&#9492;';
                }else{
                    $ruleLine .= '&#9500;';
                }
            }
            $rowArray[$key]['ruleLine'] =  $ruleLine;
        }
        return $rowArray;
    }

    /*
     */
    protected function _getFirstChildIdParentIdArray($depth = 0){
        if($this->id === 0){
            $this->section = 0;
        }
        $firstChildIdParentIdArray = array();
        $bindArray = array();
        $rootEliminateSql = '';
        if ($this->section){
            $rootEliminateSql = ' AND parent_id != 0 ';
        }
        $sql = "
            SELECT
                a.id AS id, a.parent_id AS parent_id
            FROM
                {$this->_tableName} a
            WHERE
            NOT EXISTS (
                SELECT
                    1
                FROM
                    {$this->_tableName}
                WHERE
                    parent_id = a.parent_id
                AND
                    lft < a.lft
                AND
                    section = :section
                    {$rootEliminateSql}
            )
            AND
            a.section  = section
            {$rootEliminateSql}
        ";
        if ($depth){
            $tmpDepth = $this->depth + $depth;
            $sql .= " AND depth <= {$tmpDepth} ";
        }
        $sql .= ' ORDER BY lft ASC ';
        $bindArray[':section'] = $this->section;
        $rowArray = $this->_dbg->getCommand()->queryAll($sql, $bindArray);
        foreach($rowArray as $row){
            $firstChildIdParentIdArray[$row['id']] = $row['parent_id'];
        }
        return $firstChildIdParentIdArray;
    }
    /*
     */
    protected function _getLastChildIdParentIdArray($depth = 0){
        if($this->id === 0){
            $this->section = 0;
        }
        $lastChildIdParentIdArray = array();
        $bindArray = array();
        $rootEliminateSql = '';
        if ($this->section){
            $rootEliminateSql = ' AND parent_id != 0 ';
        }
        $sql = "
            SELECT
                a.id AS id, a.parent_id AS parent_id, a.rgt AS rgt
            FROM
                {$this->_tableName} a
            WHERE
            NOT EXISTS (
                SELECT
                    1
                FROM
                    {$this->_tableName}
                WHERE
                    parent_id = a.parent_id
                AND
                    lft > a.lft
                AND
                    section = :section
                    {$rootEliminateSql}
            )
            AND
            section  = :section
            {$rootEliminateSql}
        ";
        if ($depth){
            $tmpDepth = $this->depth + $depth;
            $sql .= " AND depth <= {$tmpDepth} ";
        }
        $sql .= ' ORDER BY lft ASC ';
        $bindArray[':section'] = $this->section;
        $rowArray = $this->_dbg->getCommand()->queryAll($sql, $bindArray);
        foreach($rowArray as $row){
            $lastChildIdParentIdArray[$row['id']] = $row['parent_id'];
        }
        return $lastChildIdParentIdArray;
    }
    /*
     * used in smarty {html_options} tag
     * return array
     *     array(
     *         %id% => %name%
     * )
     */
    protected function ___getAllOptionsId(){
        $rowArray = $this->_getDescendantRowArray(true);
        $allOptionsId = $this->_getOptions($rowArray);
        return $allOptionsId;
    }
}