<?php

class Login_Controller extends Layout_Controller
{

    protected $layout_name = 'login_layout';

    public function index()
    {
        $this->layout->sidebar = V('sidebar');
        $this->layout->body = V('body');
        $this->add_css('admin:layout admin:sbmenu');

        $user = L('ME');

        if (Input::form('submit')) {
            try {
                $form = Form::filter(Input::form())
                        ->validate('token', 'not_empty', T('帐号不能为空！'))
                        ->validate('password', 'not_empty', T('密码不能为空！'));

                if ($form->no_error) {
                    $form['token'] = trim($form['token']);
                    $form['token_backend'] = trim($form['token_backend']);

                    $token = Auth::normalize($form['token'], $form['token_backend']);
                    $user = O('user', array('token' => $token));

                    $auth = new Auth($token);

                    if (!$auth->verify($form['password'])) {
                        Site::message(Site::MESSAGE_ERROR, T('帐号和密码不匹配！请您重新输入'));
                        throw new Error_Exception();
                    }

                    if (!$user->id) {
                        // 最终的结论：不允许用户在mall-old注册
                        Site::message(Site::MESSAGE_ERROR, T('请联系商城运营(400-843-6255)为您开通用户！'));
                        throw new Error_Exception();
                        // 处理gapper用户登陆的逻辑
                        if ($gapper_info = Gapper::get_user_by_identity($token)) {
                            $token = $gapper_info['username'];
                        }
                        // 处理gapper用户登陆的逻辑
                        elseif (O('gapper_fallback_user', ['token' => $token])->user->id) {
                            $token = $local_user->user->token;
                        }
                        // 非gapper用户，如南开一卡通用户，直接报错
                        else {
                            Site::message(Site::MESSAGE_ERROR, T('请联系管理员为您开通用户！'));
                            throw new Error_Exception();
                        }
                    }

                    Auth::login($token);
                }
            } catch (Error_Exception $e) {
            }
        }

        if (Auth::logged_in()) {
            if (!$user->id) {
                URI::redirect('!people/signup');
            }

            if ($form['persist']) {
                Site::remember_login($user);
            }

            Log::add(sprintf('用户%s[%d]登入成功', $user->name, $user->id), 'logon');
            Log::add(sprintf('[%s] %s[%d]成功登入系统', 'application', $user->name, $user->id), 'journal');

            if (isset($_SESSION['#LOGIN_REFERER'])) {
                $http_referer = $_SESSION['#LOGIN_REFERER'] ?: null;
                unset($_SESSION['#LOGIN_REFERER']);
                if ($http_referer) {
                    URI::redirect($http_referer);
                }
            }

            URI::redirect('/');
        }

        $this->layout->body = V('login', array('form' => $form));
    }
}
