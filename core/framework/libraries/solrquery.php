<?php
// namespace App\Model;

// use Exception;
// use Respect\Validation\ExceptionIterator;
// use ServiceException;
// use Log;

/**
 * 查询solr语法生成器
 *
 * Class SolrQuery
 * @package App\Model
 */
class SolrQuery
{
    private $_query = [];
    private $_table = '';
    protected $_config;
    const GROUP = 'group';

    const PHRASE_FIELD = 'pf';
    const MIN_MATCH = 'mm';
    const QUERY_FIELD = 'qf';
    const DEF_TYPE = 'defType';
    const STOP_WORDS = 'stopwords';
    const LOWER_CASE_OPERATORS = 'lowercaseOperators';
    const GROUP_NGROUP = 'group.ngroups';
    const GROUP_QUERY = 'group.query';
    const GROUP_FIELD = 'group.field';
    const GROUP_LIMIT = 'group.limit';
    const GROUP_OFFSET = 'group.offset';
    const GROUP_SORT = 'group.sort';

    const QUERY = 'q';
    const FILTER_QUERY = 'fq';
    const FIELD = 'fl';
    const SORT = 'sort';
    const ROWS = 'rows';
    const LIMIT = 'rows';
    const START = 'start';
    const OFFSET = 'start';
    const WRITE_TYPE = 'wt';

    public function __construct($connection = null)
    {
        global $config;
        $this->_config = $config['solr']['connections'][$connection];
    }

    /**
     * 生成Solr服务器基本地址
     * @param string $iType ('master'-主服务器;'slave'-从服务器)
     * @return string
     */
    public function getServerUrl($sType = 'master')
    {
        return 'http://' . $this->_config[$sType]['host'] .
        (isset($this->_config[$sType]['port']) ? ':' . $this->_config[$sType]['port'] : '') . '/solr/' . $this->_config[$sType]['core'];
    }

    /**
     * 添加搜索过滤器
     *
     * @param $filter
     * @return $this
     */
    public function addFilterQuery($filter)
    {
        $this->_query['fq'][] = $filter;
        return $this;
    }

    /**
     * 选择solr表
     *
     * @param $table
     * @return $this
     */
    public function from($table)
    {
        $this->_table = $table;
        return $this;
    }

    /**
     * 选择查询字段
     *
     * @param $fileds
     * @return $this
     */
    public function select($fileds)
    {
        $this->fileds($fileds);
        return $this;
    }

    /**
     * 设置查询字段
     *
     * @param $fileds
     * @return $this
     */
    public function fileds($fileds)
    {
        if (!is_array($fileds)) {
            $fileds = explode(',', $fileds);
        }
        $this->_query[self::FIELD] = $fileds;
        return $this;
    }

    /**
     * 查询条件
     *
     * @param $where
     * @return $this
     */
    public function where($where)
    {
        $this->_query[self::QUERY] = $where;
        return $this;
    }

    /**
     * 过滤查询
     * @param $filter
     */
    public function filter($filter)
    {
        $this->_query[self::FILTER_QUERY] = $filter;
        return $this;
    }

    /**
     * @param $filter
     */
    public function addFilter($filter)
    {
        $this->_query[self::FILTER_QUERY][] = $filter;
        return $this;
    }

    public function addParam($param, $value)
    {
        $this->_query[$param] = $value;
        return $this;
    }

    /**
     * 限制查询个数
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 20)
    {
        $this->_query[self::ROWS] = (int)$limit;
        return $this;
    }

    /**
     * 分页显示记录数
     *
     * @param int $offset
     * @return $this
     */
    public function offset($offset = 0)
    {
        $this->_query[self::START] = (int)$offset;
        return $this;
    }

    /**
     * 非分组查询排序
     *
     * @param $orderBy
     * @return $this
     */
    public function orderby($orderBy)
    {
        if (is_array($orderBy)) {
            $aOrderBy = [];
            foreach ($orderBy as $key => $value) {
                $aOrderBy[] = "$key $value";
            }
            $orderBy = implode(',', $aOrderBy);
        }
        $this->_query[self::SORT] = $orderBy;
        return $this;
    }

    /**
     * 设置所有的查询相关参数
     *
     * @param $param
     * @param $value
     * @return $this
     */
    public function setParam($param, $value)
    {
        $this->_query[$param] = $value;
        return $this;
    }

    /**
     * 是否开启分组查询
     *
     * @param $open
     * @return $this
     */
    public function setGroup($open)
    {
        $this->_query[self::GROUP] = $open ? true : false;
        return $this;
    }

    /**
     * 分组分类查询
     *
     * @param $groupSort
     * @return $this
     */
    public function groupBy($groupSort)
    {
        $this->_query[self::GROUP_SORT] = $groupSort;
        return $this;
    }

    public function groupQuery($groupQuery)
    {
        $this->_query[self::GROUP_QUERY] = $groupQuery;
        return $this;
    }

    public function groupSort($groupSort)
    {
        $this->_query[self::GROUP_SORT] = $groupSort;
        return $this;
    }

    /**
     * 分组分页记录数
     *
     * @param $groupLimit
     * @return $this
     */
    public function groupLimit($groupLimit)
    {
        $this->_query[self::GROUP_LIMIT] = $groupLimit;
        return $this;
    }

    /**
     * 分组分页数
     * @param $groupOffset
     * @return $this
     */
    public function groupOffset($groupOffset)
    {
        $this->_query[self::GROUP_OFFSET] = $groupOffset;
        return $this;
    }

    /**
     * 分组查询字段
     * @param $groupFiled
     * @return $this
     */
    public function groupFiled($groupFiled)
    {
        $this->_query[self::GROUP_FIELD] = $groupFiled;
        return $this;
    }

    /**
     * 通过get方式进行请求
     * @param $url
     * @return mixed
     */
    private function get($url)
    {
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, '30');//超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json;charset=UTF-8']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection:Keep-Alive']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['P3P:CP=CAO PSA OUR']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Pragma:no-cache']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cache-Control:no-cache,no-store']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expires:Thu, 01 Jan 1970 00:00:00 GMT']);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Proxy-Connection:Keep-Alive']);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        return $output;
    }

    /**
     * post json方式提交请求
     * @param $url
     * @param $data
     * @return mixed
     */
    private function post($url, $data)
    {
        //      dd($url);
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_TIMEOUT, '30');//超时时间
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Keep-Alive: 300', 'Connection: keep-alive'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        ob_start();
        curl_exec($ch);
        $contents = ob_get_contents();
        ob_end_clean();
        curl_close($ch);
        //      dd($contents);
        return $contents;
    }

    /**
     * 生成查询结果
     * @return mixed
     */
    public function find()
    {
        $server = $this->getServerUrl('slave') . '/select';
        $query = [];
        $query[self::START] = $this->_query[self::START];
        $query[self::ROWS] = $this->_query[self::ROWS];
        if (!empty($this->_query[self::SORT])) {
            if (is_array($this->_query[self::SORT])) {
                $sort = [];
                foreach ($this->_query[self::SORT] as $key => $value) {
                    $sort[] = "$key $value";
                }
                $sort = implode(',', $sort);
            } else {
                $sort = $this->_query[self::SORT];
            }
            $query[self::SORT] = $sort;
        }
        if (!empty($this->_query[self::QUERY])) {
            if (is_array($this->_query[self::QUERY])) {
                $where = [];
                foreach ($this->_query[self::QUERY] as $key => $value) {
                    $where[] = "$key:$value";
                }
                $where = implode(' AND ', $where);
            } else {
                $where = $this->_query[self::QUERY];
            }
            //          dd($where);
            $query[self::QUERY] = $where;
        } else {
            $query[self::QUERY] = '*:*';
        }
        if (!empty($this->_query['sfield'])) {
            $query['sfield'] = $this->_query['sfield'];
        }
        if (!empty($this->_query['pt'])) {
            $query['pt'] = $this->_query['pt'];
        }
        if (!empty($this->_query['d'])) {
            $query['d'] = $this->_query['d'];
        }
        if (!empty($this->_query[self::FIELD])) {
            if (is_array($this->_query[self::FIELD])) {
                $query[self::FIELD] = implode(',', $this->_query[self::FIELD]);
            } else {
                $query[self::FIELD] = $this->_query[self::FIELD];
            }
        }
        if (!empty($this->_query[self::QUERY_FIELD])) {
            if (is_array($this->_query[self::QUERY_FIELD])) {
                $query[self::QUERY_FIELD] = implode(' ', $this->_query[self::QUERY_FIELD]);
            } else {
                $query[self::QUERY_FIELD] = $this->_query[self::QUERY_FIELD];
            }
        }


        if (!empty($this->_query[self::MIN_MATCH])) {
            $query[self::MIN_MATCH] = $this->_query[self::MIN_MATCH];
        }

        if (!empty($this->_query[self::PHRASE_FIELD])) {
            if (is_array($this->_query[self::PHRASE_FIELD])) {
                $query[self::PHRASE_FIELD] = implode(' ', $this->_query[self::PHRASE_FIELD]);
            } else {
                $query[self::PHRASE_FIELD] = $this->_query[self::PHRASE_FIELD];
            }
        }

        if (!empty($this->_query[self::DEF_TYPE])) {
            $query[self::DEF_TYPE] = $this->_query[self::DEF_TYPE];
        }

        if (!empty($this->_query[self::STOP_WORDS])) {
            $query[self::STOP_WORDS] = $this->_query[self::STOP_WORDS] ? 'true' : 'false';
        }

        if (!empty($this->_query[self::LOWER_CASE_OPERATORS])) {
            $query[self::LOWER_CASE_OPERATORS] = $this->_query[self::LOWER_CASE_OPERATORS] ? 'true' : 'false';
        }

        $query[self::WRITE_TYPE] = 'json';
        if (isset($this->_query[self::GROUP]) && $this->_query[self::GROUP]) {
            $group = [
                self::GROUP => 'true',
                self::GROUP_NGROUP => 'true',
                self::GROUP_QUERY => !empty($this->_query[self::GROUP_QUERY]) ? $this->_query[self::GROUP_QUERY] : null,
                self::GROUP_FIELD => !empty($this->_query[self::GROUP_FIELD]) ? $this->_query[self::GROUP_FIELD] : null,
                self::GROUP_SORT => !empty($this->_query[self::GROUP_SORT]) ? $this->_query[self::GROUP_SORT] : null,
                self::GROUP_LIMIT => isset($this->_query[self::GROUP_LIMIT]) ? $this->_query[self::GROUP_LIMIT] : null,
                self::GROUP_OFFSET => isset($this->_query[self::GROUP_OFFSET]) ? $this->_query[self::GROUP_OFFSET] : null,
            ];
            $query = array_merge(array_filter($group, function ($value) {
                return !is_null($value);
            }), $query);
        }
        // dd($query);
        // 拼装查询url接口
        $server .= '?' . http_build_query($query);
        // 过滤查询
        if (!empty($this->_query[self::FILTER_QUERY])) {
            if (is_array($this->_query[self::FILTER_QUERY])) {
                $filter_query = [];
                foreach ($this->_query[self::FILTER_QUERY] as $key => $value) {
                    $filter_query[] = "$key:$value";
                }
            } else {
                $filter_query = [$this->_query[self::FILTER_QUERY]];
            }
            foreach ($filter_query as $key => $value) {
                $server .= '&' . self::FILTER_QUERY . '=' . $value;
            }
        }
        // dd($server);
        $return = $this->get($server);
        // Log::info('solr find url:' . $server);
        // Log::info('solr return:' . $return);
        return $return;
    }

    /**
     * 返回所有的列表数据结果
     *
     * @return array
     */
    public function all()
    {
        $data = $this->find();
        if (isset($data['responseHeader']['status']) && 0 == $data['responseHeader']['status']) {
            return isset($data['response']['docs']) ? $data['response']['docs'] : (isset($data['response']['grouped']) ? $data['response']['grouped'] : []);
        } else {
            return [];
        }
    }

    /**
     * 删除结果
     * @return mixed
     */
    public function delete()
    {
        $server = $this->getServerUrl('master') . '/update?wt=json&stream.body=<delete><query>%s</query></delete>&stream.contentType=text/xml;charset=utf-8&commit=true';
        if (!empty($this->_query[self::QUERY])) {
            if (is_array($this->_query[self::QUERY])) {
                $where = [];
                foreach ($this->_query[self::QUERY] as $key => $value) {
                    $where[] = "$key:$value";
                }
                $where = implode(' AND ', $where);
            } else {
                $where = $this->_query[self::QUERY];
            }
            $query = $where;
        } else {
            throw new Exception('删除时制定条件');
        }
        // 拼装查询url接口
        $server = sprintf($server, $query);
        Log::debug($server);
        return $this->get($server);
    }

    /**
     * 更新或者添加文档
     *
     * @param $data
     * @return mixed
     */
    public function update($data)
    {
        $server = $this->getServerUrl('master') . '/update/?wt=json';
        $doc = [
            'add' => [
                'doc' => $data,
                'overwrite' => true,
                'commitWithin' => 1000,
            ]
        ];
        //      dd($server);
        return $this->post($server, json_encode($doc));
    }

}