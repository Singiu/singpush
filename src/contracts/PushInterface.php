<?php

namespace Singiu\Singpush\Contracts;

interface PushInterface
{
    public function sendMessage($deviceToken, $title, $message);
}