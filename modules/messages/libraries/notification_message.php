<?php

class Notification_Message implements Notification_Handler {

	static function send($sender, $receivers, $title, $body) {
        if (!$sender->id) {
            $body = $body. "\n\n". I18N::T('messages', '[系统消息, 请勿回复]');
        }

		foreach ($receivers as $receiver) {
			$message = O('message');
			$message->sender = $sender;	
			$message->receiver = $receiver;
			$message->title = (string)new Markup($title, FALSE);
			$message->body = addslashes($body);
			$message->save();
		}
	}
}
