<?php
class Index_Controller extends Layout_Mall_Controller {

    public function index($id = 0) {
        $news = O('news', $id);
        if ($news->id) {
            $this->layout->body = V('news:view', array('news'=> $news));
        }
        else {
            $news = Q('news:sort(ctime D)');
            $this->layout->body = V('news:home', array('news'=> $news));
        }
        $this->layout->nav_tabs = V('mall:tab', array('select_tab'=> 'news'));
        $this->add_css('mall:content news:common');
    }
}

class Index_AJAX_Controller extends AJAX_Controller {

	function index_edit_news_click() {
		$form = Form::filter(Input::form());
		$id = $form['a_id'];
		$news = O('news', $id);
		
		if (L('ME')->is_allowed_to('修改', $news)) {
			JS::dialog(V('news:admin/edit', array('news'=>$news)));
		}
	}

	function index_edit_news_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');
		
		$news = O('news', $form['a_id']);

		if (!$news->id) {
			JS::alert(T('更新失败!'));
            return FALSE;
		}
		
		if ($form['submit']) {
			$form->validate('title', 'not_empty', T('请填写公告标题！'))
				->validate('content',  'not_empty', T('请填写公告内容！'));
			if ($form->no_error) {
				$news->title = $form['title'];
				$news->content = $form['content'];
				
				if ($news->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('公告更新成功！'));
					JS::refresh();
				}
				else {
					JS::alert(T('更新失败!'));
				}
			}
			else {
				JS::dialog(V('news:admin/edit', array('news'=>$news, 'form'=>$form)));
			}
		}
	}

	function index_delete_news_click() {
		if (!JS::confirm(T('你确定要删除该公告吗?请谨慎操作!') )) {
			return;
		}
		$form = Form::filter(Input::form());
		$news = O('news', $form['a_id']);
		if (!$news->id) return;
		$me = L('ME');
		if (!$me->is_allowed_to('删除', $news)) return;
		if ($news->delete()) {
			Site::message(Site::MESSAGE_NORMAL, T('公告删除成功！'));
		}
		JS::refresh();
	}
	
	function index_add_news_click() {
		if (L('ME')->is_allowed_to('添加', 'news')) {
			JS::dialog(V('news:admin/add'));
		}
	}

	function index_add_news_submit() {
		$form = Form::filter(Input::form());
		$me = L('ME');

		if ($form['submit']) {
			$form->validate('title', 'not_empty', T('请填写公告标题！'))
				->validate('content',  'not_empty', T('请填写公告内容！'));
			if ($form->no_error) {
				$news = O('news');
				$news->title = $form['title'];
				$news->content = $form['content'];

				if ($news->save()) {
					Site::message(Site::MESSAGE_NORMAL, T('公告添加成功！'));
					JS::refresh();
				}
				else {
					JS::alert(T('添加失败!'));
				}
			}
			else {
				JS::dialog(V('news:admin/add', array('form'=>$form)));
			}
		}
	}
}
