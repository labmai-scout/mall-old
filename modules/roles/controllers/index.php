<?php

class Index_Controller extends Layout_Admin_Controller {
	
	function _before_call($method, &$params){

		parent::_before_call($method, $params);

		$this->layout->body = V('roles:body');
		$this->layout->body->primary_tabs = Widget::factory('tabs');
		$this->layout->body->primary_tabs
			->add_tab('roles', array(
					'url'=> URI::url('!roles/index/roles'),
					'title'=>T('角色设置'),
				));
	}
	
	function index(){
		URI::redirect('!roles/index/roles');
	}
	
	function delete($id=NULL){
		if(L('ME')->access('管理分组') && $id){
			$role = O('role', $id);
			if($role->id > 0){
				if ($role->delete()) {
					/* 记录日志 */
					$log = sprintf('[roles] %s[%d]删除了角色%s[%d]',
								   L('ME')->name, L('ME')->id,
								   $role->name, $role->id);
					Log::add($log, 'journal');

					Site::message(Site::MESSAGE_NORMAL, HT('角色 %name 删除成功!', array('%name'=>$role->name)));
				}
			}
		}
		URI::redirect(URI::url('!roles/index/roles'));
	}
	
	function perms($id=NULL) {
		if (!(L('ME')->access('管理分组'))) {
			URI::redirect('error/401');
		}

		$roles = L('ROLES');
		$role = $roles[$id];

		if (!$role->id) {
			URI::redirect('error/404');
		}

		$breadcrumb = array(
			array(
				'url' => URI::url('!roles'),
				'title' => T('角色设置')
			),
			array(
				'url' => URI::url('!roles/perms.' . $role->id),
				'title' => HT('%name权限设置', array('%name' => $role->name))
			)
		);
		$this->layout->body->primary_tabs
			->add_tab('perms', array(
						  '*' => $breadcrumb
					  ))
			->select('perms');
		
		$form = Form::filter(Input::form());
		if($form['submit']){
			if($form->no_error){
				$changed = false;
				// NO.BUG#249(xiaopei.li@2010.12.17)
				// 以前若未选中任何权限就提交，会出错
				$role_perms = array();
				$perm_form = (array) $form['perms'][$role->id];
				foreach ($perm_form as $perm_name=>$access) {
					if ($perm_name == '管理所有内容' && !L('ME')->access($perm_name)) continue;
					$role_perms[$perm_name] = $access;
				}
		
				if ($role->perms != $role_perms) {
					// 由于$role->id可能小于0 (虚拟角色: 当前成员, 过期成员, 访客)
					Properties::factory($role)->set('perms', $role_perms)->save();
					/* 记录日志 */
					$log = sprintf('[roles] %s[%d]修改了角色%s[%d]的权限',
								   L('ME')->name, L('ME')->id,
								   $role->name, $role->id);
					Log::add($log, 'journal');

					Site::message(Site::MESSAGE_NORMAL, T('相关权限设置保存成功！'));
				}
				else {
					Site::message(Site::MESSAGE_ERROR, T('您没有做任何修改!'));
				}
			}
		}
		$this->add_css('roles:perms');
		$this->layout->body->content = V('perm');
		$this->layout->set('role', $role);
	}
	
	function roles() {

		if(!L('ME')->access('管理分组')){
			URI::redirect('error/401');
		}
		
		$this->layout->body->primary_tabs->select('roles');
		$this->layout->body->content = V('roles');
		
		$roles = clone L('ROLES');
		$roles_arr = $roles->to_assoc('id', 'name');
		
		$form = Form::filter(Input::form());
		if($form['submit']){
			$changed = FALSE;
			foreach($form['roles'] as $id=>$name){
				$role = O('role', $id);
				if( !($name == '' || in_array($name, $roles_arr) || $role->name == $name) ){ //不可出现重名现象
					$role->name = $name;
					$role->save();
					$roles->append(array('id'=>$role->id, 'name'=>$role->name, 'weight'=>$role->weight));
					$changed = TRUE;
				}
			}
			if ($changed) {
				/* 记录日志 */
				$log = sprintf('[roles] %s[%d]添加了角色%s[%d]',
							   L('ME')->name, L('ME')->id,
							   $role->name, $role->id);
				Log::add($log, 'journal');

				/* BUG #842::修改角色名称后提示“角色添加成功”，应该是角色名称修改成功等提示，而不是添加成功。(kai.wu@2011.7.25) */
				if ($role->id == 0)
					Site::message(Site::MESSAGE_NORMAL, T('角色修改成功！'));
				else
					Site::message(Site::MESSAGE_NORMAL, T('角色添加成功！'));
			}
			else{
				Site::message(Site::MESSAGE_ERROR, T('您没有做任何修改!'));
			}
		}

		$this->add_css('roles:role_sortable');
		$this->add_js('roles:role_sortable');
		
		$this->layout->set('roles', $roles);
	}
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_role_change_weight() {
		$form = Form::filter(Input::form());
		$role = O('role', $form['role_id']);
		$uniqid = $form['uniqid'];
		if (!$role->id) return;
		$prev_weight = Q::quote($form['prev_index']); 
		$next_weight = $prev_weight + 1;
		$current_weight = $role->weight;
		if ($prev_weight == $next_weight) return;

		$next_role = O('role', array('weight'=>$next_weight));
		$change_weight = $next_weight;
		
		if ($next_role->id) {
			$selector = "role[weight > %s][weight < %s]:sort(weight %s)";
			if ($prev_weight < $current_weight) {
				/*拖拽向上运动*/
				$tmp_weight = $change_weight = $next_weight;
				$selector = sprintf($selector, $prev_weight, $current_weight, 'A');
				$way = true;
			}
			else {
				/*拖拽向下运动*/
				$tmp_weight = $change_weight = $prev_weight;
				$selector = sprintf($selector, $current_weight, $next_weight, 'D');
				$way = false;
			}
			$roles = Q($selector);
			foreach ($roles as $r) {
				if ((int)$r->weight != $tmp_weight) {
					continue;
				}
				else {
					if ($way) {
						$tmp_weight ++;
					}
					else {
						$tmp_weight --;
					}
					$r->weight = $tmp_weight;
					$r->save();
				}
			}
		}
		$role->weight = $change_weight;
		$role->save();
		
		/*
			TUDO 该处由于更换了role的属性，所以不能直接从L(ROLES)中直接获取，需要重新生成一遍，
			而暂时由于特殊性，不能将默认角色显示的view和能编辑的角色固定显示view彻底分离开，所以需要在保存之后重新查找出来roles并增加默认角色，该处需要之后优化。
		*/
		$roles = Q('role:sort(weight A)');
		
		$default_roles = Config::get('roles.default_roles');
		/* 对默认角色按weight排序 */
		uasort($default_roles, function($a, $b) {
			return $a['weight'] < $b['weight'];
		});

		foreach ($default_roles as $role_id => $role_description) {
			$roles->prepend(array('id' => $role_id, 'name' => T($role_description['name']), 'weight' => $role_description['weight']));
		}
		
		Output::$AJAX["#$uniqid > .role_root_container"] = array(
			'data' => (string)V('role_list', array('roles' => $roles))
		);
	}
}
