<?php namespace Phpcmf\Home;

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




// 内容网站表单操作类 基于 Ftable
class Form extends \Phpcmf\Table
{
    public $cid; // 内容id
    public $form; // 表单信息

    public function __construct(...$params) {
        parent::__construct(...$params);
        // 判断表单是否操作
        $cache = \Phpcmf\Service::L('cache')->get('form-'.SITE_ID);
        $this->form = $cache[\Phpcmf\Service::L('Router')->class];
        if (!$this->form) {
            $this->_msg(0, dr_lang('网站表单【%s】不存在',\Phpcmf\Service::L('Router')->class));
            exit;
        }
        // 支持附表存储
        $this->is_data = 1;
        // 模板前缀(避免混淆)
        $this->tpl_name = $this->form['table'];
        $this->tpl_prefix = 'form_';
        // 初始化数据表
        $this->_init([
            'table' => SITE_ID.'_form_'.\Phpcmf\Service::L('Router')->class,
            'field' => $this->form['field'],
            'show_field' => 'title',
        ]);
        // 写入模板
        \Phpcmf\Service::V()->assign([
            'form_name' => $this->form['name'],
            'form_table' => $this->form['table'],
        ]);
    }

    // ========================

    // 内容列表
    protected function _Home_List() {


        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            // 启用页面缓存
            $this->cachePage(SYS_CACHE_PAGE * 3600);
        }

        // 无权限访问表单
        if (!dr_member_auth(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['show'])
        ) {
            $this->_msg(0, dr_lang('您的用户组无权限访问表单'));
            return;
        }

        // seo
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->form_list(
            $this->form,
            max(1, (int)\Phpcmf\Service::L('Input')->get('page'))
        ));

        \Phpcmf\Service::V()->assign([
            'urlrule' =>\Phpcmf\Service::L('Router')->form_list_url($this->form['table'], '[page]'),
        ]);
        \Phpcmf\Service::V()->display($this->_tpl_filename('list'));
    }

    // 添加内容
    protected function _Home_Post() {

        // 无权限访问表单
        if (!dr_member_auth(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['add'])
        ) {
            $this->_msg(0, dr_lang('您的用户组无发布权限'));
            return;
        }

        // 判断会员权限
        $this->member && $this->_member_option(0);

        // 是否有验证码
        $this->is_post_code = dr_member_auth(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['code']
        );

        list($tpl) = $this->_Post(0);

        // seo
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->form_post($this->form));

        \Phpcmf\Service::V()->assign([
            'form' =>  dr_form_hidden(),
            'rt_url' => $this->form['setting']['rt_url'] ? $this->form['setting']['rt_url'] : dr_now_url(),
            'is_post_code' => $this->is_post_code,
        ]);
        \Phpcmf\Service::V()->display($tpl);
    }

    // 显示内容
    protected function _Home_Show() {


        if (SYS_CACHE && SYS_CACHE_PAGE && !defined('SC_HTML_FILE')) {
            // 启用页面缓存
            $this->cachePage(SYS_CACHE_PAGE * 3600);
        }

        // 无权限访问表单
        if (!dr_member_auth(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['show'])
        ) {
            $this->_msg(0, dr_lang('您的用户组无权限访问表单'));
            return;
        }

        $id = intval(\Phpcmf\Service::L('Input')->get('id'));
        $name = 'from_'.$this->form['table'].'_show_id_'.$id;
        $cache = \Phpcmf\Service::L('cache')->init()->get($name);
        if (!$cache) {
            list($tpl, $data) = $this->_Show($id);
            !$data && $this->_msg(0, dr_lang('网站表单内容不存在'));
            $data = $this->_Call_Show($data);
            $cache = [
                $tpl,
                $data
            ];
            // 缓存结果
            $data['uid'] != $this->uid && \Phpcmf\Service::L('cache')->init()->save($name, $cache, SYS_CACHE_SHOW * 3600);
        } else {
            list($tpl, $data) = $cache;
        }

        \Phpcmf\Service::V()->assign($data);

        // seo
        \Phpcmf\Service::V()->assign(\Phpcmf\Service::L('Seo')->form_show(
            $this->form,
            $data
        ));

        \Phpcmf\Service::V()->display($tpl);
    }


    // ===========================

    // 格式化保存数据 保存之前
    protected function _Format_Data($id, $data, $old) {

        // 验证登录权限
        !$this->uid && $this->_json(0, dr_lang('请登录之后再提交'));

        // 判断日发布量
        $day_post = $this->_member_value(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['day_post']
        );
        if ($this->uid && $day_post
            && \Phpcmf\Service::M()->db
                ->table($this->init['table'])
                ->where('uid', $this->uid)
                ->where('DATEDIFF(from_unixtime(inputtime),now())=0')
                ->countAllResults() >= $day_post) {
            $this->_json(0, dr_lang('每天发布数量不能超过%s个', $day_post));
        }

        // 判断发布总量
        $total_post = $this->_member_value(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['total_post']
        );
        if ($this->uid && $total_post
            && \Phpcmf\Service::M()->db
                ->table($this->init['table'])
                ->where('uid', $this->uid)
                ->countAllResults() >= $total_post) {
            $this->_json(0, dr_lang('发布数量不能超过%s个', $total_post));
        }

        // 审核状态
        $is_verify = dr_member_auth(
            $this->member_authid,
            $this->member_cache['auth_site'][SITE_ID]['form'][$this->form['table']]['verify']
        );
        $data[1]['status'] = $is_verify ? 0 : 1;
        
        // 默认数据
        $data[0]['uid'] = $data[1]['uid'] = (int)$this->member['uid'];
        $data[1]['author'] = $this->member['username'] ? $this->member['username'] : 'guest';
        $data[1]['inputip'] = \Phpcmf\Service::L('Input')->ip_address();
        $data[1]['inputtime'] = SYS_TIME;
        $data[1]['tableid'] = 0;
        $data[1]['displayorder'] = 0;

        return $data;
    }


    /**
     * 回调处理结果
     * $data
     * */
    protected function _Call_Post($data) {

        // 挂钩点
        \Phpcmf\Hooks::trigger('form_post_after', $data);

        $data[1]['status'] && $this->_json(1, dr_lang('操作成功'));
        // 提醒
        \Phpcmf\Service::M('member')->admin_notice(SITE_ID, 'content', $this->member, dr_lang('%s提交审核', $this->form['name']), 'form/'.$this->form['table'].'_verify/edit:id/'.$data[1]['id'], SITE_ID);
        $this->_json(1, dr_lang('操作成功，等待管理员审核'), ['url' => $this->form['setting']['rt_url']]);
    }

    // 前端回调处理类
    protected function _Call_Show($data) {

        return $data;
    }
}
