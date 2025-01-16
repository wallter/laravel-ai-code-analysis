<?php

namespace App\Enums;

enum AIResultContentType: string
{
    case JSON = 'json';
    case TEXT = 'text';
    case MARKDOWN = 'markdown';
    case HTML = 'html';
}
