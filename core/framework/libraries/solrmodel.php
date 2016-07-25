<?php
// namespace App\Model;

// use Exception;
// use Respect\Validation\ExceptionIterator;
// use ServiceException;
// use Log;

/**
 * 抽象类：solrORM基础类
 *
 * Class SolrModel
 * @package App\Model
 */
abstract class SolrModel
{
    public static $wt = 'json';
    public static $table = '';
    public static $primaryKey = '';
    public static $connection = 'master';
    const SUCCESS_STATUS = 0;
    const GROUP_FORMAT_TOTAL = 'group-total';   //取得总数格式，比如安安租取得小区房源总数
    const GROUP_FORMAT_INDEX = 'group-index';   //取得列表格式,比如新房


    /**
     * 设置查询条件，返回查询对象
     *
     * @param $where 含有:表示搜索语法，否则默认查询主键
     * @param int $limit
     * @param int $offset
     * @param null $columns
     * @return $this
     */
    public function setQuery($where, $limit = 20, $offset = 0, $columns = null, $orderby = '')
    {
        return (new SolrQuery(static::$connection))->select($columns ?: [static::$primaryKey])
            ->from(static::$table)
            ->where(false === stripos($where, ':') ? static::$primaryKey . ':' . $where : $where)
            ->limit($limit)
            ->orderby($orderby)
            ->offset($offset);
    }

    /**
     * 搜索单挑消息
     *
     * @param $id 查询主键ID值
     * @param int $limit 查询条数限制大小
     * @param int $offset 分页
     * @param null $columns 返回结果字段列表
     * @return mixed
     */
    public static function find($id, $limit = 20, $offset = 0, $columns = null, $orderby = '')
    {
        $jResult = (new SolrQuery(static::$connection))->select($columns ?: [static::$primaryKey])
            ->from(static::$table)
            ->where(false === stripos($id, ':') ? static::$primaryKey . ':' . $id : $id)
            ->limit($limit)
            ->orderby($orderby)
            ->offset($offset)
            ->find();
        if (self::isSuccess($jResult)) {
            return self::formatListResult($jResult, $limit, $offset);
        } else {
            throw new ServiceException(self::getErroMessage($jResult));
        }
    }

    /**
     * 根据查询条件查询多条记录
     *
     * @param $where 搜索条件
     * @param int $limit 查询条数限制大小
     * @param int $offset 分页
     * @param null $columns 返回结果字段列表
     * @return mixed
     */
    public static function findAll($where, $limit = 20, $offset = 1, $columns = null, $orderby = '')
    {
        $offset--; //solr是已0开始为当前位置
        $jResult = (new SolrQuery(static::$connection))->select($columns ?: [static::$primaryKey])
            ->from(static::$table)
            ->where($where)
            ->limit($limit)
            ->orderby($orderby)
            ->offset($offset)
            ->find();
            // dd($jResult);
        if (self::isSuccess($jResult)) {
            return self::formatListResult($jResult, $limit, $offset);
        } else {
            throw new ServiceException(self::getErroMessage($jResult));
        }
    }

    /**
     * 支持全部搜索查找
     *
     * @param $where
     * @param $filter
     * @param int $limit
     * @param int $offset
     * @param null $columns
     * @param string $orderby
     * @param array $other 其他参数值添加
     * @return array
     * @throws ServiceException
     */
    public static function findAllWithFilter(
        $where,
        $filter,
        $limit = 20,
        $offset = 1,
        $columns = null,
        $orderby = '',
        $other = []
    ) {
        $offset--; //solr是已0开始为当前位置
        //      dd(self::$primaryKey);
        $aResult = (new SolrQuery(static::$connection))->select($columns ?: [static::$primaryKey])
            ->from(static::$table)
            ->where($where)
            ->filter($filter)
            ->limit($limit)
            ->orderby($orderby)
            ->offset($offset);
        if (is_array($other) && !empty($other)) {
            foreach ($other as $key => $value) {
                $aResult->addParam($key, $value);
            }
        }
        $jResult = $aResult->find();
        if (self::isSuccess($jResult)) {
            return self::formatListResult($jResult, $limit, $offset);
        } else {
            throw new \Exception(self::getErroMessage($jResult));
        }
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public static function delete($id)
    {
        $jResult = (new SolrQuery(static::$connection))
            ->from(static::$table)
            ->where(false === stripos($id, ':') ? static::$primaryKey . ':' . $id : $id)
            ->delete();
        if (self::isSuccess($jResult)) {
            return ['iAutoID' => $id];
        } else {
            throw new ServiceException(self::getErroMessage($jResult));
        }
    }

    /**
     * 根据搜索条件删除
     * @param $where
     * @return mixed
     */
    public static function deleteWithFilter($where)
    {
        $jResult = (new SolrQuery(static::$connection))
            ->from(static::$table)
            ->where($where)
            ->delete();
        if (self::isSuccess($jResult)) {
            return true;
        } else {
            throw new ServiceException(self::getErroMessage($jResult));
        }
    }

    /**
     * 更新或者创建文档
     *
     * @param $data
     * @return mixed
     */
    public static function update($data)
    {
        $query = new SolrQuery(static::$connection);
        $jResult = $query->update($data);
        if (self::isSuccess($jResult)) {
            return ['iAutoID' => array_get($data, static::$primaryKey)];
        } else {
            throw new ServiceException(self::getErroMessage($jResult));
        }
    }

    /**
     * 操作是否成功
     * @param $jResult
     * @return bool
     */
    private static function isSuccess($jResult)
    {
        $aResult = self::getArrayResult($jResult);
        if (isset($aResult['responseHeader']['status']) && self::SUCCESS_STATUS == $aResult['responseHeader']['status']) {
            return true;
        }
        return false;
    }

    /**
     * 格式化列表式返回结果
     * @param $jResult
     * @param $iLimit
     * @param $iOffset
     * @return array
     */
    private static function formatListResult($jResult, $iLimit, $iOffset)
    {
        $aResult = self::getArrayResult($jResult);
        $iTotal = $aResult['response']['numFound'];
        $iFrom = $iOffset + 1;
        $iTo = $iFrom + count($aResult['response']['docs']);
        $iCurrentPage = ceil($iOffset / $iLimit + 1);
        $iPerPage = $iLimit;
        $iLastPage = ceil($iTotal / $iLimit);

        //格式化返回结构
        $aFormatResult = [];
        $aFormatResult['iTotal'] = $iTotal;
        $aFormatResult['iPerPage'] = $iPerPage;
        $aFormatResult['iCurrentPage'] = ($iCurrentPage - 1) ? $iCurrentPage - 1 : 1;
        $aFormatResult['iLastPage'] = $iLastPage;
        $aFormatResult['iFrom'] = $iFrom;
        $aFormatResult['iTo'] = $iTo;
        $aFormatResult['aData'] = $aResult['response']['docs'];
        return $aFormatResult;
    }

    /**
     * 格式化列表式返回结果
     * @param $jResult
     * @param $iLimit
     * @param $iOffset
     * @return array
     */
    private static function formatGroupResult($jResult, $groupFields, $iLimit, $iOffset, $format)
    {
        $aResult = self::getArrayResult($jResult);
        $iTotal = $aResult['grouped'][$groupFields]['ngroups'];
        $iFrom = $iOffset + 1;
        $iTo = $iFrom + count($aResult['grouped'][$groupFields]['groups']);
        $iCurrentPage = ceil($iOffset / $iLimit + 1);
        $iPerPage = $iLimit;
        $iLastPage = ceil($iTotal / $iLimit);

        //格式化返回结构
        $aFormatResult = [];
        $aFormatResult['iTotal'] = $iTotal;
        $aFormatResult['iPerPage'] = $iPerPage;
        $aFormatResult['iCurrentPage'] = ($iCurrentPage - 1) ? $iCurrentPage - 1 : 1;
        $aFormatResult['iLastPage'] = $iLastPage;
        $aFormatResult['iFrom'] = $iFrom;
        $aFormatResult['iTo'] = $iTo;
        $aData = [];
        foreach ($aResult['grouped'][$groupFields]['groups'] as $group) {
            if (self::GROUP_FORMAT_INDEX == $format) {     //新房搜索
                $aData[] = $group['doclist']['docs'][0];
            } else {
                if (self::GROUP_FORMAT_TOTAL == $format) {   //安安租小区房源数量之类的
                    $aData[$group['groupValue']]['iTotal'] = $group['doclist']['numFound'];
                }
            }
        }
        $aFormatResult['aData'] = $aData;
        return $aFormatResult;
    }

    /**
     * 取得错误信息
     * @param $jResult
     * @return string
     */
    private static function getErroMessage($jResult)
    {
        $aResult = self::getArrayResult($jResult);
        if (isset($aResult['error']['msg'])) {
            return $aResult['error']['msg'];
        } else {
            return "SOLR RETURN UNKNOW ERRO,ERRO_MESSAGE:" . serialize($aResult);
        }
    }

    /**
     * 按照数据格式返回结果
     * @param $mResult
     * @return array|mixed
     */
    private static function getArrayResult($mResult)
    {
        $aResult = [];
        switch (self::$wt) {
            case 'json':
                $aResult = json_decode($mResult, true);
                break;
            default:
                $aResult = json_decode($mResult, true);
        }
        return $aResult;
    }

    /**
     * 按照分组进行查询
     *
     * @param $groupQuery
     * @param $groupSort
     * @param $groupFields
     * @param null $where
     * @param null $filter
     * @param null $field
     * @param null $orderby
     * @param int $groupLimit
     * @param int $groupOffset
     * @param int $limit
     * @param int $offset
     * @param array $other
     * @return array
     * @throws ServiceException
     */
    public static function findByGroup(
        $groupQuery,
        $groupSort,
        $groupFields,
        $where = null,
        $filter = null,
        $field = null,
        $orderby = null,
        $groupLimit = 20,
        $groupOffset = 0,
        $limit = 20,
        $offset = 1,
        $other = [],
        $format = 'group-index'
    ) {
        $offset--; //solr是已0开始为当前位置
        $aResult = (new SolrQuery(static::$connection))->groupFiled($groupFields ?: [static::$primaryKey])
            ->select($field)
            ->setGroup(true)
            ->from(static::$table)
            ->where($where)
            ->filter($filter)
            ->orderby($orderby)
            ->groupQuery($groupQuery)
            ->groupSort($groupSort)
            ->groupLimit($groupLimit)
            ->groupOffset($groupOffset)
            ->limit($limit)
            ->offset($offset);
        if (is_array($other) && !empty($other)) {
            foreach ($other as $key => $value) {
                $aResult->addParam($key, $value);
            }
        }
        $aResult = $aResult->find();
        if (self::isSuccess($aResult)) {
            return self::formatGroupResult($aResult, $groupFields, $limit, $offset, $format);
        } else {
            throw new ServiceException(self::getErroMessage($aResult));
        }
    }
}