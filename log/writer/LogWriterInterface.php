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
 * log writer interface
 * ====================================
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @package log\writer
 * @version 1.0
 **/
namespace lightmvc\log\writer;

interface LogWriterInterface
{

    /**
     * write log
     * @param string $message
     * @param int $level
     */
    public function write($message, $level);

    /**
     * close adapter
     */
    public function close();
}
