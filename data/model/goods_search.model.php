<?php

class goods_searchModel extends SolrModel {

    public static $table = 'test';
    public static $primaryKey = 'id';
    public static $connection = 'goods';

    const DEFAULT_PAGE = 1;
    const DEFAULT_PAGESIZE = 10;

    //允许solr查询的字段
    protected $searchable = [
        'goods_id',
        'goods_commonid',
        'kw',
        'brand_id',
        'gc_id',
        'goods_promotion_price',
        'goods_promotion_price-egt',
        'goods_promotion_price-elt',
        'goods_price',
        'have_gift',
        'goods_promotion_type',
        'goods_promotion_type-in',
        'goods_verify',
        'goods_state',
        'goods_lock',
        '_qf',
        '_fl',
        'page',
        'page_size',
    ];

    public function mobile_search(array $params, $fields, $order = '')
    {
        $condition = $this->getSearchFilters($params);
        //search with filters from solr
        $searchResult = static::findAllWithFilter($condition['q'], $condition['fq'], $condition['limit'], $condition['offset'], $condition['fl'], $condition['orders'], $condition['setParams']);
        //get more fields from DB with searched goods IDs
        $model = Model('goods');
        if($searchResult && $searchResult['iTotal'] && $searchResult['aData']) {
            $total = $searchResult['iTotal'];
            $searchList = $searchResult['aData'];
            //DB condition
            foreach ($searchList as $list) {
                $goodsIDs[] = $list['id'];
            }
            $where['goods_id'] = ["in", implode(',', $goodsIDs)];  //goods_state & goods_verify
        }
        $goodsList = $model->getGoodsListByColorDistinct($where, $fields);
        return $goodsList;
    }

    /**
     * 组织solr搜索参数
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getSearchFilters($params)
    {
        $condition = [];

        //去除无用参数
        $this->_searchFilter($params);

        //处理rows & offset
        $condition['limit'] = (isset($params['page_size']) && $params['page_size']) ? $params['page_size'] : 10;
        $condition['offset'] = (isset($params['page']) && $params['page']) ? $params['page'] : 1;
        unset($params['page']);
        unset($params['page_size']);

        //处理关键词kw
        if(isset($params['kw']) && $params['kw']) {
            $kw = $params['kw'];
            //处理关键词中带空格做自然分割的情况，此时的请求需要是q = "A" AND "B" AND ...
            
            $condition['q'] = '"' . $kw . '"';
            //处理指定搜索字段的情况
            if(isset($params['_qf']) && $params['_qf']) {
                $condition['setParams']['qf'] = $params['_qf'];
            }
            //设置query模式
            $condition['setParams']['defType'] = 'edismax';
            $condition['setParams']['stopwords'] = true;
            $condition['setParams']['lowercaseOperators'] = true;
            unset($params['kw']);
        }

        //goods_promotion_type-in
        // if(isset($params['goods_promotion_type-in']) && $params['goods_promotion_type-in']) {
        //     if(!is_array($params['goods_promotion_type-in'])) {
        //         $inValuesArr = explode(',', $params['goods_promotion_type-in']);
        //     } else {
        //         $inValuesArr = $params['goods_promotion_type-in'];
        //     }
        //     $comma = "";
        //     foreach ($inValuesArr as $inValue) {
        //         $sIn .= $comma . "goods_promotion_type:{$inValue}";
        //         $comma = "%20OR%20";
        //     }
        //     $condition['fq'][] = $sIn;
        //     unset($params['goods_promotion_type-in']);
        // }

        //价格
        // if(isset($params['goods_promotion_price-egt']) && isset($params['goods_promotion_price-elt'])) {
        //     $condition['fq']['goods_promotion_price'] = "[{$params['goods_promotion_price-egt']}%20TO%20{$params['goods_promotion_price-elt']}]";
        // } elseif (isset($params['goods_promotion_price-egt'])) {
        //     $condition['fq']['goods_promotion_price'] = "[{$params['goods_promotion_price-egt']}%20TO%20*]";
        // } elseif (isset($params['goods_promotion_price-elt'])) {
        //     $condition['fq']['goods_promotion_price'] = "[*%20TO%20{$params['goods_promotion_price-elt']}]";
        // }
        // unset($params['goods_promotion_price-egt']);
        // unset($params['goods_promotion_price-elt']);

        // 处理"params-x"格式的请求参数
        foreach ($params as $key => $value) {
            $index = strripos($key, '-');
            $tailOp = substr($key, (-$index + 1));
            if($index && in_array($tailOp, ['in', 'range'])) {
                $sKey = substr($key, 0, $index);
                switch ($tailOp) {
                    case 'in':
                        if(!is_array($value)) {
                            $inValuesArr = explode(',', $value);
                        }
                        // $comma = "";
                        // foreach ($inValuesArr as $inValue) {
                        //     $sIn .= $comma . "{$sKey}:{$inValue}";
                        //     $comma = " OR ";
                        // }
                        //todo: in option
                        $condition['fq']['in'][$sKey] = $inValuesArr;
                        break;
                    case 'range':
                        list($left, $right) = $value;
                        if(!$left) {
                            $left = '*';
                        }
                        if(!$right) {
                            $right = '*';
                        }
                        $condition['fq'][$sKey] = rawurlencode("[{$left} TO {$right}]");
                    default:
                        break;
                }
                unset($params[$key]);
            }
        }

        //处理fl
        $condition['fl'] = (isset($params['_fl']) && $params['_fl']) ? $params['_fl'] : null;
        unset($params['_fl']);

        //剩余未处理的参数都认为是fq参数
        if($params) {
            foreach ($params as $key => $value) {
                $condition['fq'][$key] = $value;
            }
        }
        unset($params);

        return $condition;
    }

    /**
     * 过滤搜索参数
     * @param  array  &$params [description]
     * @return [type]          [description]
     */
    private function _searchFilter(array &$params)
    {
        foreach ($params as $key => $value) {
            if(!in_array($key, $this->searchable)) {
                unset($params[$key]);
            }
        }
    }

    public static function update($iAutoID)
    {
        $aXfInfo = XfInterface::getSolrUpdateInfo($iAutoID);
        if(empty($aXfInfo['iAutoID'])){
            throw new ServiceException('不存在该新房');
        }
        $aPropTypeID = $aXfInfo['aPropTypeID'];
        $aPropType = $aXfInfo['aPropType'];
        foreach ($aPropTypeID as $iPropTypeID) {
            if (!in_array($iPropTypeID, $aPropType)) {
                parent::delete($aXfInfo['iAutoID'] . '-' . $iPropTypeID);
            }
        }
        foreach($aXfInfo['solr'] as $document){
            $bResult = parent::update($document);
        }
        return $bResult;
    }

    public static function delete($iAutoID)
    {
        $aXfInfo = XfInterface::getSolrUpdateInfo($iAutoID);
        if(empty($aXfInfo['iAutoID'])){
            throw new ServiceException('不存在该新房');
        }
        foreach($aXfInfo['solr'] as $document){
            $bResult = parent::delete($document['id']);
        }
        return $bResult;
    }


}
