<?php

class News {
    
    static function admin_admin_setup() {
        Event::bind('admin.index.tab', 'News::admin_news_tab');
    }

    static function admin_news_tab($e, $tabs) {
        $params = Config::get('system.controller_params');

        switch ($params[0]) {
            case 'news_view':
                Event::bind('admin.index.content', 'News::admin_news_content_view', 0, 'news_view'); 
                $breadcrumb = array(
                    array(
                        'url' => URI::url('!admin/admin/news'),
                        'title'=> T('新闻公告')
                    ),
                    array(
                        'url'=>URI::url('!admin/admin/news_view'),
                        'title'=>T('查看'),
                    )
                );

                $tabs->add_tab('news_view', array('*'=>$breadcrumb));
                break;            
            case 'news_add':
                Event::bind('admin.index.content', 'News::admin_news_content_add', 0, 'news_add'); 
                $breadcrumb = array(
                    array(
                        'url' => URI::url('!admin/admin/news'),
                        'title'=> T('新闻公告')
                    ),
                    array(
                        'url'=>URI::url('!admin/admin/news_add'),
                        'title'=>T('添加'),
                    )
                );

                $tabs->add_tab('news_add', array('*'=>$breadcrumb));
                break;
            case 'news_edit':
                Event::bind('admin.index.content', 'News::admin_news_content_edit', 0, 'news_edit'); 
                $breadcrumb = array(
                    array(
                        'url' => URI::url('!admin/admin/news'),
                        'title'=> T('新闻公告')
                    ),
                    array(
                        'url'=>URI::url('!admin/admin/news_edit'),
                        'title'=>T('修改'),
                    )
                );

                $tabs->add_tab('news_edit', array('*'=>$breadcrumb));
                break;
            
            default:
                Event::bind('admin.index.content', 'News::admin_news_content', 0, 'news');
                $tabs->add_tab('news', array(
                    'url' => URI::url('!admin/admin/news'),
                    'title'=> T('新闻公告')
                ));
                break;
        }
    }

    static function admin_news_content($e, $tabs) {
        $news = Q('news:sort(ctime D)');

        $tabs->content = V('news:admin/list', array(
            'news' => $news
        ));
    }

    static function admin_news_content_add($e, $tabs) {
        $form = Form::filter(Input::form());
        $me = L('ME');

        if ($form['submit']) {
            $form->validate('title', 'not_empty', T('请填写公告标题！'))
                ->validate('content',  'not_empty', T('请填写公告内容！'));
            if ($form['title'] && (mb_strlen($form['title']) > (Config::get('news.default_title_length', 50)))) {
                $form->set_error('title',
                     T('标题不得超过%num字符', array(
                        '%num' => Config::get('news.default_title_length', 50)
                     )));
            }
            if ($form->no_error) {
                $news = O('news');
                $news->title = $form['title'];
                $news->content = $form['content'];

                if ($news->save()) {
                    Site::message(Site::MESSAGE_NORMAL, T('公告添加成功！'));
                    URI::redirect('!admin/admin/news');
                }
            }
        }

        $tabs->content = V('news:admin/add', array('form'=>$form));
    }

    static function admin_news_content_edit($e, $tabs) {
        $params = Config::get('system.controller_params');
        $form = Form::filter(Input::form());
        $news = O('news', $params[1]);

        if ($form['submit']) {
            $form->validate('title', 'not_empty', T('请填写公告标题！'))
                ->validate('content',  'not_empty', T('请填写公告内容！'));
            if ($form['title'] && (mb_strlen($form['title']) > (Config::get('news.default_title_length', 50)))) {
                $form->set_error('title',
                     T('标题不得超过%num字符', array(
                        '%num' => Config::get('news.default_title_length', 50)
                     )));
            }
            if ($form->no_error) {
                $news->title = $form['title'];
                $news->content = $form['content'];
                
                if ($news->save()) {
                    Site::message(Site::MESSAGE_NORMAL, T('公告更新成功！'));
                    JS::refresh();
                }
            }
        }
       
        $tabs->content = V('news:admin/edit', array('news' => $news, 'form' => $form));
    }

    static function admin_news_content_view($e, $tabs) {
        $params = Config::get('system.controller_params');
        $news = O('news', $params[1]);       
        $tabs->content = V('news:admin/admin_view', array('news' => $news));
    }

    static function news_ACL($e, $user, $perm, $object, $options) {
        $e->return_value = TRUE;
        return FALSE;
    }

    static function attachments_ACL($e, $user, $perm, $object, $options) {
        switch($perm) {
            case '列表文件' :
            case '下载文件' :
                $e->return_value = TRUE;
                break;
            case '上传文件' :
            case '修改文件' :
            case '删除文件' :
                if ($user->access('查看控制面板')) {
                    $e->return_value = TRUE; 
                }
                break;
        }

        return FALSE;
    }
}
