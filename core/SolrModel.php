<?php
/**
 * Copyright 2016 Koketsu
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * model (Solr)
 * =============================================
 * @author koketsu <jameslittle.private@gmail.com>
 * @package core
 * @version 1.0
 **/
namespace lightmvc\core;

use lightmvc\Application;
use lightmvc\exception\ExceptionCode;
use lightmvc\exception\SolrException;
use lightmvc\info\InfoCollector;
use lightmvc\resource\ResourcePool;
use SolrClient;
use SolrInputDocument;
use SolrQuery;
use SolrQueryResponse;
use SolrUpdateResponse;

class SolrModel
{

    protected $solr;
    protected $table;
    // columns needed by default
    protected $default_column_list;

    protected $multi_value_columns;
    protected $multi_value_spliter = ',';

    /**
     * __construct
     */
    public function __construct()
    {
        $solr_config = $this->_getConfig();
        $host_name   = $solr_config['tables'][$this->table];
        if (empty($solr_config['hosts'][$host_name])) {
            throw new SolrException(
                sprintf(
                    'host matched with table %s not exists: %s',
                    $this->table,
                    $host_name
                ),
                ExceptionCode::SOLR_CONFIG_ERROR
            );
        }
        $solr_config = $solr_config['hosts'][$host_name];
        $solr_config = $this->filterSolrConfig($solr_config);
        $solr        = $this->getSolr($solr_config);
        if (!$solr) {
            throw new SolrException('solr server connection error', ExceptionCode::SOLR_CONFIG_ERROR);
        }
        $this->solr = $solr;
    }
    /**
     * get solr config
     * @throws SolrException
     */
    protected function _getConfig()
    {
        $solr_config = Application::getConfigByKey('solr');
        if (empty($solr_config)) {
            $solr_config = APPLICATION_CONFIG_DIR . APPLICATION_ENVI . DS . 'solr.php';
            if (!is_file($solr_config)) {
                throw new SolrException(
                    'config file not exist: ' . $solr_config,
                    ExceptionCode::SOLR_CONFIG_NOT_EXIST
                );
            }
            $solr_config = include $solr_config;
            Application::setConfig('solr', $solr_config);
        }
        return $solr_config;
    }
    /**
     * filter solr config
     * @param array $config
     * @throws SolrException
     */
    private function filterSolrConfig($config)
    {
        if (empty($config) || empty($config['host'])) {
            throw new SolrException(
                'host empty',
                ExceptionCode::SOLR_CONFIG_ERROR
            );
        }
        $solr_config             = [];
        $solr_config['host']     = $config['host'];
        $solr_config['port']     = empty($config['port']) ? 8080 : $config['port'];
        $solr_config['username'] = empty($config['username']) ? 0 : $config['username'];
        $solr_config['password'] = empty($config['password']) ? '' : $config['password'];
        $solr_config['path']     = empty($config['path']) ? '' : $config['path'];
        $solr_config['timeout']  = empty($config['timeout']) ? 0 : $config['timeout'];
        return $solr_config;
    }
    /**
     * get memcache connection
     * @return SolrClient
     */
    private function getSolr($solr_config)
    {
        if (!extension_loaded('solr')) {
            return null;
        }
        $resource_type = 'solr';
        $resource_pool = ResourcePool::getInstance();
        $resource_key  = $resource_pool->getResourceKey($solr_config);
        $solr          = $resource_pool->getResource($resource_type, $resource_key);
        if ($solr) {
            return $solr;
        }
        $solr_config = filter_array($solr_config, ['host', 'port', 'username', 'password', 'path'], true);
        $solr        = null;
        try {
            $solr = new SolrClient($solr_config);
        } catch (Exception $e) {
            throw new SolrException(
                'solr client error: ' . $e->getMessage(),
                ExceptionCode::SOLR_CONNECTION_ERROR
            );
        }
        $resource_pool->registerResource($resource_type, $resource_key, $solr);
        return $solr;
    }
    /**
     * query by 'AND', the result would be only the first row
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryByAnd($and_list, $not_list = null, $column_list = null)
    {
        $query_param_list = $this->createQueryParamsByAnd($and_list, $not_list, null, 1, 0);
        if (!$query_param_list) {
            return [];
        }
        return $this->query($query_param_list, $column_list);
    }
    /**
     * query by 'AND'. return all the rows match the condition
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function queryAllByAnd(
        $and_list,
        $not_list = null,
        $column_list = null,
        $order = null,
        $limit = null,
        $offset = null
    ) {
        $query_param_list = $this->createQueryParamsByAnd($and_list, $not_list, $order, $limit, $offset);
        if (!$query_param_list) {
            return [];
        }
        return $this->query($query_param_list, $column_list);
    }
    /**
     * query all rows by solr query
     * @param string $query
     * @param array $column_list
     * @param string $order
     * @param string $limit
     * @param int $offset
     * @return array
     */
    public function queryAll($query, $column_list = null, $order = null, $limit = null, $offset = null)
    {
        if (empty($query)) {
            return [];
        }

        $query_param_list          = [];
        $query_param_list['query'] = $query;
        $query_param_list['sort']  = $order ? $order : '';
        $query_param_list['start'] = empty($offset) ? 0 : $offset;
        $query_param_list['rows']  = empty($limit) ? 0 : $limit;
        return $this->query($query_param_list, $column_list);
    }
    /**
     * get the first row of the result set
     * @param string $query
     * @param array $column_list
     * @param string $order
     * @param int $offset
     * @return  array
     */
    public function queryRow($query, $column_list = null, $order = null, $offset = null)
    {
        if (empty($query)) {
            return [];
        }

        $query_param_list          = [];
        $query_param_list['query'] = $query;
        $query_param_list['sort']  = $order ? $order : '';
        $query_param_list['start'] = empty($offset) ? 0 : $offset;
        $query_param_list['rows']  = 1;
        return $this->query($query_param_list, $column_list);
    }
    /**
     * set the isForce flag true if you want to insert a record with nothing
     * @param array $data_map
     * @return int: affected_rows
     */
    public function insert($data_map)
    {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }

        return $this->updateSolrDocument($data_map);
    }
    /**
     * update solr data
     * @param array $data_map
     * @param array $where_condition_list
     *
     *     Quite different with mysql model, you can't use like > , <, <=, >= here
     *     only you can use "="
     *
     *     Example:
     *         array (
     *             'uid'   => 1,
     *             'email' => 'abc@def.com'
     *         )
     * @return int: affected_rows
     */
    public function update($data_map, array $where_condition_list = [])
    {
        $data_map = $this->filterInputDataMap($data_map);
        if (empty($data_map)) {
            return 0;
        }

        $data_map = array_merge($data_map, $where_condition_list);
        return $this->updateSolrDocument($data_map);
    }
    /**
     * update solr document
     * multi-value column: 1,2,3,4,,5,6
     * @param array $data_map
     * @return int: affected_rows
     */
    protected function updateSolrDocument($data_map)
    {
        if (empty($data_map)) {
            return 0;
        }
        $doc = new SolrInputDocument();
        foreach ($data_map as $key => $value) {
            if (empty($this->multi_value_columns) || !isset($this->multi_value_columns[$key])) {
                $doc->addField($key, $value);
            } else {
                foreach (explode($this->multi_value_spliter, $value) as $single_val) {
                    $doc->addField($key, $single_val);
                }
            }
        }
        $update_response = null;
        try {
            $this->solr->addDocument($doc, true);
            $update_response = $this->solr->commit();
            $this->solr->optimize();
        } catch (Exception $e) {
            __add_info(
                'update to solr failed: ' . $e->getMessage(),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            return 0;
        }
        $update_response = $this->parseResponse($update_response);
        if (empty($update_response)) {
            return 0;
        }

        return intval($update_response['status_code'] == '200' && $update_response['success'] == '1');
    }
    /**
     * delete by query
     * @param string $query
     * @return int: affected_rows
     */
    public function deleteByQuery($query)
    {
        if (empty($query)) {
            return 0;
        }

        $update_response = null;
        try {
            $this->solr->deleteByQuery($query);
            $update_response = $this->solr->commit();
        } catch (Exception $e) {
            __add_info(
                'solr delete failed: ' . $e->getMessage(),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            return 0;
        }
        $update_response = $this->parseResponse($update_response);
        if (empty($update_response)) {
            return false;
        }

        return intval($update_response['status_code'] == '200' && $update_response['success'] == '1');
    }
    /**
     * delete by id list
     * @param array $id_list
     * @return int: affected_rows
     */
    public function deleteByIds($id_list)
    {
        if (empty($id_list)) {
            return 0;
        }

        $update_response = null;
        try {
            $this->solr->deleteByIds($id_list);
            $update_response = $this->solr->commit();
        } catch (Exception $e) {
            __add_info(
                'solr delete failed: ' . $e->getMessage(),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            return 0;
        }
        $update_response = $this->parseResponse($update_response);
        if (empty($update_response)) {
            return 0;
        }

        if ($update_response['status_code'] == '200' && $update_response['success'] == '1') {
            return count($id_list);
        }
        return 0;
    }
    /**
     * query
     * @param array $query_param_list
     * @param array $column_list
     * @return array
     */
    private function query($query_param_list, $column_list = null)
    {
        if (!$this->solr) {
            return [];
        }
        if (empty($query_param_list['query'])) {
            return [];
        }
        $this->solr->setResponseWriter('json');
        $query = new SolrQuery();
        $query->setQuery($query_param_list['query']);
        $query->set('objectClassName', 'SolrClass');
        $query->set('objectPropertiesStorageMode', 1);
        if (!empty($column_list)) {
            foreach ($column_list as $column) {
                $query->addField($column);
            }
        }
        if (!empty($query_param_list['sort'])) {
            preg_match_all('/([^ ]+) *(asc|desc|random)/i', $query_param_list['sort'], $matches, PREG_SET_ORDER);
            if (!empty($matches)) {
                foreach ($matches as $key => $match) {
                    if (strtolower($match[2]) == 'asc') {
                        $query->addSortField($match[1], SolrQuery::ORDER_ASC);
                    } elseif (strtolower($match[2]) == 'desc') {
                        $query->addSortField($match[1], SolrQuery::ORDER_DESC);
                    } elseif (strtolower($match[2]) == 'random') {
                        $query->addSortField('random_' . mt_rand(1, PHP_INT_MAX));
                    }
                }
            }
        }
        $query->setStart($query_param_list['start']);
        if ($query_param_list['rows'] > 0) {
            $query->setRows($query_param_list['rows']);
        }
        __add_info(
            'query: ' . var_export($query_param_list, true),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        try {
            $query_response = $this->solr->query($query);
        } catch (Exception $e) {
            __add_info(
                'query failed to solr: ' . $e->getMessage(),
                InfoCollector::TYPE_EXCEPTION,
                InfoCollector::LEVEL_DEBUG
            );
            return [];
        }
        $query_response = $this->parseResponse($query_response);
        if (empty($query_response)) {
            return [];
        }

        $query_response = $query_response['data'];
        if ($query_param_list['rows'] == 1) {
            return current($query_response);
        }
        return $query_response;
    }
    /**
     * create solr query
     * @param array $and_list
     * @param array $not_list
     * @param array $column_list
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function createQueryParamsByAnd(
        $and_list,
        $not_list = null,
        $order = null,
        $limit = null,
        $offset = null
    ) {
        $is_and_list_not_null     = is_array($and_list) && count($and_list) ? true : false;
        $is_not_and_list_not_null = is_array($not_list) && count($not_list) ? true : false;
        if (!$is_and_list_not_null && !$is_not_and_list_not_null) {
            return false;
        }
        $query  = '';
        $params = [];
        if ($is_and_list_not_null) {
            foreach ($and_list as $column => $value) {
                $query .= " AND {$column}:{$value}";
            }
        }
        if ($is_not_and_list_not_null) {
            foreach ($not_list as $column => $value) {
                $query .= " AND NOT {$column}:{$value}";
            }
        }
        if ($query) {
            $query = substr($query, 5);
        }

        $query_param['query'] = $query;
        $query_param['sort']  = $order ? $order : null;
        $query_param['start'] = empty($offset) ? 0 : $offset;
        $query_param['rows']  = empty($limit) ? 0 : $limit;
        return $query_param;
    }
    /**
     * filter input data map
     * @param array $data_map
     */
    public function filterInputDataMap($data_map)
    {
        if (empty($this->default_column_list)) {
            return $data_map;
        }

        return filter_array($data_map, $this->default_column_list);
    }
    /**
     * parse solr response
     * @param SolrResponse $response
     * @return array
     */
    protected function parseResponse($response)
    {
        if (empty($response)) {
            return [];
        }

        $response_array                = [];
        $response_array['response']    = $response->getRawResponse();
        $response_array['status_code'] = $response->getHttpStatus();
        $response_array['success']     = $response->success();
        __add_info(
            'request_url: ' . $response->getRequestUrl(),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        __add_info(
            sprintf(
                'response from solr[%s][%s]: %s',
                get_class($response),
                $response_array['success'],
                $response_array['response']
            ),
            InfoCollector::TYPE_LOGIC,
            InfoCollector::LEVEL_DEBUG
        );
        if ($response instanceof SolrUpdateResponse) {
        } elseif ($response instanceof SolrQueryResponse) {
            $response_array['data'] = [];
            $response               = $response->getResponse();
            foreach ($response['response']['docs'] as $key => $doc) {
                $response_array['data'][$key] = $doc;
            }
        }
        return $response_array;
    }
}
