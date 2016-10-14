<?php

class Message_Controller extends Base_Controller {

	function index($id=0) {

		$message = O('message', $id);
		if (!$message->id) {
			URI::redirect('error/404');
		}

		if ($message->receiver->id != L('ME')->id) {
			URI::redirect('error/401');
		}

		if (!$message->is_read) {
			$message->is_read = TRUE;
			$message->save();
		}

		$content =
			V('message/view')
				->set('message', $message);

		$this->layout->body->primary_tabs
			->add_tab('view', array(
				'url' => $message->url(),
				'title' => I18N::T('messages', '查看消息'),
			))
			->set('content', $content)
			->select('view');

	}

	function reply($id=0) {
		$message = O('message',$id);
		if (!$message->id || $message->receiver->id != L('ME')->id) {
			URI::redirect('error/404');
		}

		$form = Form::filter(Input::form());

		if($form['submit']) {
			try {
				$form
					->validate('title', 'not_empty', I18N::T('messages', '消息标题不能为空!'))
					->validate('body', 'not_empty', I18N::T('messages', '消息内容不能为空!'));
				if($form->no_error) {
					$receiver = O('user', $form['receiver']);

					if (!$receiver->id) {
						Site::message(Site::MESSAGE_ERROR, I18N::T('messages', '消息收件人不能为空!'));
					}

					$sender = L('ME');
					$message = O('message');
					$message->title = $form['title'];
					$message->body = $form['body'];
					$message->receiver = $receiver;
					$message->sender = $sender;
					$message->save();


					$log = sprintf('[message] %s[%d] 回复了 %s 的消息',
												L('ME')->name, L('ME')->id,
												$message->receiver->name);

					Log::add($log, 'journal');

					Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '消息发送成功!'));
					URI::redirect('!messages');
				}
			}
			catch (Error_Exception $e) {
			}
		}

		$content = V('message/reply',array('message'=>$message,'form'=>$form));

		$this->layout->body->primary_tabs
				->add_tab('reply',array(
					'url' => $message->url('','','','reply'),
					'title' => I18N::T('messages', '回复消息'),
				))
				->set('content', $content)
				->select('reply');
	}

	function delete($id=0) {
		$message = O('message',$id);
		if (!$message->id) {
			URI::redirect('error/404');
		}

		$user = L('ME');
		if ($message->receiver->id != $user->id) {
			URI::redirect('error/401');
		}

		if ($message->delete()) {

			/*添加记录*/
			$log = sprintf('[message] %s[%d] 删除了消息 %s[%d]', $user->name, $user->id, $message->title, $message->id);
			Log::add($log, 'journal');

			Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '消息删除成功！'));
		} else {
			Site::message(Site::MESSAGE_ERROR, I18N::T('messages', '消息删除失败！'));
		}
		URI::redirect('!messages/index');
	}

	function delete_read() {
		$me = L('ME');
		$msgs = Q("message[is_read][receiver=$me]");
		if (count($msgs)) {
			foreach($msgs as $message) {
				if ($message->receiver->id == $me->id) {
					$message->delete();
				}
			}
			Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '已读消息删除成功！'));
		}
		else {
			Site::message(Site::MESSAGE_ERROR, I18N::T('messages', '当前没有已读消息！'));
		}

		URI::redirect('!messages/index');
	}


	const BATCH_DELETE = 1;
	const BATCH_MARK_READ = 2;
	const BATCH_MARK_UNREAD = 3;

	function batch_action() {
		$user = L('ME');
		$form = Form::filter(Input::form());
		if (is_array($form['select'])) {
			$me = L('ME');
			if ($form['delete']) {
				$op = self::BATCH_DELETE;
			}
			elseif ($form['mark_read']) {
				$op = self::BATCH_MARK_READ;
			}
			elseif ($form['mark_unread']) {
				$op = self::BATCH_MARK_UNREAD;
			}
			foreach ($form['select'] as $id) {
				$message = O('message', $id);
				if ($message->id && $message->receiver->id == $me->id) {
					switch($op) {
					case self::BATCH_DELETE:
						$log = sprintf('[message] %s[%d] 删除了消息 %s[%d]',
													$user->name, $user->id,
													$message->title, $message->id);
						$message->delete();

						/*添加记录*/
						Log::add($log, 'journal');
						break;
					case self::BATCH_MARK_READ:
						$message->is_read = TRUE;
						$message->save();

						/*添加记录*/
						$log = sprintf('[message] %s[%d] 对消息 %s[%d] 标记已读',
													$user->name, $user->id,
													$message->title, $message->id);
						Log::add($log, 'journal');

						break;
					case self::BATCH_MARK_UNREAD:
						$message->is_read = FALSE;
						$message->save();
						/*添加记录*/
						$log = sprintf('[message] %s[%d] 对消息 %s[%d] 标记未读',
													$user->name, $user->id,
													$message->title, $message->id);
						Log::add($log, 'journal');
						break;
					}
				}
			}

			switch($op) {
			case self::BATCH_DELETE:
				Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已删除成功！'));
				break;
			case self::BATCH_MARK_READ:
				Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已标记为已读！'));
				break;
			case self::BATCH_MARK_UNREAD:
				Site::message(Site::MESSAGE_NORMAL, I18N::T('messages', '您选中的消息已标记为未读！'));
				break;
			}

		}

		URI::redirect('!messages/index');
	}

}

