<?php namespace Phpcmf\Model;

/* *
 *
 * Copyright [2019] [李睿]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * http://www.tianruixinxi.com
 *
 * 本文件是框架系统文件，二次开发时不建议修改本文件
 *
 * */


// 模型类

class Verify extends \Phpcmf\Model
{

    // 缓存
    public function cache() {

        $data = $this->table('admin_verify')->getAll();
        $cache = [];
        if ($data) {
            foreach ($data as $t) {
                $t['value'] = dr_string2array($t['verify']);
                unset($t['verify']);
                $cache[$t['id']] = $t;
            }
        }

        \Phpcmf\Service::L('cache')->set_file('verify', $cache);
        return;
    }
    
}