<?php namespace Phpcmf\Controllers\Admin;

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



// 用户组
class Member_group extends \Phpcmf\Table
{

    private $type;

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 表单验证配置
        $this->form_rule = [
            'name' => [
                'name' => '名称',
                'rule' => [
                    'empty' => dr_lang('名称不能为空')
                ],
                'filter' => [],
                'length' => '200'
            ],
        ];
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户组管理' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-users'],
                    '添加组' => [\Phpcmf\Service::L('Router')->class.'/add', 'fa fa-plus'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    '等级制度' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/level_index', 'fa fa-list-ol'],
                    '添加等级' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/level_add', 'fa fa-plus'],
                    '修改等级' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/level_edit', 'fa fa-edit'],
                    'help' => [515]
                ]
            ),
        ]);
    }

    private function _init_group() {
        $this->type = 1;
        // 表单显示名称
        $this->name = dr_lang('用户组');
        $this->tpl_prefix = 'member_group_';
        // 初始化数据表
        $this->_init([
            'table' => 'member_group',
            'sys_field' => [],
            'order_by' => 'displayorder asc,id asc',
            'list_field' => [],
        ]);
    }

    private function _init_level() {
        $this->type = 0;
        // 表单显示名称
        $this->name = dr_lang('用户组等级');
        $this->tpl_prefix = 'member_level_';
        // 初始化数据表
        $this->_init([
            'table' => 'member_level',
            'sys_field' => [],
            'order_by' => '`value` asc',
            'list_field' => [],
            'where_list' => 'gid='.(int)\Phpcmf\Service::L('Input')->get('gid'),
        ]);
    }

    // 管理
    public function index() {
        $this->_init_group();
        list($tpl) = $this->_List(null, -1);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 添加
    public function add() {
        $this->_init_group();
        list($tpl) = $this->_Post(0);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 修改
    public function edit() {
        $this->_init_group();
        list($tpl) = $this->_Post(intval(\Phpcmf\Service::L('Input')->get('id')));
        $page = intval(\Phpcmf\Service::L('Input')->get('page'));
        \Phpcmf\Service::V()->assign([
            'page' => $page,
            'form' => dr_form_hidden(['page' => $page]),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 排序
    public function order_edit() {
        $this->_init_group();
        $this->_Display_Order(intval(\Phpcmf\Service::L('Input')->get('id')), intval(\Phpcmf\Service::L('Input')->get('value')));
    }

    // 允许注册
    public function register_edit() {
        $this->_init_group();
        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $data = $this->_Data($id);
        !$data && $this->_json(0, dr_lang('数据#%s不存在', $id));
        $value = $data['register'] ? 0 : 1;
        \Phpcmf\Service::M()->table('member_group')->save($id, 'register', $value);
        exit($this->_json(1, dr_lang('操作成功'), ['value' => $value]));
    }

    // 允许申请
    public function apply_edit() {
        $this->_init_group();
        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $data = $this->_Data($id);
        !$data && $this->_json(0, dr_lang('数据#%s不存在', $id));
        $value = $data['apply'] ? 0 : 1;
        \Phpcmf\Service::M()->table('member_group')->save($id, 'apply', $value);
        exit($this->_json(1, dr_lang('操作成功'), ['value' => $value]));
    }

    // 删除
    public function del() {
        $this->_init_group();
        $ids = \Phpcmf\Service::L('Input')->get_post_ids();
        $this->_Del(
            $ids,
            null,
            null,
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // 等级管理
    public function level_index() {
        $this->_init_level();
        list($tpl) = $this->_List([], -1);
        \Phpcmf\Service::V()->assign([
            'gid' => intval($_GET['gid']),
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '用户组管理' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-users'],
                    '修改' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    '等级制度' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/level_index', 'fa fa-list-ol'],
                    '添加等级' => [\Phpcmf\Service::L('Router')->class.'/level_add{gid='.intval($_GET['gid']).'}', 'fa fa-plus'],
                ]
            ),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 添加等级
    public function level_add() {
        $this->_init_level();
        $gid = intval($_GET['gid']);
        list($tpl) = $this->_Post(0);
        \Phpcmf\Service::V()->assign([
            'gid' => $gid,
            'form' => dr_form_hidden(['gid' => $gid]),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 修改等级
    public function level_edit() {
        $this->_init_level();
        list($tpl, $data) = $this->_Post(intval(\Phpcmf\Service::L('Input')->get('id')));
        \Phpcmf\Service::V()->assign([
            'form' => dr_form_hidden(['gid' => intval($data['gid'])]),
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 允许申请等级
    public function apply_level_edit() {
        $this->_init_level();
        $id = (int)\Phpcmf\Service::L('Input')->get('id');
        $data = $this->_Data($id);
        !$data && $this->_json(0, dr_lang('数据#%s不存在', $id));
        $value = $data['apply'] ? 0 : 1;
        \Phpcmf\Service::M()->table('member_level')->save($id, 'apply', $value);
        exit($this->_json(1, dr_lang('操作成功'), ['value' => $value]));
    }

    // 删除等级
    public function level_del() {
        $this->_init_level();
        $this->_Del(
            \Phpcmf\Service::L('Input')->get_post_ids(),
            null,
            null,
            \Phpcmf\Service::M()->dbprefix($this->init['table'])
        );
    }

    // 保存
    protected function _Save($id = 0, $data = [], $old = [],  $func = null) {
        return parent::_Save($id, $data, $old, function($id, $data, $old){
            if ($this->type) {
                $data['setting'] = dr_array2string(\Phpcmf\Service::L('Input')->post('setting'));
                $data['price'] = floatval($data['price']);
                $data['days'] = intval($data['days']);
                !$id && $data['displayorder'] = 0;
            } else {
                $data['gid'] = (int)\Phpcmf\Service::L('Input')->post('gid');
                if (!$data['gid']) {
                    dr_return_data(0, dr_lang('所属用户组id不存在'), $data);
                }
                $data['stars'] = intval($data['stars']);
                $data['value'] = intval($data['value']);
                $data['apply'] = 1;
            }
            return dr_return_data(1, null, $data);
        });
    }

    /**
     * 获取内容
     * $id      内容id,新增为0
     * */
    protected function _Data($id = 0) {
        $data = parent::_Data($id);
        if ($this->type) {
            $data['setting'] = dr_string2array($data['setting']);
        }
        return $data;
    }


}
