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



// 收款管理
class Member_paymeet extends \Phpcmf\Table
{

    public function __construct(...$params)
    {
        parent::__construct(...$params);
        // 支持附表存储
        $this->is_data = 0;
        // 模板前缀(避免混淆)
        $this->my_field = array(
            'title' => array(
                'ismain' => 1,
                'name' => dr_lang('关键字'),
                'fieldname' => 'title',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
            'username' => array(
                'ismain' => 1,
                'name' => dr_lang('付款账户'),
                'fieldname' => 'username',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
            'tousername' => array(
                'ismain' => 1,
                'name' => dr_lang('收款账户'),
                'fieldname' => 'tousername',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
            'uid' => array(
                'ismain' => 1,
                'name' => dr_lang('付款uid'),
                'fieldname' => 'uid',
                'fieldtype' => 'Text',
                'setting' => array(
                    'option' => array(
                        'width' => 200,
                    ),
                )
            ),
        );
        // 表单显示名称
        $this->name = dr_lang('上门收款');
        // 初始化数据表
        $this->_init([
            'table' => 'member_paylog',
            'field' => $this->my_field,
            'sys_field' => [],
            'order_by' => 'inputtime desc',
            'date_field' => 'inputtime',
            'where_list' => '`type`="meet"',
            'list_field' => [],
        ]);
        \Phpcmf\Service::V()->assign([
            'menu' => \Phpcmf\Service::M('auth')->_admin_menu(
                [
                    '上门收款' => [\Phpcmf\Service::L('Router')->class.'/index', 'fa fa-user'],
                    '详情' => ['hide:'.\Phpcmf\Service::L('Router')->class.'/edit', 'fa fa-edit'],
                    'help' => [ 596 ],
                ]
            ),
            'field' => $this->my_field,
            'is_meet' => 1,
        ]);
    }

    // index
    public function index() {
        $this->_List();
        \Phpcmf\Service::V()->display('member_paylog_list.html');
    }

    // edit
    public function edit() {
        list($tpl, $data) = $this->_Post((int)\Phpcmf\Service::L('Input')->get('id'), [], 1);
        !$data && $this->_admin_msg(0, dr_lang('支付记录不存在'));
        \Phpcmf\Service::V()->display('member_paylog_edit.html');
    }
    
    // 删除
    public function del() {
        $this->_Del(\Phpcmf\Service::L('Input')->get_post_ids());
    }

    /**
     * 保存内容
     * $id      内容id,新增为0
     * $data    提交内容数组,留空为自动获取
     * $old     老数据
     * $func    格式化提交的数据 提交前
     * $func    格式化提交的数据 保存后
     * */
    protected function _Save($id = 0, $data = [], $old = [], $before = null, $after = null) {

        $post = \Phpcmf\Service::L('Input')->post('post');
        if (!isset($post['verify'])) {
            return dr_return_data(0, dr_lang('审核状态必须选择'), ['field' => 'verify']);
        } elseif (!$post['note']) {
            return dr_return_data(0, dr_lang('审核备注必须填写'), ['field' => 'note']);
        }

        // 更新提醒
        \Phpcmf\Service::M('member')->todo_admin_notice('member_paymeet/edit:id/'.$id, 0);

        // 收到款
        if ($post['verify']) {
            \Phpcmf\Service::M('member')->notice($old['uid'], 5, '流水号('.$id.') 上门付款成功',\Phpcmf\Service::L('Router')->member_url('paylog/show', ['id'=>$id]));
            return \Phpcmf\Service::M('Pay')->paysuccess('fc-'.$id, $post['note']);
        } else {
            \Phpcmf\Service::M('member')->notice($old['uid'], 5, '流水号('.$id.') 上门付款失败',\Phpcmf\Service::L('Router')->member_url('paylog/show', ['id'=>$id]));
            \Phpcmf\Service::M()->table('member_paylog')->update($id, [
                'result' => $post['note'],
                'status' => 0,
            ]);
        }

        return dr_return_data($id, 'ok');
    }
    
}
