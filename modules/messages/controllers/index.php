<?php

class Index_Controller extends Base_Controller {

	function index(){
		$form = Site::form();

		$start = (int) $form['st'];
		$per_page = 15;
		$start = $start - ($start % $per_page);

		$query = $form['query'];

		if($query) {
			$query = Q::quote($query);

			$selector = "message[title*={$query}]";
		}
		else {
			$selector = 'message';
		}

		$me = L('ME');
		$selector .= "[receiver={$me}]:sort(is_read A, ctime D)";

		$messages = Q($selector);

		if($start > 0) {
			$last = floor($messages->total_count() / $per_page) * $per_page;
			if ($last == $messages->total_count()) $last = max(0, $last - $per_page);
			if ($start > $last) {
				$start = $last;
			}
			$messages = $messages->limit($start, $per_page);
		} else {
			$messages = $messages->limit($per_page);
		}

		$pagination = Widget::factory('pagination');
		$pagination->set(array(
			'start' => $start,
			'per_page' => $per_page,
			'total' => $messages->total_count(),
		));

		$content = V('index',array('messages'=>$messages,'pagination'=>$pagination, 'form'=>$form));

		//if (Browser::name() != 'ie') {
			$tab = $this->layout->body->primary_tabs->get_tab('index');
			$me = L('ME');
			$tab['number'] = Q("message[receiver=$me][is_read=0]")->total_count();
			$this->layout->body->primary_tabs->set_tab('index', $tab);
		//}

		$this->layout->body->primary_tabs
			->select('index')
			->set('content', $content);

	}

	/* BUG #834::送样预约机主对送样者发送消息时，应预填消息标题
	   解决：给add方法添加表示仪器id的参数$eq，默认为NULL。该参数由!messages/index/send方法传递。
	   添加判断当$eq非空时，将$form['title']赋值为$eq所对应的name。。(kai.wu@2011.7.25)
	 */
	function add($to=0, $eq=NULL) {
		$form = Form::filter(Input::form());
		$to = O('user', $to);
        $me = L('ME');
        if (!$me->id || !$me->is_active()) URI::redirect('error/401');

		if ($form['submit']) {
			try {

				/* NO.BUG#239(xiaopei.li@2010.12.13) */
				/* BUG #836::在添加消息页面先选择一个收件人名称后再删除并且发送的消息有标题和内容时就算收件人一项为空也可点击发送并提示消息发送成功 但收件人并不能收到消息。
				   原因： 只有一个收件人时，先添加再删除的操作将会把$form['receivers']的值改为string(2)"{}"而不是NULL，这样的话validate就能够正常通过而不会报错。
				   解决： 用preg_match将"{}"筛选掉，并将$form['receivers']置为NULL。(kai.wu@2011.7.25) */

				$receivers_type = $form['receivers_type'];

				$receivers = $this->_get_receivers_users('user', $form);


				$form
					->validate('title', 'not_empty', I18N::T('messages', '消息标题不能为空!'))
					->validate('body', 'not_empty', I18N::T('messages', '消息内容不能为空!'));

				if (!$form->no_error) throw new Error_Exception();

				foreach ($receivers as $user_id=>$user_name) {

					if (!$user_id) continue;

					$message = O('message');
					$message->title = $form['title'];
					$message->body = $form['body'];
					$message->receiver = O('user', $user_id);
					$message->sender = $me;
					$ret = $message->save();
				}

				if ($ret) {
					$log = sprintf('[message] %s[%d] 添加了新消息 %s[%d]',
								   L('ME')->name, L('ME')->id,
								   $message->title, $message->id);
					Log::add($log, 'journal');


					if (Config::get('messages.send.by.otherway', FALSE)) {
						Event::trigger('message.send.way.submit', L('ME'), $form, $receivers);
					}

					Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '消息发送成功!'));
					URI::redirect('!messages');
				}
				else {
					Site::message(Site::MESSAGE_ERROR, I18N::T('messages', '消息发送失败!'));
				}

			}
			catch (Error_Exception $e) {
			}
		}

		if (!$form['submit'] && $eq) {
			$title = Event::trigger('message.title.get', $eq);
			if ($title) $form['title'] = H($title);
		}

		$tmp_receivers = isset($form['receivers']) ? json_decode($form['receivers'], TRUE) : null;
		if (empty($tmp_receivers) && $to->id>0) {
			$form['receivers'] = json_encode(array(
				$to->id => $to->name
			));
		}
		$this->layout->body->primary_tabs->select('add');
		$this->layout->body->primary_tabs->content = V('add', array('form'=>$form, 'to'=>$to));
	}

	function _get_receivers_users($receivers_type, $form = NULL) {

		if ($receivers_type == 'user') {
			//用户发送
			$form['receiver_users'] = ($form['receiver_users'] != '{}') ? $form['receiver_users'] : NULL;

			if (!$form['receiver_users']) {
				$form->set_error('receiver_users', I18N::T('messages', '收件用户不能为空!'));
				return FALSE;
			}

			return json_decode($form['receiver_users'], TRUE);

		}
		elseif ($receivers_type == 'all') {
			//所有人发送
			return Q('user[!hidden]')->to_assoc('id', 'name');
		}
		elseif ($receivers_type == 'group') {
			//组织机构发送

			$group = O('tag', $form['receiver_group']);

			if ($group->id) {

				$group_root = Tag_Model::root('group');
				if ($group_root->id == $group->id) {
				       return Q('user[!hidden]')->to_assoc('id', 'name')->to_assoc('id', 'name');
				}
				else {
				       return Q('(tag#'.$form['receiver_group'].') user')->to_assoc('id', 'name');
				}
			}
			else {
				$groups = array_keys((array)json_decode($form['receiver_group'], TRUE));
				return Q('(tag#'.join('| tag#', $groups).') user')->to_assoc('id', 'name');
			}

		}
		elseif ($receivers_type == 'role') {
			//角色发送
			$users = Event::trigger('people.get.users.by.role', $form['receiver_role'], Config::get('messages.send.by.otherway'));
			return $users;
		}
		elseif($receivers_type == 'lab' && Module::is_installed('labs')) {
			//实验室发送
			$form['receiver_labs'] = ($form['receiver_labs'] != '{}') ? $form['receiver_labs'] : NULL;
			if (!$form['receiver_labs']) {
				$form->set_error('receiver_labs', I18N::T('messages', '收件实验室不能为空!'));
				return FALSE;
			}

			$labs = json_decode($form['receiver_labs'], TRUE);
			$new_labs = join(',', array_flip($labs));
			$users = Q("user[!hidden][lab_id=$new_labs]")->to_assoc('id', 'name');
			return $users;
		}

	}
}

class Index_AJAX_Controller extends AJAX_Controller {
	function index_delete_selected_click() {
		$me = L('ME');
		$selected_ids = Input::form('selected_ids');
		if (JS::confirm(I18N::T('messages','您确定删除选中的消息吗?'))) {
			foreach ($selected_ids as $id) {
				$message = O('message', $id);
				if ($message->id && $message->receiver->id == $me->id) {
					$message->delete();
					Log::add(sprintf('[message] %s[%d] 删除了消息 %s[%d]', $me->name, $me->id, $message->title, $message->id), 'journal');
				}
			}
			$form_token = Input::form('form_token');
			$uniqid = $_SESSION[$form_token];
			JS::refresh($uniqid);
		}
	}
}
