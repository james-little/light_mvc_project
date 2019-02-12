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
 * Socket Data Processor Helper
 * =======================================================
 * help socket processor to process different kinds of data
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package socket
 * @version 1.0
 **/
namespace lightmvc\socket\processor\helper;

abstract class SocketProcessorHelper
{

    /**
     * filter data and do process
     * @param array | string $data
     * @param array | string $params
     */
    abstract public function process($data, $params);
}
