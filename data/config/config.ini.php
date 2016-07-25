<?php


$config = array();
$config['base_site_url'] 		= '/';
$config['shop_site_url'] 		= '/shop';
$config['cms_site_url'] 		= '/cms';
$config['microshop_site_url'] 	= '/microshop';
$config['circle_site_url'] 		= '/circle';
$config['admin_site_url'] 		= '/admin';
$config['mobile_site_url'] 		= '/mobile';
$config['wap_site_url'] 		= '/wap';
$config['chat_site_url'] 		= '/chat';
$config['node_site_url'] 		= 'http://219.83.164.157:8090';
$config['delivery_site_url']    = '/delivery';
$config['upload_site_url']		= '/data/upload';
$config['resource_site_url']	= '/data/resource';
$config['version'] 		= '201601130001';
$config['setup_date'] 	= '2016-06-08 09:41:08';
$config['gip'] 			= 0;
$config['dbdriver'] 	= 'mysqli';
$config['tablepre']                = 't_';
$config['db']['1']['dbhost']       = getenv('DB_HOST');
$config['db']['1']['dbport']       = getenv('DB_PORT');
$config['db']['1']['dbuser']       = getenv('DB_USER');
$config['db']['1']['dbpwd']        = getenv('DB_PWD');
$config['db']['1']['dbname']       = getenv('DB_NAME');
$config['db']['1']['dbcharset']    = 'UTF-8';
$config['db']['slave']                  = $config['db']['master'];
$config['session_expire'] 	= 3600;
$config['lang_type'] 		= 'zh_cn';
$config['cookie_pre'] 		= '4488_';
$config['thumb']['cut_type'] = 'gd';
$config['thumb']['impath'] = '';
$config['cache']['type'] 			= 'file';
//$config['redis']['prefix']      	= 'nc_';
//$config['redis']['master']['port']     	= 6379;
//$config['redis']['master']['host']     	= '127.0.0.1';
//$config['redis']['master']['pconnect'] 	= 0;
//$config['redis']['slave']      	    = array();
//$config['fullindexer']['open']      = false;
//$config['fullindexer']['appname']   = '33hao';
$config['debug'] 			= getenv('APP_DEBUG');
$config['default_store_id'] = '1';
$config['url_model'] = false;
$config['subdomain_suffix'] = '';
//$config['session_type'] = 'redis';
//$config['session_save_path'] = 'tcp://127.0.0.1:6379';
$config['node_chat'] = true;
//流量记录表数量，为1~10之间的数字，默认为3，数字设置完成后请不要轻易修改，否则可能造成流量统计功能数据错误
$config['flowstat_tablenum'] = 3;
$config['sms']['gwUrl'] = 'http://sdkhttp.eucp.b2m.cn/sdk/SDKService';
$config['sms']['serialNumber'] = '';
$config['sms']['password'] = '';
$config['sms']['sessionKey'] = '';
$config['queue']['open'] = false;
$config['queue']['host'] = '127.0.0.1';
$config['queue']['port'] = 6379;
$config['cache_open'] = false;
$config['delivery_site_url']    = '/delivery';

/****** solr ********/
$config['solr'] = [
    'connections' => [
        'goods' => [
            'master'=>[
                'host' => getenv('SOLR_HOST_GOODS_MASTER'),
                'core' => getenv('SOLR_CORE_GOODS_MASTER'),
                'port' => getenv('SOLR_PORT_GOODS_MASTER'),
            ],
            'slave'=>[
                'host' => getenv('SOLR_HOST_GOODS_SLAVE'),
                'core' => getenv('SOLR_CORE_GOODS_SLAVE'),
                'port' => getenv('SOLR_PORT_GOODS_SLAVE'),
            ],
        ],
    ]
];

return $config;